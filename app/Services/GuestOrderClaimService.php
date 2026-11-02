<?php

namespace App\Services;

use App\Models\Order;

class GuestOrderClaimService
{
    private const SESSION_KEY = 'guest_claim_order_id';

    public function remember(Order $order): void
    {
        $order->loadMissing('payments');

        if ($order->user_id !== null || ! $this->hasSuccessfulPayment($order)) {
            return;
        }

        $sessionToken = (string) session('checkout_token');

        if ($sessionToken === '' || ! hash_equals($sessionToken, (string) $order->checkout_token)) {
            return;
        }

        session([self::SESSION_KEY => (int) $order->id]);
    }

    public function allows(Order $order): bool
    {
        $order->loadMissing('payments');

        return $order->user_id === null
            && (int) session(self::SESSION_KEY) === (int) $order->id
            && $this->hasSuccessfulPayment($order);
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function hasSuccessfulPayment(Order $order): bool
    {
        return $order->payments->contains(fn ($payment) => $payment->paid_at !== null);
    }
}
