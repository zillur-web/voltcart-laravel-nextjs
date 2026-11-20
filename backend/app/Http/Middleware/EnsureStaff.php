<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_staff || ! $user->tokenCan('admin')) {
            return ApiResponse::error('Unauthorized.', 403);
        }

        return $next($request);
    }
}
