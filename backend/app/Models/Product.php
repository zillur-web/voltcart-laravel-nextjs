<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'sku',
        'stock',
        'color',
        'sizes',
        'rating',
        'reviews_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'sizes' => 'array',
            'rating' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function effectivePriceCents(): int
    {
        $price = Money::toCents($this->price);

        if ($this->discount_price !== null) {
            $discount = Money::toCents($this->discount_price);
            if ($discount > 0 && $discount < $price) {
                return $discount;
            }
        }

        return $price;
    }

    public function toCatalogArray(bool $detail = false): array
    {
        $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (string) $this->price,
            'discount_price' => $this->discount_price !== null ? (string) $this->discount_price : null,
            'effective_price' => Money::fromCents($this->effectivePriceCents()),
            'rating' => (float) $this->rating,
            'reviews_count' => $this->reviews_count,
            'stock' => $this->stock,
            'in_stock' => $this->is_active && $this->stock > 0,
            'color' => $this->color,
            'sizes' => $this->sizes ?? [],
            'is_active' => $this->is_active,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'image' => $primary?->url(),
            'images' => $this->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $image->url(),
                'is_primary' => $image->is_primary,
            ])->values(),
        ];

        if ($detail) {
            $data['description'] = $this->description;
            $data['sku'] = $this->sku;
        }

        return $data;
    }

    public function toAdminArray(): array
    {
        return [
            ...$this->toCatalogArray(true),
            'category_id' => $this->category_id,
        ];
    }
}
