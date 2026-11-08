<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Owner Kamsya',
                'email' => 'owner@kamsya.id',
                'phone' => '0812-9000-1234',
                'role' => UserRole::Owner,
            ],
            [
                'name' => 'Admin Kamsya',
                'email' => 'admin@kamsya.id',
                'phone' => '0813-2211-4455',
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Petugas Kandang',
                'email' => 'petugas@kamsya.id',
                'phone' => '0857-9900-1122',
                'role' => UserRole::Keeper,
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'role' => $data['role'],
                    'is_active' => true,
                    'password' => 'kamsya123',
                    'email_verified_at' => now(),
                ]
            );

            $user->forceFill(['last_login_at' => null])->save();
        }
    }
}
