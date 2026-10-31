<?php

return [
    'max_quantity' => (int) env('CART_MAX_QUANTITY', 99),
    'guest_cookie' => 'sograte_cart',
    'guest_cookie_minutes' => (int) env('CART_GUEST_COOKIE_MINUTES', 60 * 24 * 30),
];
