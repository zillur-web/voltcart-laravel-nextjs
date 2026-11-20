<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = User::query()->where('is_staff', false)->withCount('orders')->latest();

        if (! empty($data['q'])) {
            $like = Text::like(Text::clean($data['q']));
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)->orWhere('email', 'like', $like);
            });
        }

        $paginator = $query->paginate($data['per_page'] ?? 12);

        return ApiResponse::success(
            collect($paginator->items())->map(fn (User $user) => [
                ...$user->toPublicArray(),
                'orders_count' => $user->orders_count,
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

    public function show(int $customer): JsonResponse
    {
        $user = User::query()->where('is_staff', false)->with(['orders' => fn ($query) => $query->latest()->with('items')])->find($customer);

        if (! $user) {
            return ApiResponse::error('Customer not found.', 404);
        }

        return ApiResponse::success([
            ...$user->toPublicArray(),
            'orders' => $user->orders->map(fn ($order) => $order->toPublicArray())->values(),
        ]);
    }
}
