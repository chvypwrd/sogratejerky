<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentAmountsTest extends TestCase
{
    use RefreshDatabase;

    private function paymentFor(Order $order, PaymentStatus $status, ?int $amountCents = 2400): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_test_'.Str::uuid(),
            'amount_cents' => $amountCents,
            'currency' => 'USD',
            'status' => $status,
            'paid_at' => null,
        ]);
    }

    public function test_non_captured_payments_have_no_captured_or_refundable_balance(): void
    {
        $order = Order::factory()->create();

        foreach ([PaymentStatus::Pending, PaymentStatus::Processing, PaymentStatus::Failed] as $status) {
            $payment = $this->paymentFor($order, $status);

            if ($status === PaymentStatus::Failed) {
                $payment->update(['canceled_at' => now()]);
            }

            $this->assertFalse($payment->isCaptured());
            $this->assertSame(0, $payment->captured_cents);
            $this->assertSame(0, $payment->refunded_cents);
            $this->assertSame(0, $payment->refundable_cents);
            $this->assertSame('$0.00', $payment->captured_formatted);
            $this->assertSame('$24.00', $payment->amount_formatted);
        }
    }

    public function test_captured_amount_and_refunds_use_confirmed_payment_state(): void
    {
        $order = Order::factory()->create();
        $payment = $this->paymentFor($order, PaymentStatus::Paid);
        $payment->update(['paid_at' => now()]);

        $this->assertTrue($payment->isCaptured());
        $this->assertSame(2400, $payment->captured_cents);
        $this->assertSame('$24.00', $payment->captured_formatted);
        $this->assertSame(0, $payment->refunded_cents);
        $this->assertSame(2400, $payment->refundable_cents);

        $pending = PaymentRefund::create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'request_key' => 'pending-refund-key',
            'amount_cents' => 1000,
            'currency' => 'USD',
            'status' => RefundStatus::Pending,
        ]);

        $this->assertSame(0, $payment->refunded_cents);
        $this->assertSame(1400, $payment->refundable_cents);

        $pending->update([
            'status' => RefundStatus::Succeeded,
            'refunded_at' => now(),
        ]);

        $this->assertSame(1000, $payment->refunded_cents);
        $this->assertSame(1400, $payment->refundable_cents);

        PaymentRefund::create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'request_key' => 'second-refund-key',
            'amount_cents' => 1400,
            'currency' => 'USD',
            'status' => RefundStatus::Succeeded,
        ]);

        $payment->update(['status' => PaymentStatus::Refunded]);

        $this->assertSame(2400, $payment->captured_cents);
        $this->assertSame(2400, $payment->refunded_cents);
        $this->assertSame(0, $payment->refundable_cents);
    }
}
