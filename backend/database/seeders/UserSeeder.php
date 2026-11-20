<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Avery Cole', 'superadmin@shopco.test', 'super-admin'],
            ['Jordan Blake', 'admin@shopco.test', 'admin'],
            ['Mina Shah', 'products@shopco.test', 'product-manager'],
            ['Leo Martins', 'orders@shopco.test', 'order-manager'],
            ['Sam Okonkwo', 'support@shopco.test', 'support'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'Password123!',
                    'is_staff' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->roles()->sync([Role::query()->where('slug', $role)->value('id')]);
        }

        User::query()->updateOrCreate(
            ['email' => 'customer@shopco.test'],
            [
                'name' => 'Riley Chen',
                'password' => 'Password123!',
                'is_staff' => false,
                'phone' => '555-0148',
                'email_verified_at' => now(),
            ]
        );
    }
}
