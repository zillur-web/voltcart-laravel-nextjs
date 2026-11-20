<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now()->toDateString();
        $paidStatuses = ['paid', 'processing', 'shipped', 'delivered'];

        $sales = Order::query()
            ->whereIn('status', $paidStatuses)
            ->where('paid_at', '>=', now()->subDays(29)->startOfDay())
            ->get(['paid_at', 'total']);

        $days = collect(range(0, 29))->map(function (int $offset) use ($sales) {
            $date = now()->subDays(29 - $offset)->toDateString();
            $total = $sales
                ->filter(fn (Order $order) => optional($order->paid_at)->toDateString() === $date)
                ->sum(fn (Order $order) => (float) $order->total);

            return [
                'date' => $date,
                'total' => number_format($total, 2, '.', ''),
            ];
        });

        $recent = Order::query()->with('user')->latest()->limit(8)->get();

        return ApiResponse::success([
            'today_orders' => Order::query()->whereDate('created_at', $today)->where('status', '!=', 'cancelled')->count(),
            'today_revenue' => number_format((float) Order::query()->whereDate('paid_at', $today)->whereIn('status', $paidStatuses)->sum('total'), 2, '.', ''),
            'customers' => User::query()->where('is_staff', false)->count(),
            'low_stock' => Product::query()->where('is_active', true)->where('stock', '<=', 5)->count(),
            'sales' => $days,
            'recent_orders' => $recent->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'total' => (string) $order->total,
                'customer' => $order->user?->name ?? $order->shipping_name,
                'created_at' => $order->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
