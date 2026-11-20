<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\ApiResponse;
use App\Support\Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['children' => fn ($query) => $query->where('is_active', true)])
            ->get();

        $newArrivals = Product::query()
            ->where('is_active', true)
            ->with(['images', 'category'])
            ->latest()
            ->limit(8)
            ->get();

        $topIds = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as sold')
            ->whereNotNull('product_id')
            ->whereHas('order', fn ($query) => $query->whereIn('status', ['paid', 'processing', 'shipped', 'delivered']))
            ->groupBy('product_id')
            ->orderByDesc('sold')
            ->limit(4)
            ->pluck('product_id');

        $topSelling = Product::query()
            ->where('is_active', true)
            ->whereIn('id', $topIds)
            ->with(['images', 'category'])
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $topIds->all(), true))
            ->values();

        if ($topSelling->isEmpty()) {
            $topSelling = Product::query()
                ->where('is_active', true)
                ->with(['images', 'category'])
                ->orderByDesc('rating')
                ->limit(4)
                ->get();
        }

        return ApiResponse::success([
            'categories' => $categories->map(fn (Category $category) => $category->toPublicArray(true))->values(),
            'new_arrivals' => $newArrivals->map(fn (Product $product) => $product->toCatalogArray())->values(),
            'top_selling' => $topSelling->map(fn (Product $product) => $product->toCatalogArray())->values(),
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with('children')
            ->get();

        return ApiResponse::success(
            $categories->map(fn (Category $category) => $category->toPublicArray(true))->values()
        );
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:40'],
            'size' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $query = Product::query()->where('is_active', true)->with(['images', 'category']);

        if (! empty($data['q'])) {
            $like = Text::like(Text::clean($data['q']));
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)->orWhere('sku', 'like', $like);
            });
        }

        if (! empty($data['category'])) {
            $category = Category::query()->where('slug', $data['category'])->where('is_active', true)->first();
            if (! $category) {
                return ApiResponse::success([], '', 200, $this->emptyMeta());
            }
            $query->whereIn('category_id', $category->listingIds());
        }

        if (isset($data['min_price'])) {
            $query->whereRaw('COALESCE(discount_price, price) >= ?', [$data['min_price']]);
        }

        if (isset($data['max_price'])) {
            $query->whereRaw('COALESCE(discount_price, price) <= ?', [$data['max_price']]);
        }

        if (! empty($data['color'])) {
            $query->where('color', $data['color']);
        }

        if (! empty($data['size'])) {
            $query->whereJsonContains('sizes', $data['size']);
        }

        match ($data['sort'] ?? 'newest') {
            'price_asc' => $query->orderByRaw('COALESCE(discount_price, price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(discount_price, price) desc'),
            default => $query->latest(),
        };

        $paginator = $query->paginate($data['per_page'] ?? 9);

        return ApiResponse::success(
            collect($paginator->items())->map(fn (Product $product) => $product->toCatalogArray())->values(),
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

    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['images', 'category'])
            ->first();

        if (! $product) {
            return ApiResponse::error('Product not found.', 404);
        }

        $related = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['images', 'category'])
            ->limit(4)
            ->get();

        return ApiResponse::success([
            'product' => $product->toCatalogArray(true),
            'related' => $related->map(fn (Product $item) => $item->toCatalogArray())->values(),
        ]);
    }

    private function emptyMeta(): array
    {
        return [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 9,
            'total' => 0,
        ];
    }
}
