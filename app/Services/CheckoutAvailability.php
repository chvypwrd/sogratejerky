<?php

namespace App\Services;

class CheckoutAvailability
{
    public function enabled(): bool
    {
        if (! config('commerce.checkout_enabled', true)) {
            return false;
        }

        if (app()->isProduction() && ! config('commerce.allow_production_checkout', false)) {
            return false;
        }

        if (app()->isProduction() && blank(config('services.stripe.webhook_secret'))) {
            return false;
        }

        return true;
    }

    public function ensureEnabled(): void
    {
        abort_unless($this->enabled(), 404);
    }
}
