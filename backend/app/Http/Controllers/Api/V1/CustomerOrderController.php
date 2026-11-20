<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return ApiResponse::success(
            collect($paginator->items())->map(fn (Order $order) => $order->toPublicArray())->values(),
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

    public function show(Request $request, int $order): JsonResponse
    {
        $record = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $order)
            ->with(['items', 'payments'])
            ->first();

        if (! $record) {
            return ApiResponse::error('Order not found.', 404);
        }

        return ApiResponse::success($record->toPublicArray(true));
    }
}
