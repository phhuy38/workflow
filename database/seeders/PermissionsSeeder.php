<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public const array PERMISSIONS = [
        'manage_templates',
        'publish_templates',
        'launch_instances',
        'view_all_instances',
        'manage_instances',
        'complete_assigned_steps',
        'view_own_instances',
        'manage_users',
        'manage_system',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
