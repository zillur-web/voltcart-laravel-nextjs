<?php

use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\V1\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\AdminAuthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgot']);
    Route::post('auth/reset-password', [AuthController::class, 'reset']);
    Route::get('auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::get('home', [CatalogController::class, 'home']);
    Route::get('categories', [CatalogController::class, 'categories']);
    Route::get('products', [CatalogController::class, 'index']);
    Route::get('products/{slug}', [CatalogController::class, 'show']);

    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'add']);
    Route::patch('cart/items/{item}', [CartController::class, 'update']);
    Route::delete('cart/items/{item}', [CartController::class, 'remove']);
    Route::post('cart/coupon', [CartController::class, 'applyCoupon']);
    Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);

    Route::middleware(['auth:sanctum', 'customer'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('cart/merge', [CartController::class, 'merge']);
        Route::post('checkout', [CheckoutController::class, 'store']);
        Route::post('checkout/confirm', [CheckoutController::class, 'confirm']);
        Route::get('orders', [CustomerOrderController::class, 'index']);
        Route::get('orders/{order}', [CustomerOrderController::class, 'show']);
    });

    Route::prefix('admin')->group(function () {
        Route::post('auth/login', [AdminAuthController::class, 'login']);
        Route::post('auth/forgot-password', [AdminAuthController::class, 'forgot']);
        Route::post('auth/reset-password', [AdminAuthController::class, 'reset']);

        Route::middleware(['auth:sanctum', 'staff'])->group(function () {
            Route::get('auth/me', [AdminAuthController::class, 'me']);
            Route::post('auth/logout', [AdminAuthController::class, 'logout']);
            Route::put('profile', [AdminAuthController::class, 'updateProfile']);

            Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permission:reports.view');

            Route::get('products', [AdminProductController::class, 'index'])->middleware('permission:products.view');
            Route::post('products', [AdminProductController::class, 'store'])->middleware('permission:products.create');
            Route::get('products/{product}', [AdminProductController::class, 'show'])->middleware('permission:products.view');
            Route::match(['put', 'post'], 'products/{product}', [AdminProductController::class, 'update'])->middleware('permission:products.edit');
            Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->middleware('permission:products.delete');
            Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])->middleware('permission:products.edit');

            Route::get('categories', [AdminCategoryController::class, 'index'])->middleware('permission:categories.view');
            Route::post('categories', [AdminCategoryController::class, 'store'])->middleware('permission:categories.manage');
            Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->middleware('permission:categories.manage');
            Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('permission:categories.manage');

            Route::get('orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.view');
            Route::get('orders/{order}', [AdminOrderController::class, 'show'])->middleware('permission:orders.view');
            Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.update_status');

            Route::get('coupons', [AdminCouponController::class, 'index'])->middleware('permission:coupons.manage');
            Route::post('coupons', [AdminCouponController::class, 'store'])->middleware('permission:coupons.manage');
            Route::put('coupons/{coupon}', [AdminCouponController::class, 'update'])->middleware('permission:coupons.manage');
            Route::delete('coupons/{coupon}', [AdminCouponController::class, 'destroy'])->middleware('permission:coupons.manage');

            Route::get('customers', [AdminCustomerController::class, 'index'])->middleware('permission:customers.view');
            Route::get('customers/{customer}', [AdminCustomerController::class, 'show'])->middleware('permission:customers.view');

            Route::middleware('permission:roles.manage')->group(function () {
                Route::get('roles', [RoleController::class, 'index']);
                Route::post('roles', [RoleController::class, 'store']);
                Route::put('roles/{role}', [RoleController::class, 'update']);
                Route::delete('roles/{role}', [RoleController::class, 'destroy']);
            });

            Route::middleware('permission:staff.manage')->group(function () {
                Route::get('staff', [RoleController::class, 'staff']);
                Route::post('staff', [RoleController::class, 'storeStaff']);
                Route::put('staff/{user}', [RoleController::class, 'updateStaff']);
                Route::delete('staff/{user}', [RoleController::class, 'destroyStaff']);
            });
        });
    });
});
