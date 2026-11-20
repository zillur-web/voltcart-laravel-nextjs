<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\CommerceException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\ApiResponse;
use App\Support\Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,paid,processing,shipped,delivered,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Order::query()->with('user')->latest();

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (! empty($data['q'])) {
            $like = Text::like(Text::clean($data['q']));
            $query->where(function ($inner) use ($like) {
                $inner->where('number', 'like', $like)->orWhere('email', 'like', $like)->orWhere('shipping_name', 'like', $like);
            });
        }

        $paginator = $query->paginate($data['per_page'] ?? 12);

        return ApiResponse::success(
            collect($paginator->items())->map(fn (Order $order) => [
                ...$order->toPublicArray(),
                'customer' => $order->shipping_name,
            ])->values(),
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

    public function show(Order $order): JsonResponse
    {
        $order->load(['items', 'payments', 'user']);

        return ApiResponse::success($order->toPublicArray(true));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,processing,shipped,delivered,cancelled'],
        ]);

        try {
            $updated = $this->orders->updateStatus($order, $data['status'], $request->user()->id);
        } catch (CommerceException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success($updated->toPublicArray(true), 'Order status updated.');
    }
}
