<?php

namespace App\Services;

use App\Models\Product;

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

    public function productIsPurchasable(Product $product): bool
    {
        return $this->enabled() && $product->isAvailable();
    }

    public function ensureEnabled(): void
    {
        abort_unless($this->enabled(), 404);
    }
}
