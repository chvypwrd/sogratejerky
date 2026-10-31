<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingCarrier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Http\Requests\Admin\FulfillmentNoteRequest;
use App\Http\Requests\Admin\RefundOrderRequest;
use App\Http\Requests\Admin\ShipOrderRequest;
use App\Models\Order;
use App\Services\OrderFulfillmentService;
use App\Services\PaymentRefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $query = Order::query()->withCount('items');

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
            'awaiting_payment' => Order::where('payment_status', PaymentStatus::Pending->value)
                ->where('order_status', '!=', OrderStatus::Cancelled->value)
                ->count(),
            'paid_awaiting_fulfillment' => Order::where('payment_status', PaymentStatus::Paid->value)
                ->where('fulfillment_status', FulfillmentStatus::Unfulfilled->value)
                ->where('order_status', '!=', OrderStatus::Cancelled->value)
                ->count(),
            'ready_for_pickup' => Order::where('fulfillment_status', FulfillmentStatus::ReadyForPickup->value)->count(),
            'out_for_delivery' => Order::where('fulfillment_status', FulfillmentStatus::OutForDelivery->value)->count(),
            'recently_fulfilled' => Order::whereNotNull('fulfilled_at')
                ->where('fulfilled_at', '>=', now()->subDays(7))
                ->count(),
        ];

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

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.show', [
            'order' => $order->load(['items', 'activities.actor', 'user', 'payments.refunds.requester']),
            'carriers' => ShippingCarrier::cases(),
        ]);
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
}
