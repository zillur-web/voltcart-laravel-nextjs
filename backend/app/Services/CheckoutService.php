<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Exceptions\CommerceException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use App\Support\Text;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;

class CheckoutService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly OrderService $orders,
    ) {
    }

    public function start(User $user, array $shipping): array
    {
        $cart = $user->id
            ? \App\Models\Cart::query()->where('user_id', $user->id)->first()
            : null;

        if (! $cart || $cart->items()->doesntExist()) {
            throw new CheckoutException('Your cart is empty.');
        }

        $cart->load(['items.product', 'coupon']);
        $totals = $this->carts->totals($cart);

        if ($cart->items->isEmpty()) {
            throw new CheckoutException('Your cart is empty.');
        }

        foreach ($cart->items as $item) {
            $product = $item->product;
            if (! $product || ! $product->is_active || $product->stock < $item->quantity) {
                throw new CheckoutException('One or more items are out of stock. Update your cart and try again.');
            }
        }

        $order = DB::transaction(function () use ($user, $shipping, $cart, $totals) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'email' => $user->email,
                'shipping_name' => Text::clean($shipping['name']),
                'phone' => Text::clean($shipping['phone']),
                'line1' => Text::clean($shipping['line1']),
                'line2' => Text::clean($shipping['line2'] ?? null),
                'city' => Text::clean($shipping['city']),
                'state' => Text::clean($shipping['state']),
                'postal_code' => Text::clean($shipping['postal_code']),
                'country' => Text::clean($shipping['country']),
                'subtotal' => Money::fromCents($totals['subtotal_cents']),
                'discount' => Money::fromCents($totals['discount_cents']),
                'shipping' => Money::fromCents($totals['shipping_cents']),
                'total' => Money::fromCents($totals['total_cents']),
                'coupon_code' => $totals['coupon']?->code,
            ]);
            $order->assignNumber();

            foreach ($cart->items as $item) {
                $unit = $item->product->effectivePriceCents();
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'unit_price' => Money::fromCents($unit),
                    'quantity' => $item->quantity,
                    'line_total' => Money::fromCents($unit * $item->quantity),
                ]);
            }

            return $order->fresh('items');
        });

        if ($totals['total_cents'] > 0 && ! config('services.stripe.secret')) {
            throw new CheckoutException('Stripe is not configured. Add STRIPE_SECRET to the API environment.');
        }

        if ($totals['total_cents'] === 0) {
            $paid = DB::transaction(fn () => $this->orders->markPaid($order));
            $this->orders->notify($paid);

            return [
                'order' => $paid->toPublicArray(true),
                'client_secret' => null,
                'paid' => true,
            ];
        }

        $intent = $this->stripe()->paymentIntents->create([
            'amount' => $totals['total_cents'],
            'currency' => config('services.stripe.currency', 'usd'),
            'payment_method_types' => ['card'],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->number,
            ],
        ], [
            'idempotency_key' => 'order-'.$order->id,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => $intent->id,
            'amount' => $order->total,
            'currency' => $intent->currency,
            'status' => $intent->status,
        ]);

        $order->load('payments');

        return [
            'order' => $order->toPublicArray(true),
            'client_secret' => $intent->client_secret,
            'paid' => false,
        ];
    }

    public function confirm(User $user, string $paymentIntentId): Order
    {
        $payment = Payment::query()->where('stripe_payment_intent_id', $paymentIntentId)->first();
        $order = $payment?->order;

        if (! $order || $order->user_id !== $user->id) {
            throw new CheckoutException('Order not found.');
        }

        try {
            $intent = $this->stripe()->paymentIntents->retrieve($paymentIntentId);
        } catch (\Throwable $exception) {
            report($exception);
            throw new CheckoutException('Unable to verify the payment.');
        }

        if ((string) ($intent->metadata['order_id'] ?? '') !== (string) $order->id) {
            throw new CheckoutException('Payment does not match this order.');
        }

        if ((int) $intent->amount !== Money::toCents($order->total) || $intent->currency !== config('services.stripe.currency', 'usd')) {
            throw new CheckoutException('Payment amount does not match the order total.');
        }

        if ($intent->status !== 'succeeded') {
            $payment->update(['status' => $intent->status]);
            throw new CheckoutException('Payment was not completed. The order has not been marked as paid.');
        }

        $wasPending = $order->status === 'pending';

        try {
            $paid = DB::transaction(fn () => $this->orders->markPaid($order));
        } catch (CommerceException $exception) {
            $this->refund($payment, $paymentIntentId);
            $order->refresh();
            if ($order->status === 'pending') {
                $order->update(['status' => 'cancelled']);
            }
            $this->orders->notify($order->fresh());
            throw new CheckoutException($exception->getMessage().' The payment was refunded.');
        }

        if ($wasPending) {
            $this->orders->notify($paid);
        }

        return $paid;
    }

    private function refund(Payment $payment, string $paymentIntentId): void
    {
        try {
            $this->stripe()->refunds->create(['payment_intent' => $paymentIntentId]);
            $payment->update(['status' => 'refunded']);
        } catch (\Throwable $exception) {
            report($exception);
            $payment->update(['status' => 'refund_failed']);
        }
    }

    private function stripe(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw new CheckoutException('Stripe is not configured. Add STRIPE_SECRET to the API environment.');
        }

        return new StripeClient($secret);
    }
}
