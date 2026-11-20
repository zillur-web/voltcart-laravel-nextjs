<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'image',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function listingIds(): array
    {
        if ($this->parent_id === null) {
            return static::query()
                ->where('id', $this->id)
                ->orWhere('parent_id', $this->id)
                ->pluck('id')
                ->all();
        }

        return [$this->id];
    }

    public function toPublicArray(bool $withChildren = false): array
    {
        $data = [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($withChildren) {
            $data['children'] = $this->children->map(fn (Category $child) => $child->toPublicArray())->values();
        }

        return $data;
    }
}
