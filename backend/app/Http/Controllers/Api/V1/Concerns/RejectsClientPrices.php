<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait RejectsClientPrices
{
    protected function rejectClientPrices(Request $request): ?JsonResponse
    {
        foreach (['price', 'unit_price', 'total', 'subtotal', 'discount', 'amount', 'line_total'] as $field) {
            if ($request->exists($field)) {
                return ApiResponse::error('Prices and totals are calculated by the server and cannot be submitted.', 422);
            }
        }

        return null;
    }
}
