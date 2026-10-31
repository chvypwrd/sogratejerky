<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Models\DeliveryZone;
use App\Models\FulfillmentSetting;
use App\Models\Order;
use App\Models\PickupLocation;
use App\Models\ShippingRateRule;
use App\Services\CartService;
use App\Services\CheckoutCalculator;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutCalculator $checkoutCalculator,
        private OrderService $orderService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->resolveCart();
        $cart->load('items.variant.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        if ($cart->items->contains(fn ($item) => ! $item->isAvailable())) {
            return redirect()->route('cart.index')->with('error', 'One or more items in your cart are unavailable. Please update your cart before checking out.');
        }

        $token = session('checkout_token');

        if (! is_string($token) || strlen($token) < 40) {
            $token = Str::random(64);
            session(['checkout_token' => $token]);
        }

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
        ]);
    }

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        abort_if(app()->isProduction(), 404);

        $data = $request->validated();
        $existing = Order::where('checkout_token', $data['checkout_token'])->first();

        if (! $existing && ! hash_equals((string) session('checkout_token'), $data['checkout_token'])) {
            throw ValidationException::withMessages([
                'checkout_token' => 'Your checkout session expired. Please review your cart and try again.',
            ]);
        }

        $cart = $this->cartService->resolveCart();

        $order = $existing ?: $this->orderService->createFromCheckout(
            $cart,
            $data,
            $this->checkoutCalculator->calculate($cart, $data['fulfillment_method'], $data, $request->user()),
            $request->user(),
            $data['checkout_token'],
        );

        session()->forget('checkout_token');

        if ($request->user()) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('status', 'Your order has been placed.');
        }

        return redirect()
            ->to(URL::temporarySignedRoute(
                'checkout.confirmation',
                now()->addDays(7),
                ['order' => $order->order_number]
            ))
            ->with('status', 'Your order has been placed.');
    }

    public function confirmation(Request $request, Order $order): View
    {
        return view('checkout.confirmation', [
            'order' => $order->load('items'),
        ]);
    }
}
