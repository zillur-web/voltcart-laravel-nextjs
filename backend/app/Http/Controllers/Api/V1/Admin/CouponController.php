<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        $coupons = Coupon::query()->orderByDesc('id')->get();

        return ApiResponse::success($coupons->map(fn (Coupon $coupon) => $coupon->toPublicArray())->values());
    }

    public function store(Request $request): JsonResponse
    {
        $coupon = Coupon::query()->create($this->attributes($request));

        return ApiResponse::success($coupon->toPublicArray(), 'Coupon created.', 201);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $coupon->update($this->attributes($request, $coupon));

        return ApiResponse::success($coupon->fresh()->toPublicArray(), 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return ApiResponse::success(null, 'Coupon deleted.');
    }

    private function attributes(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($data['type'] === 'percent' && (float) $data['value'] > 100) {
            abort(422, 'Percentage coupons cannot exceed 100.');
        }

        return [
            'code' => strtoupper(trim($data['code'])),
            'type' => $data['type'],
            'value' => $data['value'],
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ];
    }
}
