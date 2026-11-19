<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingCarrier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Http\Requests\Admin\CompleteFulfillmentRequest;
use App\Http\Requests\Admin\FulfillmentNoteRequest;
use App\Http\Requests\Admin\RefundOrderRequest;
use App\Http\Requests\Admin\ShipOrderRequest;
use App\Models\Order;
use App\Models\SiteBranding;
use App\Services\OrderFulfillmentService;
use App\Services\PaymentRefundService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
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
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $query = Order::regular()->withCount('items');

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

        $order->load(['items', 'activities.actor', 'user', 'payments.refunds.requester', 'media']);

        return view('admin.orders.show', [
            'order' => $order,
            'carriers' => ShippingCarrier::cases(),
        ]);
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

    public function proof(Order $order, Media $media, Request $request): Response
    {
        $this->authorize('view', $order);

        abort_unless(
            $media->model_type === $order->getMorphClass()
            && (int) $media->model_id === (int) $order->id
            && in_array($media->collection_name, ['delivery_proof', 'pickup_proof'], true),
            404,
        );

        try {
            $path = $request->boolean('preview') && $media->hasGeneratedConversion('thumb')
                ? $media->getPath('thumb')
                : $media->getPath();
        } catch (Throwable) {
            abort(404);
        }

        abort_unless(is_file($path), 404);

        return response()->file($path);
    }

    public function markReadyForPickup(FulfillmentNoteRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        $this->fulfillmentService->markReadyForPickup($order, $request->user(), $request->validated('note'));

        return back()->with('status', 'Order marked ready for pickup.');
    }

    public function markPickedUp(CompleteFulfillmentRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        if ($order->fulfillment_status === FulfillmentStatus::PickedUp) {
            return back()->with('status', 'Order is already marked picked up.');
        }

        $media = $this->attachProofPhoto($request, $order, 'pickup_proof');

        try {
            $this->fulfillmentService->markPickedUp($order, $request->user(), $request->validated('note'));
        } catch (Throwable $exception) {
            $media?->delete();

            throw $exception;
        }

        return back()->with('status', 'Order marked picked up.');
    }

    public function markOutForDelivery(FulfillmentNoteRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        $this->fulfillmentService->markOutForDelivery($order, $request->user(), $request->validated('note'));

        return back()->with('status', 'Order marked out for delivery.');
    }

    public function markDelivered(CompleteFulfillmentRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('fulfill', $order);

        if ($order->fulfillment_status === FulfillmentStatus::Delivered) {
            return back()->with('status', 'Order is already marked delivered.');
        }

        $media = $this->attachProofPhoto($request, $order, 'delivery_proof');

        try {
            $this->fulfillmentService->markDelivered($order, $request->user(), $request->validated('note'));
        } catch (Throwable $exception) {
            $media?->delete();

            throw $exception;
        }

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

    private function attachProofPhoto(CompleteFulfillmentRequest $request, Order $order, string $collection): ?Media
    {
        if (! $request->hasFile('proof_photo')) {
            return null;
        }

        $media = null;

        try {
            $extension = $request->file('proof_photo')->guessExtension() ?: 'jpg';

            $media = $order->addMediaFromRequest('proof_photo')
                ->usingFileName(Str::uuid().'.'.$extension)
                ->withCustomProperties([
                    'fulfillment_method' => $order->fulfillment_method->value,
                    'note' => $request->validated('note'),
                    'uploaded_by' => (string) $request->user()->id,
                    'uploaded_by_name' => $request->user()->name,
                    'captured_at' => now()->toIso8601String(),
                ])
                ->toMediaCollection($collection, 'local');

            Image::load($media->getPath())->save($media->getPath());

            return $media;
        } catch (Throwable $exception) {
            $media?->delete();

            throw ValidationException::withMessages([
                'proof_photo' => 'The proof photo could not be processed. Please choose a JPEG, PNG, or WebP image.',
            ]);
        }
    }

    private function printData(Order $order): array
    {
        $order->loadMissing(['items', 'user', 'payments.refunds', 'activities.actor']);

        $branding = SiteBranding::getOrCreateSafely();
        $settings = app(SettingsService::class);
        $payment = $order->payments->first(fn ($payment) => $payment->paid_at !== null)
            ?? $order->payments->first();
        $refundedCents = $payment?->refunded_cents ?? 0;

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
