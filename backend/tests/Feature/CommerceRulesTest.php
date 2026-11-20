<?php

namespace Tests\Feature;

use App\Exceptions\CouponException;
use App\Exceptions\OversellException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_does_not_reveal_which_field_failed(): void
    {
        User::query()->create([
            'name' => 'Riley',
            'email' => 'riley@example.com',
            'password' => 'Password123!',
            'is_staff' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'riley@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonPath('message', 'These credentials do not match our records.');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'Password123!',
        ])->assertStatus(422)->assertJsonPath('message', 'These credentials do not match our records.');
    }

    public function test_customers_cannot_view_another_customers_order(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'Password123!',
        ]);
        $other = User::query()->create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'password' => 'Password123!',
        ]);

        $order = Order::query()->create([
            'user_id' => $owner->id,
            'status' => 'pending',
            'email' => $owner->email,
            'shipping_name' => 'Owner',
            'phone' => '555',
            'line1' => '1 Main',
            'city' => 'NYC',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'United States',
            'subtotal' => 10,
            'discount' => 0,
            'shipping' => 0,
            'total' => 10,
        ]);
        $order->assignNumber();

        $token = $other->createToken('customer', ['customer'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/orders/'.$order->id)
            ->assertStatus(404);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'OLD',
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 0,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->expectException(CouponException::class);
        app(CouponService::class)->discountCents($coupon, 5000);
    }

    public function test_stock_is_taken_once_and_oversell_is_rejected(): void
    {
        $user = User::query()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => 'Password123!',
        ]);

        $categoryId = \App\Models\Category::query()->create([
            'name' => 'Tees',
            'slug' => 'tees',
            'is_active' => true,
        ])->id;

        $product = Product::query()->create([
            'category_id' => $categoryId,
            'name' => 'Tee',
            'slug' => 'tee',
            'description' => 'A tee',
            'price' => 20,
            'sku' => 'TEE-1',
            'stock' => 1,
            'is_active' => true,
        ]);

        $first = $this->pendingOrder($user, $product);
        $second = $this->pendingOrder($user, $product);
        $service = app(OrderService::class);

        $service->markPaid($first);
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame('paid', $first->fresh()->status);

        $this->expectException(OversellException::class);
        $service->markPaid($second);
    }

    public function test_cart_ignores_a_client_supplied_price(): void
    {
        $categoryId = \App\Models\Category::query()->create([
            'name' => 'Tees',
            'slug' => 'tees',
            'is_active' => true,
        ])->id;

        $product = Product::query()->create([
            'category_id' => $categoryId,
            'name' => 'Tee',
            'slug' => 'tee',
            'description' => 'A tee',
            'price' => 40,
            'discount_price' => 30,
            'sku' => 'TEE-2',
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1,
        ])->assertStatus(422);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk();

        $this->assertSame(Money::fromCents($product->effectivePriceCents()), $response->json('data.items.0.unit_price'));
        $this->assertSame('60.00', $response->json('data.items.0.line_total'));
    }

    private function pendingOrder(User $user, Product $product): Order
    {
        $order = Order::query()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'email' => $user->email,
            'shipping_name' => $user->name,
            'phone' => '555',
            'line1' => '1 Main',
            'city' => 'NYC',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'United States',
            'subtotal' => 20,
            'discount' => 0,
            'shipping' => 15,
            'total' => 35,
        ]);
        $order->assignNumber();
        $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => 20,
            'quantity' => 1,
            'line_total' => 20,
        ]);

        return $order;
    }
}
