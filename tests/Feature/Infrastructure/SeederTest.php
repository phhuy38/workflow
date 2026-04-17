<?php

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds all required permissions', function () {
    $this->artisan('db:seed', ['--class' => RequiredDataSeeder::class]);

    expect(Permission::count())->toBe(9);

    foreach (PermissionsSeeder::PERMISSIONS as $permission) {
        expect(Permission::where('name', $permission)->exists())->toBeTrue();
    }
});

it('seeds all required roles', function () {
    $this->artisan('db:seed', ['--class' => RequiredDataSeeder::class]);

    expect(Role::count())->toBe(5);

    foreach (['admin', 'manager', 'process_designer', 'executor', 'beneficiary'] as $role) {
        expect(Role::where('name', $role)->exists())->toBeTrue();
    }
});

it('seeds admin user with admin role', function () {
    $this->artisan('db:seed', ['--class' => RequiredDataSeeder::class]);

    $adminEmail = config('app.admin.email', 'admin@workflow.local');
    $admin = User::where('email', $adminEmail)->first();

    expect($admin)->not->toBeNull();
    expect($admin->hasRole('admin'))->toBeTrue();
    expect($admin->is_active)->toBeTrue();
});

it('assigns correct permissions to admin role', function () {
    $this->artisan('db:seed', ['--class' => RequiredDataSeeder::class]);

    $admin = Role::findByName('admin');
    expect($admin->permissions()->count())->toBe(9);
});

it('assigns correct permissions to manager role', function () {
    $this->artisan('db:seed', ['--class' => RequiredDataSeeder::class]);

    $manager = Role::findByName('manager');
    expect($manager->permissions()->pluck('name'))
        ->toContain('launch_instances')
        ->toContain('view_all_instances')
        ->toContain('manage_instances')
        ->toContain('view_own_instances');
});

it('restricts beneficiary to view_own_instances only', function () {
    $this->artisan('db:seed', ['--class' => RequiredDataSeeder::class]);

    $beneficiary = Role::findByName('beneficiary');
    expect($beneficiary->permissions()->count())->toBe(1);
    expect($beneficiary->permissions()->first()->name)->toBe('view_own_instances');
});
