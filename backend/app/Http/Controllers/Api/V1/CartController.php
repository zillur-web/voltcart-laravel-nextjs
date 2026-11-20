<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CommerceException;
use App\Http\Controllers\Api\V1\Concerns\RejectsClientPrices;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    use RejectsClientPrices;

    public function __construct(private readonly CartService $carts)
    {
    }

    public function show(Request $request): JsonResponse
    {
        [$cart, $token] = $this->carts->resolve($this->customer($request), $request->header('X-Cart-Token'));

        return ApiResponse::success($this->carts->present($cart, $token));
    }

    public function add(Request $request): JsonResponse
    {
        if ($rejected = $this->rejectClientPrices($request)) {
            return $rejected;
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'size' => ['nullable', 'string', 'max:20'],
        ]);

        [$cart, $token] = $this->carts->resolve($this->customer($request), $request->header('X-Cart-Token'));
        $product = Product::query()->findOrFail($data['product_id']);

        try {
            $this->carts->add($cart, $product, $data['quantity'], $data['size'] ?? null);
        } catch (CommerceException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success($this->carts->present($cart->fresh(), $token), 'Added to cart.');
    }

    public function update(Request $request, CartItem $item): JsonResponse
    {
        if ($rejected = $this->rejectClientPrices($request)) {
            return $rejected;
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        [$cart, $token] = $this->carts->resolve($this->customer($request), $request->header('X-Cart-Token'));

        try {
            $this->carts->updateQuantity($cart, $item, $data['quantity']);
        } catch (CommerceException $exception) {
            $status = $exception->getMessage() === 'Cart item not found.' ? 404 : 422;

            return ApiResponse::error($status === 404 ? 'Cart item not found.' : $exception->getMessage(), $status);
        }

        return ApiResponse::success($this->carts->present($cart->fresh(), $token), 'Cart updated.');
    }

    public function remove(Request $request, CartItem $item): JsonResponse
    {
        [$cart, $token] = $this->carts->resolve($this->customer($request), $request->header('X-Cart-Token'));

        try {
            $this->carts->remove($cart, $item);
        } catch (CommerceException $exception) {
            return ApiResponse::error('Cart item not found.', 404);
        }

        return ApiResponse::success($this->carts->present($cart->fresh(), $token), 'Item removed.');
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        if ($rejected = $this->rejectClientPrices($request)) {
            return $rejected;
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ]);

        [$cart, $token] = $this->carts->resolve($this->customer($request), $request->header('X-Cart-Token'));

        try {
            $this->carts->applyCoupon($cart, $data['code']);
        } catch (CommerceException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success($this->carts->present($cart->fresh(), $token), 'Coupon applied.');
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        [$cart, $token] = $this->carts->resolve($this->customer($request), $request->header('X-Cart-Token'));
        $this->carts->removeCoupon($cart);

        return ApiResponse::success($this->carts->present($cart->fresh(), $token), 'Coupon removed.');
    }

    public function merge(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->is_staff) {
            return ApiResponse::error('Unauthorized.', 403);
        }

        $cart = $this->carts->merge($user, $request->header('X-Cart-Token') ?: $request->input('guest_token'));

        return ApiResponse::success($this->carts->present($cart), 'Cart merged.');
    }

    private function customer(Request $request): ?\App\Models\User
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user || $user->is_staff || ! $user->tokenCan('customer')) {
            return null;
        }

        return $user;
    }
}
