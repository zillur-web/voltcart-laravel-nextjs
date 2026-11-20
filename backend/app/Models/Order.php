<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = [
        'pending',
        'paid',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
    ];

    public const TRANSITIONS = [
        'pending' => ['cancelled'],
        'paid' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'number',
        'user_id',
        'status',
        'email',
        'shipping_name',
        'phone',
        'line1',
        'line2',
        'city',
        'state',
        'postal_code',
        'country',
        'subtotal',
        'discount',
        'shipping',
        'total',
        'coupon_code',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function holdsStock(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'shipped', 'delivered'], true);
    }

    public function assignNumber(): void
    {
        $this->number = sprintf('ORD-%s-%06d', $this->created_at->format('Y'), $this->id);
        $this->save();
    }

    public function toPublicArray(bool $detail = false): array
    {
        $data = [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'email' => $this->email,
            'subtotal' => (string) $this->subtotal,
            'discount' => (string) $this->discount,
            'shipping' => (string) $this->shipping,
            'total' => (string) $this->total,
            'coupon_code' => $this->coupon_code,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->relationLoaded('items')
                ? $this->items->map(fn (OrderItem $item) => $item->toPublicArray())->values()
                : [],
        ];

        if ($detail) {
            $data['shipping_address'] = [
                'name' => $this->shipping_name,
                'phone' => $this->phone,
                'line1' => $this->line1,
                'line2' => $this->line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ];
            $data['customer'] = $this->relationLoaded('user') && $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : [
                'name' => $this->shipping_name,
                'email' => $this->email,
            ];
            $data['payments'] = $this->relationLoaded('payments')
                ? $this->payments->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'amount' => (string) $payment->amount,
                    'currency' => $payment->currency,
                    'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                ])->values()
                : [];
            $data['allowed_transitions'] = self::TRANSITIONS[$this->status] ?? [];
        }

        return $data;
    }
}
