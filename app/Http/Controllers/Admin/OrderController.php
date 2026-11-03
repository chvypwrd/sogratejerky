<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderEvent;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ShippingCarrier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Http\Requests\Admin\FulfillmentNoteRequest;
use App\Http\Requests\Admin\RefundOrderRequest;
use App\Http\Requests\Admin\ShipOrderRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SiteBranding;
use App\Services\OrderFulfillmentService;
use App\Services\PaymentRefundService;
use App\Services\SettingsService;
use App\Services\StripeGateway;
use App\Services\TestOrderCleanupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private OrderFulfillmentService $fulfillmentService,
        private PaymentRefundService $refundService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'order_status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'fulfillment_status' => ['nullable', Rule::enum(FulfillmentStatus::class)],
            'fulfillment_method' => ['nullable', Rule::enum(FulfillmentMethod::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
            'order_type' => ['nullable', Rule::in(['regular', 'test', 'all'])],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $orderType = $validated['order_type'] ?? 'regular';

        $query = Order::query()->withCount('items');

        if ($orderType === 'regular') {
            $query->regular();
        } elseif ($orderType === 'test') {
            $query->testOrders();
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('customer_email', 'like', '%'.$search.'%');
            });
        }

        foreach (['order_status', 'payment_status', 'fulfillment_status', 'fulfillment_method'] as $filter) {
            if (! empty($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('placed_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('placed_at', '<=', $validated['date_to']);
        }

        $sort = $validated['sort'] ?? 'newest';
        $direction = $sort === 'oldest' ? 'asc' : 'desc';

        $orders = $query
            ->orderBy('placed_at', $direction)
            ->orderBy('id', $direction)
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'awaiting_payment' => Order::regular()
                ->where('payment_status', PaymentStatus::Pending->value)
                ->where('order_status', '!=', OrderStatus::Cancelled->value)
                ->count(),
            'paid_awaiting_fulfillment' => Order::regular()
                ->where('payment_status', PaymentStatus::Paid->value)
                ->where('fulfillment_status', FulfillmentStatus::Unfulfilled->value)
                ->where('order_status', '!=', OrderStatus::Cancelled->value)
                ->count(),
            'ready_for_pickup' => Order::regular()->where('fulfillment_status', FulfillmentStatus::ReadyForPickup->value)->count(),
            'out_for_delivery' => Order::regular()->where('fulfillment_status', FulfillmentStatus::OutForDelivery->value)->count(),
            'recently_fulfilled' => Order::regular()->whereNotNull('fulfilled_at')
                ->where('fulfilled_at', '>=', now()->subDays(7))
                ->count(),
        ];

        $validated['order_type'] = $orderType;

        return view('admin.orders.index', [
            'orders' => $orders,
            'summary' => $summary,
            'filters' => $validated,
            'orderStatuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
            'fulfillmentMethods' => FulfillmentMethod::cases(),
        ]);
    }

    public function show(Order $order, TestOrderCleanupService $cleanup): View
    {
        $this->authorize('view', $order);

        $order->load(['items', 'activities.actor', 'user', 'payments.refunds.requester']);

        return view('admin.orders.show', [
            'order' => $order,
            'carriers' => ShippingCarrier::cases(),
            'testCleanup' => $order->is_test_order ? $cleanup->inspect($order, true) : null,
        ]);
    }

    public function testCleanup(Request $request, TestOrderCleanupService $cleanup): View
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::testOrders()
            ->latest('placed_at')
            ->paginate(25)
            ->withQueryString();

        $orders->getCollection()->transform(function (Order $order) use ($cleanup) {
            $order->setAttribute('test_cleanup', $cleanup->inspect($order, true));

            return $order;
        });

        return view('admin.orders.test-cleanup.index', compact('orders'));
    }

    public function previewTestCleanup(Order $order, TestOrderCleanupService $cleanup): View
    {
        $this->authorize('refund', $order);

        return view('admin.orders.test-cleanup.preview', [
            'order' => $order,
            'inspection' => $cleanup->inspect($order, true),
        ]);
    }

    public function destroyTestOrder(Request $request, Order $order, TestOrderCleanupService $cleanup): RedirectResponse
    {
        $this->authorize('refund', $order);

        $request->validate([
            'confirm_order_number' => ['required', 'string'],
        ]);

        if ($request->input('confirm_order_number') !== $order->order_number) {
            throw ValidationException::withMessages([
                'confirm_order_number' => 'Enter the exact order number to confirm permanent deletion.',
            ]);
        }

        $audit = $cleanup->delete($order, $request->user());

        return redirect()
            ->route('admin.orders.test-cleanup')
            ->with('status', 'Test order '.$audit->order_number.' permanently deleted. Audit record #'.$audit->id.'.');
    }

    public function updateTestStatus(Request $request, Order $order, StripeGateway $gateway): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        $request->validate([
            'is_test_order' => ['required', 'boolean'],
        ]);

        $isTestOrder = $request->boolean('is_test_order');

        if ($isTestOrder && ! $order->is_test_order) {
            $this->assertVerifiedTestOrder($order, $gateway);
        }

        $previous = (bool) $order->is_test_order;

        if ($previous !== $isTestOrder) {
            $order->update(['is_test_order' => $isTestOrder]);

            $order->activities()->create([
                'event' => ($isTestOrder ? OrderEvent::TestOrderMarked : OrderEvent::TestOrderUnmarked)->value,
                'previous_status' => $previous ? 'test' : 'regular',
                'new_status' => $isTestOrder ? 'test' : 'regular',
                'actor_id' => $request->user()->id,
                'actor_name' => $request->user()->name,
                'note' => $isTestOrder
                    ? 'Order marked as a test order after Stripe test-mode verification.'
                    : 'Test order classification removed.',
                'occurred_at' => now(),
            ]);
        }

        return back()->with('status', $isTestOrder ? 'Order marked as a test order.' : 'Test order flag removed.');
    }

    public function workOrder(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.print.work-order', $this->printData($order));
    }

    public function invoice(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.print.invoice', $this->printData($order));
    }

    public function markReadyForPickup(FulfillmentNoteRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        $this->fulfillmentService->markReadyForPickup($order, $request->user(), $request->validated('note'));

        return back()->with('status', 'Order marked ready for pickup.');
    }

    public function markPickedUp(FulfillmentNoteRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        $this->fulfillmentService->markPickedUp($order, $request->user(), $request->validated('note'));

        return back()->with('status', 'Order marked picked up.');
    }

    public function markOutForDelivery(FulfillmentNoteRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        $this->fulfillmentService->markOutForDelivery($order, $request->user(), $request->validated('note'));

        return back()->with('status', 'Order marked out for delivery.');
    }

    public function markDelivered(FulfillmentNoteRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        $this->fulfillmentService->markDelivered($order, $request->user(), $request->validated('note'));

        return back()->with('status', 'Order marked delivered.');
    }

    public function markShipped(ShipOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        $data = $request->validated();

        $this->fulfillmentService->markShipped(
            $order,
            $request->user(),
            ShippingCarrier::from($data['shipping_carrier']),
            $data['tracking_number'] ?? null,
            $request->boolean('untracked'),
            $data['note'] ?? null,
        );

        return back()->with('status', 'Order marked shipped.');
    }

    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $this->fulfillmentService->cancel($order, $request->user(), $request->validated('reason'));

        return back()->with('status', 'Order cancelled.');
    }

    public function refund(RefundOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('refund', $order);

        $payment = $order->payments()->whereIn('status', ['paid', 'partially_refunded'])->first();

        if (! $payment) {
            return back()->withErrors(['refund' => 'No captured payment is available to refund.']);
        }

        $data = $request->validated();
        $amountCents = (int) round(((float) $data['amount']) * 100);

        $this->refundService->request(
            $order,
            $payment,
            $request->user(),
            $amountCents,
            $data['reason'] ?? null,
            $data['request_key'],
        );

        return back()->with('status', 'Refund request submitted.');
    }

    private function assertVerifiedTestOrder(Order $order, StripeGateway $gateway): void
    {
        $payments = $order->payments()
            ->whereNotNull('payment_intent_id')
            ->get();

        if ($payments->isEmpty()) {
            throw ValidationException::withMessages([
                'is_test_order' => 'This order has no Stripe PaymentIntent to verify as a test order.',
            ]);
        }

        foreach ($payments as $payment) {
            if ($payment->stripe_livemode === true) {
                throw ValidationException::withMessages([
                    'is_test_order' => 'This order contains live Stripe payments and cannot be marked as a test order.',
                ]);
            }

            if ($payment->stripe_livemode === false) {
                continue;
            }

            try {
                $verification = $gateway->retrievePaymentIntentForTestModeVerification($payment->payment_intent_id);
                $intent = $verification['intent'];
            } catch (Throwable $exception) {
                report($exception);

                throw ValidationException::withMessages([
                    'is_test_order' => 'The Stripe payment could not be verified with live or test-mode credentials. Check STRIPE_TEST_SECRET and PaymentIntents Read permission.',
                ]);
            }

            if (data_get($intent, 'livemode') !== false) {
                throw ValidationException::withMessages([
                    'is_test_order' => 'This order contains live Stripe payments and cannot be marked as a test order.',
                ]);
            }

            if (! $this->intentMatchesPayment($intent, $payment)) {
                throw ValidationException::withMessages([
                    'is_test_order' => 'The Stripe PaymentIntent does not match the stored payment record.',
                ]);
            }

            $payment->update(['stripe_livemode' => false]);
        }
    }

    private function intentMatchesPayment(object $intent, Payment $payment): bool
    {
        if ((string) data_get($intent, 'id') !== (string) $payment->payment_intent_id) {
            return false;
        }

        if ((int) data_get($intent, 'amount') !== (int) $payment->amount_cents) {
            return false;
        }

        if (strtoupper((string) data_get($intent, 'currency')) !== strtoupper((string) $payment->currency)) {
            return false;
        }

        $metadataOrderId = data_get($intent, 'metadata.order_id');

        return $metadataOrderId === null || (string) $metadataOrderId === (string) $payment->order_id;
    }

    private function printData(Order $order): array
    {
        $order->loadMissing(['items', 'user', 'payments.refunds', 'activities.actor']);

        $branding = SiteBranding::getOrCreateSafely();
        $settings = app(SettingsService::class);
        $payment = $order->payments->first(fn ($payment) => $payment->paid_at !== null)
            ?? $order->payments->first();
        $refundedCents = $payment
            ? (int) $payment->refunds
                ->filter(fn ($refund) => $refund->status === RefundStatus::Succeeded)
                ->sum('amount_cents')
            : 0;

        return [
            'order' => $order,
            'logoUrl' => $branding && $branding->hasLogo()
                ? $branding->getLogoUrl()
                : asset('brand/sograte-wordmark.png'),
            'business' => [
                'name' => $settings->get('general.site_name', config('app.name', 'Sograte')),
                'email' => $settings->get('contact.email', ''),
                'phone' => $settings->get('contact.phone', ''),
                'address' => $settings->get('contact.address', ''),
            ],
            'payment' => $payment,
            'refundedCents' => $refundedCents,
            'orderNote' => $order->activities->first(fn ($activity) => filled($activity->note))?->note,
        ];
    }
}
