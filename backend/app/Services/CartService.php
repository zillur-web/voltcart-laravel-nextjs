<?php

namespace App\Services;

use App\Exceptions\CommerceException;
use App\Exceptions\CouponException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use App\Support\Pricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public function __construct(private readonly CouponService $coupons)
    {
    }

    public function resolve(?User $user, ?string $guestToken): array
    {
        if ($user && ! $user->is_staff) {
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

            return [$cart, null];
        }

        if (! $guestToken || ! Str::isUuid($guestToken)) {
            $guestToken = (string) Str::uuid();
        }

        $cart = Cart::query()->firstOrCreate(['guest_token' => $guestToken]);

        return [$cart, $guestToken];
    }

    public function add(Cart $cart, Product $product, int $quantity, ?string $size = null): void
    {
        if (! $product->is_active) {
            throw new CommerceException('This product is not available.');
        }

        if ($product->stock < 1) {
            throw new CommerceException('This product is out of stock.');
        }

        if ($size && $product->sizes && ! in_array($size, $product->sizes, true)) {
            throw new CommerceException('Choose a valid size.');
        }

        $item = CartItem::query()->firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);
        $item->size = $size;

        $next = ($item->exists ? $item->quantity : 0) + $quantity;
        if ($next > $product->stock) {
            throw new CommerceException('Only '.$product->stock.' left in stock.');
        }

        $item->quantity = $next;
        $item->save();
    }

    public function updateQuantity(Cart $cart, CartItem $item, int $quantity): void
    {
        $this->assertItem($cart, $item);
        $product = $item->product;

        if (! $product || ! $product->is_active) {
            throw new CommerceException('This product is not available.');
        }

        if ($quantity > $product->stock) {
            throw new CommerceException('Only '.$product->stock.' left in stock.');
        }

        $item->quantity = $quantity;
        $item->save();
    }

    public function remove(Cart $cart, CartItem $item): void
    {
        $this->assertItem($cart, $item);
        $item->delete();
    }

    public function applyCoupon(Cart $cart, string $code): void
    {
        $summary = $this->totals($cart);
        $coupon = $this->coupons->findValid($code, $summary['subtotal_cents']);
        $cart->coupon_id = $coupon->id;
        $cart->save();
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->coupon_id = null;
        $cart->save();
    }

    public function merge(User $user, ?string $guestToken): Cart
    {
        $userCart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        if (! $guestToken || ! Str::isUuid($guestToken)) {
            return $userCart;
        }

        $guest = Cart::query()->where('guest_token', $guestToken)->with('items')->first();
        if (! $guest || $guest->id === $userCart->id) {
            return $userCart;
        }

        DB::transaction(function () use ($userCart, $guest) {
            foreach ($guest->items as $item) {
                $existing = $userCart->items()->where('product_id', $item->product_id)->first();
                $product = Product::query()->find($item->product_id);
                $stock = $product?->stock ?? 0;

                if ($existing) {
                    $existing->quantity = min($stock, $existing->quantity + $item->quantity);
                    if ($existing->quantity < 1) {
                        $existing->delete();
                    } else {
                        $existing->save();
                    }
                } elseif ($stock > 0 && $product?->is_active) {
                    $item->cart_id = $userCart->id;
                    $item->quantity = min($stock, $item->quantity);
                    $item->save();
                }
            }

            if (! $userCart->coupon_id && $guest->coupon_id) {
                $userCart->coupon_id = $guest->coupon_id;
                $userCart->save();
            }

            $guest->items()->delete();
            $guest->delete();
        });

        return $userCart->fresh();
    }

    public function totals(Cart $cart): array
    {
        $cart->loadMissing(['items.product', 'coupon']);
        $subtotal = 0;

        foreach ($cart->items as $item) {
            if (! $item->product) {
                continue;
            }
            $subtotal += $item->product->effectivePriceCents() * $item->quantity;
        }

        $discount = 0;
        $coupon = null;
        $couponError = null;

        if ($cart->coupon) {
            try {
                $discount = $this->coupons->discountCents($cart->coupon, $subtotal);
                $coupon = $cart->coupon;
            } catch (CouponException $exception) {
                $couponError = $exception->getMessage();
            }
        }

        return [
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'shipping_cents' => Pricing::shippingCents($subtotal),
            'total_cents' => Pricing::totalCents($subtotal, $discount),
            'coupon' => $coupon,
            'coupon_error' => $couponError,
        ];
    }

    public function present(Cart $cart, ?string $token = null): array
    {
        $cart->load(['items.product.images', 'items.product.category', 'coupon']);
        $totals = $this->totals($cart);
        $lines = [];

        foreach ($cart->items as $item) {
            if (! $item->product) {
                continue;
            }
            $unit = $item->product->effectivePriceCents();
            $line = $unit * $item->quantity;
            $lines[] = [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'size' => $item->size,
                'unit_price' => Money::fromCents($unit),
                'line_total' => Money::fromCents($line),
                'product' => $item->product->toCatalogArray(),
            ];
        }

        return [
            'cart_token' => $token ?? $cart->guest_token,
            'items' => $lines,
            'summary' => [
                'subtotal' => Money::fromCents($totals['subtotal_cents']),
                'discount' => Money::fromCents($totals['discount_cents']),
                'shipping' => Money::fromCents($totals['shipping_cents']),
                'total' => Money::fromCents($totals['total_cents']),
                'currency' => config('services.stripe.currency', 'usd'),
            ],
            'coupon' => $totals['coupon'] ? [
                'code' => $totals['coupon']->code,
                'type' => $totals['coupon']->type,
                'value' => (string) $totals['coupon']->value,
            ] : null,
            'coupon_error' => $totals['coupon_error'],
        ];
    }

    private function assertItem(Cart $cart, CartItem $item): void
    {
        if ($item->cart_id !== $cart->id) {
            throw new CommerceException('Cart item not found.');
        }
    }
}
