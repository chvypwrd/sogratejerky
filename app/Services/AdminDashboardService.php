<?php

namespace App\Services;

use App\Enums\FulfillmentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    public function report(CarbonInterface $month): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $trendStart = now()->subDays(29)->startOfDay();
        $trendEnd = now()->endOfDay();

        $grossToday = $this->paidSalesBetween($todayStart, $todayEnd);
        $grossMonth = $this->paidSalesBetween($monthStart, $monthEnd);
        $refundsMonth = $this->refundsBetween($monthStart, $monthEnd);
        $netMonth = $grossMonth - $refundsMonth;

        $grossByDay = $this->paidSalesByDay($trendStart, $trendEnd);
        $refundsByDay = $this->refundsByDay($trendStart, $trendEnd);
        $trend = $this->salesTrend($trendStart, $trendEnd, $grossByDay, $refundsByDay);

        return [
            'month' => $monthStart->format('Y-m'),
            'monthLabel' => $monthStart->format('F Y'),
            'ordersToday' => Order::regular()
                ->whereBetween('placed_at', [$todayStart, $todayEnd])
                ->count(),
            'ordersMonth' => Order::regular()
                ->whereBetween('placed_at', [$monthStart, $monthEnd])
                ->count(),
            'grossToday' => $grossToday,
            'grossTodayFormatted' => $this->formatCents($grossToday),
            'grossMonth' => $grossMonth,
            'grossMonthFormatted' => $this->formatCents($grossMonth),
            'refundsMonth' => $refundsMonth,
            'refundsMonthFormatted' => $this->formatCents($refundsMonth),
            'netMonth' => $netMonth,
            'netMonthFormatted' => $this->formatCents($netMonth),
            'awaitingFulfillment' => Order::regular()
                ->whereIn('payment_status', [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded])
                ->where('fulfillment_status', 'unfulfilled')
                ->where('order_status', '!=', 'cancelled')
                ->count(),
            'unreadMessages' => ContactMessage::newMessages()->count(),
            'salesTrend' => $trend,
            'salesTrendLabels' => $trend->pluck('label')->all(),
            'salesTrendGross' => $trend->pluck('gross')->all(),
            'salesTrendRefunds' => $trend->pluck('refunds')->all(),
            'salesTrendNet' => $trend->pluck('net')->all(),
            'fulfillmentMethods' => $this->fulfillmentMethodBreakdown($monthStart, $monthEnd),
            'recentOrders' => Order::regular()
                ->withCount('items')
                ->orderByDesc('placed_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'bestSellers' => $this->bestSellers($monthStart, $monthEnd),
        ];
    }

    private function paidSalesBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) Payment::query()
            ->whereHas('order', fn ($query) => $query->regular())
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount_cents');
    }

    private function refundsBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) PaymentRefund::query()
            ->whereHas('order', fn ($query) => $query->regular())
            ->where('status', RefundStatus::Succeeded->value)
            ->whereBetween('refunded_at', [$from, $to])
            ->sum('amount_cents');
    }

    private function paidSalesByDay(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Payment::query()
            ->whereHas('order', fn ($query) => $query->regular())
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('DATE(paid_at) as report_date, SUM(amount_cents) as total')
            ->groupBy('report_date')
            ->pluck('total', 'report_date');
    }

    private function refundsByDay(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return PaymentRefund::query()
            ->whereHas('order', fn ($query) => $query->regular())
            ->where('status', RefundStatus::Succeeded->value)
            ->whereBetween('refunded_at', [$from, $to])
            ->selectRaw('DATE(refunded_at) as report_date, SUM(amount_cents) as total')
            ->groupBy('report_date')
            ->pluck('total', 'report_date');
    }

    private function salesTrend(CarbonInterface $from, CarbonInterface $to, Collection $grossByDay, Collection $refundsByDay): Collection
    {
        $days = collect();
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m-d');
            $gross = (int) ($grossByDay[$key] ?? 0);
            $refunds = (int) ($refundsByDay[$key] ?? 0);

            $days->push([
                'label' => $cursor->format('M j'),
                'gross' => $gross,
                'refunds' => $refunds,
                'net' => $gross - $refunds,
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    private function fulfillmentMethodBreakdown(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $counts = Order::regular()
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('fulfillment_method, COUNT(*) as total')
            ->groupBy('fulfillment_method')
            ->pluck('total', 'fulfillment_method');

        return collect(FulfillmentMethod::cases())
            ->map(fn (FulfillmentMethod $method) => [
                'label' => $method->label(),
                'value' => (int) ($counts[$method->value] ?? 0),
            ])
            ->values();
    }

    private function bestSellers(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.payment_status', [
                PaymentStatus::Paid->value,
                PaymentStatus::PartiallyRefunded->value,
                PaymentStatus::Refunded->value,
            ])
            ->where('orders.order_status', '!=', 'cancelled')
            ->where('orders.is_test_order', false)
            ->whereBetween('orders.placed_at', [$from, $to])
            ->selectRaw('order_items.product_name, order_items.variant_name, order_items.sku, SUM(order_items.quantity) as units, SUM(order_items.line_total_cents) as revenue_cents')
            ->groupBy('order_items.product_name', 'order_items.variant_name', 'order_items.sku')
            ->orderByDesc('units')
            ->limit(5)
            ->get();
    }

    private function formatCents(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
