<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Role → permissions matrix (ADR-004, ADR-044)
     */
    private const array ROLE_PERMISSIONS = [
        'admin' => [
            'manage_templates',
            'publish_templates',
            'launch_instances',
            'view_all_instances',
            'manage_instances',
            'complete_assigned_steps',
            'view_own_instances',
            'manage_users',
            'manage_system',
        ],
        'manager' => [
            'launch_instances',
            'view_all_instances',
            'manage_instances',
            'view_own_instances',
        ],
        'process_designer' => [
            'manage_templates',
            'publish_templates',
            'view_all_instances',
            'view_own_instances',
        ],
        'executor' => [
            'complete_assigned_steps',
            'view_own_instances',
        ],
        'beneficiary' => [
            'view_own_instances',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
