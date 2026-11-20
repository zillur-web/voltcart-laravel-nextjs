<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use App\Support\Pricing;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::query()->where('email', 'customer@shopco.test')->firstOrFail();

        if (Order::query()->where('user_id', $customer->id)->exists()) {
            return;
        }

        $samples = [
            ['paid', ['SC-0001'], [1]],
            ['processing', ['SC-0005'], [1]],
            ['shipped', ['SC-0014', 'SC-0021'], [1, 1]],
            ['delivered', ['SC-0009'], [2]],
            ['pending', ['SC-0016'], [1]],
            ['cancelled', ['SC-0019'], [1]],
        ];

        foreach ($samples as [$status, $skus, $quantities]) {
            $lines = [];
            $subtotal = 0;

            foreach ($skus as $index => $sku) {
                $product = Product::query()->where('sku', $sku)->firstOrFail();
                $unit = $product->effectivePriceCents();
                $qty = $quantities[$index];
                $line = $unit * $qty;
                $subtotal += $line;
                $lines[] = compact('product', 'unit', 'qty', 'line');
            }

            $shipping = Pricing::shippingCents($subtotal);
            $order = Order::query()->create([
                'user_id' => $customer->id,
                'status' => $status,
                'email' => $customer->email,
                'shipping_name' => $customer->name,
                'phone' => '555-0148',
                'line1' => '18 Mercer Street',
                'line2' => 'Apt 4',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10013',
                'country' => 'United States',
                'subtotal' => Money::fromCents($subtotal),
                'discount' => '0.00',
                'shipping' => Money::fromCents($shipping),
                'total' => Money::fromCents($subtotal + $shipping),
                'paid_at' => in_array($status, ['paid', 'processing', 'shipped', 'delivered'], true) ? now()->subDays(2) : null,
            ]);
            $order->assignNumber();

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'name' => $line['product']->name,
                    'sku' => $line['product']->sku,
                    'unit_price' => Money::fromCents($line['unit']),
                    'quantity' => $line['qty'],
                    'line_total' => Money::fromCents($line['line']),
                ]);

                if (in_array($status, ['paid', 'processing', 'shipped', 'delivered'], true)) {
                    $line['product']->decrement('stock', $line['qty']);
                }
            }

            if ($status !== 'pending') {
                $order->payments()->create([
                    'stripe_payment_intent_id' => null,
                    'amount' => $order->total,
                    'currency' => 'usd',
                    'status' => $status === 'cancelled' ? 'canceled' : 'succeeded',
                ]);
            }
        }
    }
}
