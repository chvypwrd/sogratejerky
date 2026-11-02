<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Models\Cart;
use App\Models\DeliveryZone;
use App\Models\FulfillmentSetting;
use App\Models\Order;
use App\Models\PickupLocation;
use App\Models\ShippingRateRule;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutAvailability;
use App\Services\CheckoutPaymentService;
use App\Services\PaymentReconciler;
use App\Services\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutAvailability $availability,
        private CheckoutPaymentService $paymentService,
        private PaymentReconciler $reconciler,
        private StripeGateway $gateway,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $this->availability->ensureEnabled();

        $cart = $this->cartService->resolveCart();
        $cart->load('items.variant.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        if ($cart->items->contains(fn ($item) => ! $item->isAvailable())) {
            return redirect()->route('cart.index')->with('error', 'One or more items in your cart are unavailable. Please update your cart before checking out.');
        }

        $token = $this->checkoutTokenFor($cart, $request->user());

        $addresses = $request->user()
            ? $request->user()->addresses()->orderByDesc('is_default_shipping')->orderBy('id')->get()
            : collect();

        return view('checkout.index', [
            'cart' => $cart,
            'subtotal' => $this->cartService->subtotal($cart),
            'itemCount' => $this->cartService->itemCount($cart),
            'checkoutToken' => $token,
            'setting' => FulfillmentSetting::current(),
            'shippingRules' => ShippingRateRule::active()->ordered()->get(),
            'deliveryZones' => DeliveryZone::active()->ordered()->get(),
            'pickupLocations' => PickupLocation::active()->ordered()->get(),
            'addresses' => $addresses,
            'stripeConfigured' => $this->gateway->configured(),
            'stripePublishableKey' => $this->gateway->publishableKey(),
            'stripeTestMode' => $this->gateway->isTestMode(),
            'saveCardAvailable' => (bool) $request->user(),
        ]);
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $this->availability->ensureEnabled();

        $data = $request->validated();

        if (! hash_equals((string) session('checkout_token'), $data['checkout_token'])) {
            throw ValidationException::withMessages([
                'checkout_token' => 'Your checkout session expired. Please review your cart and try again.',
            ]);
        }

        $cart = $this->cartService->resolveCart();
        $token = $data['checkout_token'];
        $order = Order::query()->where('checkout_token', $token)->first();

        if ($order && ($order->isTerminalCheckout() || ! $order->belongsToCheckoutContext($cart, $request->user()))) {
            $token = $this->rotateCheckoutToken();
        }

        $response = $this->paymentService->start($cart, $data, $request->user(), $token);
        $response['checkout_token'] = $token;

        return response()->json($response);
    }

    private function checkoutTokenFor(Cart $cart, ?User $user): string
    {
        $token = session('checkout_token');

        if (! is_string($token) || strlen($token) < 40) {
            return $this->rotateCheckoutToken();
        }

        $order = Order::query()->where('checkout_token', $token)->first();

        if ($order && ($order->isTerminalCheckout() || ! $order->belongsToCheckoutContext($cart, $user))) {
            return $this->rotateCheckoutToken();
        }

        return $token;
    }

    private function rotateCheckoutToken(): string
    {
        $token = Str::random(64);
        session(['checkout_token' => $token]);

        return $token;
    }

    public function paymentReturn(Request $request, Order $order): View
    {
        $payment = $order->payments()->first();
        $error = null;

        if ($payment) {
            try {
                $payment = $this->reconciler->reconcilePayment($payment);
            } catch (Throwable $exception) {
                report($exception);
                $payment = $payment->fresh();
                $error = 'We could not verify the payment status yet. Stripe may still be processing this payment.';
            }
        } else {
            $error = 'No payment attempt was found for this order.';
        }

        $order = $order->fresh()->load('items');

        if ($payment && $order->isTerminalCheckout() && hash_equals((string) session('checkout_token'), (string) $order->checkout_token)) {
            $this->rotateCheckoutToken();
        }

        return view('checkout.return', [
            'order' => $order,
            'payment' => $payment,
            'error' => $error,
            'confirmationUrl' => URL::temporarySignedRoute(
                'checkout.confirmation',
                now()->addDays(7),
                ['order' => $order->order_number],
            ),
        ]);
    }

    public function confirmation(Request $request, Order $order): View
    {
        return view('checkout.confirmation', [
            'order' => $order->load(['items', 'payments']),
        ]);
    }
}
