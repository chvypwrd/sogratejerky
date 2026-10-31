<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commerce Configuration
    |--------------------------------------------------------------------------
    |
    | CHECKOUT_ENABLED is the emergency off switch for checkout. Production
    | checkout remains disabled unless the launch gate is explicitly opened.
    |
    */

    'checkout_enabled' => (bool) env('CHECKOUT_ENABLED', true),

    'allow_production_checkout' => (bool) env('ALLOW_PRODUCTION_CHECKOUT', false),

];
