<?php

namespace App\Services;

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Support\Money;

class CouponService
{
    public function findValid(string $code, int $subtotalCents): Coupon
    {
        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();

        if (! $coupon || ! $coupon->is_active) {
            throw new CouponException('This coupon code is not valid.');
        }

        $this->assertUsable($coupon, $subtotalCents);

        return $coupon;
    }

    public function assertUsable(Coupon $coupon, int $subtotalCents): void
    {
        if (! $coupon->is_active) {
            throw new CouponException('This coupon code is not valid.');
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new CouponException('This coupon code is not valid.');
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            throw new CouponException('This coupon has expired.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new CouponException('This coupon has reached its usage limit.');
        }

        $minimum = Money::toCents($coupon->min_order_amount);
        if ($subtotalCents < $minimum) {
            throw new CouponException('This coupon requires a minimum order of $'.Money::fromCents($minimum).'.');
        }
    }

    public function discountCents(Coupon $coupon, int $subtotalCents): int
    {
        $this->assertUsable($coupon, $subtotalCents);

        if ($coupon->type === 'percent') {
            $discount = (int) round($subtotalCents * ((float) $coupon->value) / 100);
        } else {
            $discount = Money::toCents($coupon->value);
        }

        return min(max($discount, 0), $subtotalCents);
    }
}
