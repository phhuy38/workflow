<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => config('app.admin.email', 'admin@workflow.local')],
            [
                'full_name' => config('app.admin.name', 'System Admin'),
                'password' => Hash::make(config('app.admin.password', 'changeme')),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $admin->syncRoles(['admin']);
    }
}
