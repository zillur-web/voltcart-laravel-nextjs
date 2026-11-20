<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ApiResponse;
use App\Support\Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()->with('children')->whereNull('parent_id')->orderBy('sort_order')->get();

        return ApiResponse::success($categories->map(fn (Category $category) => $category->toPublicArray(true))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $category = Category::query()->create($this->attributes($data));

        return ApiResponse::success($category->toPublicArray(), 'Category created.', 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $this->validated($request, $category);
        $category->update($this->attributes($data, $category->id));

        return ApiResponse::success($category->fresh()->toPublicArray(), 'Category updated.');
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists() || $category->products()->exists()) {
            return ApiResponse::error('Remove child categories and products before deleting this category.', 422);
        }

        $category->delete();

        return ApiResponse::success(null, 'Category deleted.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$category?->id])],
            'image' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function attributes(array $data, ?int $ignoreId = null): array
    {
        if (! empty($data['parent_id'])) {
            $parent = Category::query()->find($data['parent_id']);
            if ($parent?->parent_id) {
                abort(422, 'Categories can only be two levels deep.');
            }
        }

        $base = Str::slug(Text::clean($data['name'])) ?: 'category';
        $slug = $base;
        $i = 1;
        while (Category::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return [
            'parent_id' => $data['parent_id'] ?? null,
            'name' => Text::clean($data['name']),
            'slug' => $slug,
            'image' => $data['image'] ?? null,
            'is_active' => (bool) $data['is_active'],
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
