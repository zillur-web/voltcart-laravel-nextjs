<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::query()->updateOrCreate(['code' => 'WELCOME20'], [
            'type' => 'percent',
            'value' => 20,
            'min_order_amount' => 50,
            'usage_limit' => 100,
            'used_count' => 4,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(6),
            'is_active' => true,
        ]);

        Coupon::query()->updateOrCreate(['code' => 'SAVE10'], [
            'type' => 'fixed',
            'value' => 10,
            'min_order_amount' => 40,
            'usage_limit' => 50,
            'used_count' => 2,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addMonths(3),
            'is_active' => true,
        ]);

        Coupon::query()->updateOrCreate(['code' => 'EXPIRED10'], [
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 0,
            'usage_limit' => 20,
            'used_count' => 1,
            'starts_at' => now()->subMonths(3),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);
    }
}
