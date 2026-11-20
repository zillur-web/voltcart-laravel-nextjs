<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['View products', 'products.view', 'Products'],
            ['Create products', 'products.create', 'Products'],
            ['Edit products', 'products.edit', 'Products'],
            ['Delete products', 'products.delete', 'Products'],
            ['View categories', 'categories.view', 'Categories'],
            ['Manage categories', 'categories.manage', 'Categories'],
            ['View orders', 'orders.view', 'Orders'],
            ['Update order status', 'orders.update_status', 'Orders'],
            ['Manage coupons', 'coupons.manage', 'Coupons'],
            ['View customers', 'customers.view', 'Customers'],
            ['View reports', 'reports.view', 'Reports'],
            ['Manage roles', 'roles.manage', 'Roles'],
            ['Manage staff', 'staff.manage', 'Staff'],
        ];

        $permissions = collect($definitions)->mapWithKeys(function (array $row) {
            $permission = Permission::query()->updateOrCreate(
                ['slug' => $row[1]],
                ['name' => $row[0], 'group' => $row[2]]
            );

            return [$row[1] => $permission->id];
        });

        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'description' => 'Full access. This role cannot be edited or deleted.',
                'is_super' => true,
                'permissions' => $permissions->values()->all(),
            ],
            'admin' => [
                'name' => 'Admin',
                'description' => 'Store operations except role and staff administration.',
                'is_super' => false,
                'permissions' => $permissions->except(['roles.manage', 'staff.manage'])->values()->all(),
            ],
            'product-manager' => [
                'name' => 'Product Manager',
                'description' => 'Catalog, pricing, and stock.',
                'is_super' => false,
                'permissions' => $permissions->only([
                    'products.view', 'products.create', 'products.edit', 'products.delete',
                    'categories.view', 'categories.manage',
                ])->values()->all(),
            ],
            'order-manager' => [
                'name' => 'Order Manager',
                'description' => 'Orders and customer lookup.',
                'is_super' => false,
                'permissions' => $permissions->only([
                    'orders.view', 'orders.update_status', 'customers.view',
                ])->values()->all(),
            ],
            'support' => [
                'name' => 'Support',
                'description' => 'Read-only orders and customers.',
                'is_super' => false,
                'permissions' => $permissions->only(['orders.view', 'customers.view'])->values()->all(),
            ],
        ];

        foreach ($roles as $slug => $role) {
            $model = Role::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'is_super' => $role['is_super'],
                ]
            );
            $model->permissions()->sync($role['permissions']);
        }
    }
}
