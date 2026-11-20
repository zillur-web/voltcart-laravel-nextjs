<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ApiResponse;
use App\Support\Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Product::query()->with(['images', 'category'])->latest();

        if (! empty($data['q'])) {
            $like = Text::like(Text::clean($data['q']));
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)->orWhere('sku', 'like', $like);
            });
        }

        if (! empty($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }

        $paginator = $query->paginate($data['per_page'] ?? 12);

        return ApiResponse::success(
            collect($paginator->items())->map(fn (Product $product) => $product->toAdminArray())->values(),
            '',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['images', 'category']);

        return ApiResponse::success($product->toAdminArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $product = DB::transaction(function () use ($request, $data) {
            $product = Product::query()->create($this->attributes($data));
            $product->slug = $this->slug($product->name, $product->id);
            $product->save();
            $this->storeImages($request, $product);

            return $product;
        });

        ActivityLog::write($request->user()->id, 'product.created', $product);
        $product->load(['images', 'category']);

        return ApiResponse::success($product->toAdminArray(), 'Product created.', 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request, $product);
        $product->fill($this->attributes($data));
        $product->slug = $this->slug($product->name, $product->id);
        $product->save();
        $this->storeImages($request, $product);
        ActivityLog::write($request->user()->id, 'product.updated', $product);
        $product->load(['images', 'category']);

        return ApiResponse::success($product->toAdminArray(), 'Product updated.');
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        if ($product->orderItems()->exists()) {
            return ApiResponse::error('This product is part of existing orders and cannot be deleted. Deactivate it instead.', 422);
        }

        $product->delete();
        ActivityLog::write($request->user()->id, 'product.deleted', null, ['id' => $product->id, 'name' => $product->name]);

        return ApiResponse::success(null, 'Product deleted.');
    }

    public function destroyImage(Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return ApiResponse::error('Image not found.', 404);
        }

        $image->delete();

        return ApiResponse::success(null, 'Image removed.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($product?->id)],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'color' => ['nullable', 'string', 'max:40'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*' => ['image', 'max:4096'],
        ]);
    }

    private function attributes(array $data): array
    {
        return [
            'category_id' => $data['category_id'],
            'name' => Text::clean($data['name']),
            'slug' => 'tmp-'.Str::uuid(),
            'description' => Text::clean($data['description']),
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'sku' => strtoupper(Text::clean($data['sku'])),
            'stock' => $data['stock'],
            'color' => Text::clean($data['color'] ?? null),
            'sizes' => array_values($data['sizes'] ?? []),
            'is_active' => $requestActive = (bool) $data['is_active'],
        ];
    }

    private function slug(string $name, int $id): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 1;
        while (Product::query()->where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function storeImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $start = (int) $product->images()->max('sort_order');
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($request->file('images') as $index => $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'sort_order' => $start + $index + 1,
                'is_primary' => ! $hasPrimary && $index === 0,
            ]);
        }
    }
}
