<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Exceptions\CouponException;
use App\Exceptions\OversellException;
use App\Models\ActivityLog;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrderService
{
    public function __construct(private readonly CouponService $coupons)
    {
    }

    public function markPaid(Order $order): Order
    {
        $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

        if (in_array($locked->status, ['paid', 'processing', 'shipped', 'delivered'], true)) {
            return $locked->load('items', 'payments');
        }

        if ($locked->status !== 'pending') {
            throw new CheckoutException('This order can no longer be paid.');
        }

        $this->takeStock($locked);

        if ($locked->coupon_code) {
            $coupon = Coupon::query()->where('code', $locked->coupon_code)->lockForUpdate()->first();
            if (! $coupon) {
                throw new CouponException('This coupon code is not valid.');
            }
            $this->coupons->assertUsable($coupon, (int) round(((float) $locked->subtotal) * 100));
            $coupon->increment('used_count');
        }

        $locked->status = 'paid';
        $locked->paid_at = now();
        $locked->save();
        $locked->payments()->whereNotIn('status', ['refunded', 'refund_failed'])->update(['status' => 'succeeded']);

        Cart::query()->where('user_id', $locked->user_id)->delete();

        ActivityLog::write($locked->user_id, 'order.paid', $locked, ['number' => $locked->number]);

        return $locked->fresh(['items', 'payments', 'user']);
    }

    public function updateStatus(Order $order, string $status, ?int $actorId): Order
    {
        if (! in_array($status, Order::STATUSES, true)) {
            throw new CheckoutException('Unknown order status.');
        }

        $updated = DB::transaction(function () use ($order, $status) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $allowed = Order::TRANSITIONS[$locked->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                throw new CheckoutException('This status change is not allowed.');
            }

            if ($status === 'cancelled' && $locked->holdsStock()) {
                $this->restoreStock($locked);
            }

            $from = $locked->status;
            $locked->status = $status;
            $locked->save();

            return [$locked->fresh(['items', 'payments', 'user']), $from];
        });

        [$fresh, $from] = $updated;
        ActivityLog::write($actorId, 'order.status', $fresh, ['from' => $from, 'to' => $status]);
        $this->notify($fresh);

        return $fresh;
    }

    public function notify(Order $order): void
    {
        if (! $order->email) {
            return;
        }

        try {
            Notification::route('mail', $order->email)->notify(new OrderStatusNotification($order));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function takeStock(Order $order): void
    {
        $lines = [];
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();

            if (! $product || ! $product->is_active || $product->stock < $item->quantity) {
                $available = $product?->stock ?? 0;
                throw new OversellException(
                    'Not enough stock for '.$item->name.' (requested '.$item->quantity.', available '.$available.'). The order was not completed.'
                );
            }

            $lines[] = [$product, $item->quantity];
        }

        foreach ($lines as [$product, $quantity]) {
            $product->decrement('stock', $quantity);
        }
    }

    private function restoreStock(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }
            $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();
            $product?->increment('stock', $item->quantity);
        }
    }
}
