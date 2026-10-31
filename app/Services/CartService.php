<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'sograte_cart_token';

    public function resolveCart(): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $token = $this->guestToken();

        return Cart::firstOrCreate(['guest_token_hash' => $this->hashToken($token)]);
    }

    public function existingCart(): ?Cart
    {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::id())->first();
        }

        $token = $this->readGuestToken();

        return $token ? Cart::where('guest_token_hash', $this->hashToken($token))->first() : null;
    }

    public function addVariant(ProductVariant $variant, int $quantity, ?Product $expectedProduct = null): Cart
    {
        $this->assertQuantity($quantity);
        $this->assertVariantAvailable($variant);

        if ($expectedProduct && $variant->product_id !== $expectedProduct->id) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'The selected variant does not belong to this product.',
            ]);
        }

        $cart = $this->resolveCart();

        DB::transaction(function () use ($cart, $variant, $quantity) {
            $this->addVariantToCart($cart, $variant, $quantity);
        });

        return $cart->fresh();
    }

    public function addVariantToCart(Cart $cart, ProductVariant $variant, int $quantity, string $errorKey = 'quantity'): void
    {
        $this->assertQuantity($quantity, $errorKey);
        $this->assertVariantAvailable($variant, $errorKey);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->lockForUpdate()
            ->first();

        $newQuantity = ($item?->quantity ?? 0) + $quantity;

        if ($newQuantity > $this->maxQuantity()) {
            throw ValidationException::withMessages([
                $errorKey => 'The maximum quantity for this item is '.$this->maxQuantity().'.',
            ]);
        }

        if ($item) {
            $item->update(['quantity' => $newQuantity]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => $newQuantity,
            ]);
        }
    }

    public function updateItem(Cart $cart, CartItem $item, int $quantity): void
    {
        $this->assertItemOwnership($cart, $item);

        if ($quantity === 0) {
            $item->delete();

            return;
        }

        $this->assertQuantity($quantity);

        DB::transaction(function () use ($item, $quantity) {
            $item->update(['quantity' => $quantity]);
        });
    }

    public function removeItem(Cart $cart, CartItem $item): void
    {
        $this->assertItemOwnership($cart, $item);

        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function consumeForOrder(Order $order): void
    {
        if (! $order->cart_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            $cart = Cart::whereKey($order->cart_id)->lockForUpdate()->first();

            if (! $cart) {
                return;
            }

            foreach ($order->items()->get() as $item) {
                if (! $item->product_variant_id) {
                    continue;
                }

                $cartItem = CartItem::where('cart_id', $cart->id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $cartItem) {
                    continue;
                }

                $remaining = $cartItem->quantity - $item->quantity;

                if ($remaining <= 0) {
                    $cartItem->delete();
                } else {
                    $cartItem->update(['quantity' => $remaining]);
                }
            }
        });
    }

    public function subtotal(Cart $cart): int
    {
        $cart->loadMissing('items.variant.product');

        return (int) $cart->items->sum(function (CartItem $item) {
            return $item->isAvailable() ? $item->variant->price * $item->quantity : 0;
        });
    }

    public function subtotalFormatted(Cart $cart): string
    {
        return '$'.number_format($this->subtotal($cart) / 100, 2);
    }

    public function itemCount(Cart $cart): int
    {
        return (int) $cart->items()->sum('quantity');
    }

    public function assertCheckoutable(Cart $cart): void
    {
        $cart->loadMissing('items.variant.product');

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        foreach ($cart->items as $item) {
            if (! $item->isAvailable() || $item->quantity < 1 || $item->quantity > $this->maxQuantity()) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more items in your cart are unavailable. Please update your cart before checking out.',
                ]);
            }
        }
    }

    public function existingItemCount(): int
    {
        $cart = $this->existingCart();

        return $cart ? $this->itemCount($cart) : 0;
    }

    public function mergeGuestCartIntoUserCart(User $user): void
    {
        $token = $this->readGuestToken();

        if (! $token) {
            return;
        }

        $hash = $this->hashToken($token);

        DB::transaction(function () use ($user, $hash) {
            $guestCart = Cart::where('guest_token_hash', $hash)->lockForUpdate()->first();

            if (! $guestCart) {
                return;
            }

            $userCart = Cart::firstOrCreate(['user_id' => $user->id]);
            $userCart = Cart::whereKey($userCart->id)->lockForUpdate()->first();

            foreach ($guestCart->items()->with('variant.product')->get() as $guestItem) {
                $existing = CartItem::where('cart_id', $userCart->id)
                    ->where('product_variant_id', $guestItem->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                $quantity = min(
                    $this->maxQuantity(),
                    ($existing?->quantity ?? 0) + $guestItem->quantity
                );

                if ($existing) {
                    $existing->update(['quantity' => $quantity]);
                } else {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'product_variant_id' => $guestItem->product_variant_id,
                        'quantity' => $quantity,
                    ]);
                }
            }

            $guestCart->items()->delete();
            $guestCart->delete();
        });

        $this->forgetGuestToken();
    }

    public function maxQuantity(): int
    {
        return max(1, (int) config('cart.max_quantity', 99));
    }

    private function assertQuantity(int $quantity, string $errorKey = 'quantity'): void
    {
        if ($quantity < 1 || $quantity > $this->maxQuantity()) {
            throw ValidationException::withMessages([
                $errorKey => 'Quantity must be between 1 and '.$this->maxQuantity().'.',
            ]);
        }
    }

    private function assertVariantAvailable(ProductVariant $variant, string $errorKey = 'quantity'): void
    {
        $variant->loadMissing('product');

        if (! $variant->is_active || $variant->price <= 0 || ! $variant->product || ! $variant->product->is_active) {
            throw ValidationException::withMessages([
                $errorKey => 'This item is no longer available.',
            ]);
        }
    }

    private function assertItemOwnership(Cart $cart, CartItem $item): void
    {
        if ($item->cart_id !== $cart->id) {
            abort(404);
        }
    }

    private function guestToken(): string
    {
        $token = $this->readGuestToken();

        if (! $token) {
            $token = Str::random(64);
            session([self::SESSION_KEY => $token]);
            Cookie::queue(cookie(
                config('cart.guest_cookie', 'sograte_cart'),
                $token,
                (int) config('cart.guest_cookie_minutes', 43200),
                '/',
                null,
                app()->isProduction(),
                true,
                false,
                'lax'
            ));
        }

        return $token;
    }

    private function readGuestToken(): ?string
    {
        $token = request()->cookie(config('cart.guest_cookie', 'sograte_cart'));

        if (! is_string($token) || $token === '') {
            $token = session(self::SESSION_KEY);
        }

        return is_string($token) && strlen($token) >= 40 ? $token : null;
    }

    private function forgetGuestToken(): void
    {
        session()->forget(self::SESSION_KEY);
        Cookie::queue(Cookie::forget(config('cart.guest_cookie', 'sograte_cart')));
    }

    private function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, config('app.key'));
    }
}
