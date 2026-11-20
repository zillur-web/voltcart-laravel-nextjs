<?php

namespace App\Support;

class Pricing
{
    public const FREE_SHIPPING_OVER = 20000;

    public const SHIPPING_CENTS = 1500;

    public static function shippingCents(int $subtotalCents): int
    {
        if ($subtotalCents <= 0 || $subtotalCents >= self::FREE_SHIPPING_OVER) {
            return 0;
        }

        return self::SHIPPING_CENTS;
    }

    public static function totalCents(int $subtotalCents, int $discountCents): int
    {
        return max(0, $subtotalCents - $discountCents + self::shippingCents($subtotalCents));
    }
}
