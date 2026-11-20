<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CommerceException;
use App\Http\Controllers\Api\V1\Concerns\RejectsClientPrices;
use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use RejectsClientPrices;

    public function __construct(private readonly CheckoutService $checkout)
    {
    }

    public function store(Request $request): JsonResponse
    {
        if ($rejected = $this->rejectClientPrices($request)) {
            return $rejected;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'line1' => ['required', 'string', 'max:180'],
            'line2' => ['nullable', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:80'],
        ]);

        try {
            $result = $this->checkout->start($request->user(), $data);
        } catch (CommerceException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success($result, 'Checkout started.', 201);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_intent_id' => ['required', 'string', 'max:120'],
        ]);

        try {
            $order = $this->checkout->confirm($request->user(), $data['payment_intent_id']);
        } catch (CommerceException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success($order->toPublicArray(true), 'Payment confirmed.');
    }
}
