<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RequiredDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
