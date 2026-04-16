<?php

use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// ─── Seeding & Role/Permission Matrix ─────────────────────────────────────────

test('all 5 roles exist after RequiredDataSeeder', function () {
    $this->seed(RequiredDataSeeder::class);

    foreach (['admin', 'manager', 'process_designer', 'executor', 'beneficiary'] as $role) {
        expect(Role::where('name', $role)->exists())->toBeTrue("Role '{$role}' missing");
    }
});

test('all 9 permissions exist after RequiredDataSeeder', function () {
    $this->seed(RequiredDataSeeder::class);

    $expected = [
        'manage_templates', 'publish_templates', 'launch_instances',
        'view_all_instances', 'manage_instances', 'complete_assigned_steps',
        'view_own_instances', 'manage_users', 'manage_system',
    ];

    foreach ($expected as $permission) {
        expect(Permission::where('name', $permission)->exists())->toBeTrue("Permission '{$permission}' missing");
    }
});

test('admin has all 9 permissions', function () {
    $this->seed(RequiredDataSeeder::class);

    $adminRole = Role::findByName('admin');

    foreach (['manage_templates', 'publish_templates', 'launch_instances',
        'view_all_instances', 'manage_instances', 'complete_assigned_steps',
        'view_own_instances', 'manage_users', 'manage_system'] as $permission) {
        expect($adminRole->hasPermissionTo($permission))->toBeTrue("Admin missing '{$permission}'");
    }
});

test('manager has correct permissions', function () {
    $this->seed(RequiredDataSeeder::class);

    $role = Role::findByName('manager');

    expect($role->hasPermissionTo('launch_instances'))->toBeTrue();
    expect($role->hasPermissionTo('view_all_instances'))->toBeTrue();
    expect($role->hasPermissionTo('manage_instances'))->toBeTrue();
    expect($role->hasPermissionTo('view_own_instances'))->toBeTrue();

    expect($role->hasPermissionTo('manage_users'))->toBeFalse();
    expect($role->hasPermissionTo('manage_system'))->toBeFalse();
    expect($role->hasPermissionTo('manage_templates'))->toBeFalse();
    expect($role->hasPermissionTo('complete_assigned_steps'))->toBeFalse();
});

test('process_designer has correct permissions', function () {
    $this->seed(RequiredDataSeeder::class);

    $role = Role::findByName('process_designer');

    expect($role->hasPermissionTo('manage_templates'))->toBeTrue();
    expect($role->hasPermissionTo('publish_templates'))->toBeTrue();
    expect($role->hasPermissionTo('view_all_instances'))->toBeTrue();
    expect($role->hasPermissionTo('view_own_instances'))->toBeTrue();

    expect($role->hasPermissionTo('launch_instances'))->toBeFalse();
    expect($role->hasPermissionTo('manage_users'))->toBeFalse();
    expect($role->hasPermissionTo('complete_assigned_steps'))->toBeFalse();
});

test('executor only has complete_assigned_steps and view_own_instances', function () {
    $this->seed(RequiredDataSeeder::class);

    $role = Role::findByName('executor');

    expect($role->hasPermissionTo('complete_assigned_steps'))->toBeTrue();
    expect($role->hasPermissionTo('view_own_instances'))->toBeTrue();

    expect($role->hasPermissionTo('launch_instances'))->toBeFalse();
    expect($role->hasPermissionTo('manage_templates'))->toBeFalse();
    expect($role->hasPermissionTo('manage_users'))->toBeFalse();
    expect($role->hasPermissionTo('view_all_instances'))->toBeFalse();
});

test('beneficiary only has view_own_instances', function () {
    $this->seed(RequiredDataSeeder::class);

    $role = Role::findByName('beneficiary');

    expect($role->hasPermissionTo('view_own_instances'))->toBeTrue();

    expect($role->hasPermissionTo('complete_assigned_steps'))->toBeFalse();
    expect($role->hasPermissionTo('launch_instances'))->toBeFalse();
    expect($role->hasPermissionTo('manage_templates'))->toBeFalse();
    expect($role->hasPermissionTo('manage_users'))->toBeFalse();
});

test('admin account is created by RequiredDataSeeder', function () {
    $this->seed(RequiredDataSeeder::class);

    $admin = User::where('email', config('app.admin.email'))->first();
    expect($admin)->not->toBeNull();
    expect($admin->hasRole('admin'))->toBeTrue();
    expect($admin->is_active)->toBeTrue();
});

test('seeder is idempotent — running twice does not create duplicates', function () {
    $this->seed(RequiredDataSeeder::class);
    $this->seed(RequiredDataSeeder::class);

    expect(Role::where('name', 'admin')->count())->toBe(1);
    expect(Permission::where('name', 'manage_users')->count())->toBe(1);
    expect(User::where('email', config('app.admin.email'))->count())->toBe(1);
});

// ─── Dashboard Access Control (AC2, AC3) ──────────────────────────────────────

test('executor gets 403 on GET /dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('executor');

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

test('beneficiary gets 403 on GET /dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('beneficiary');

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

test('user without any role gets 403 on GET /dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create(); // no role

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

test('admin can access dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('manager can access dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('process_designer can access dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('process_designer');

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

// ─── Permission Cache (AC5) ────────────────────────────────────────────────────

test('permission check uses cache and not database after warm-up', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');

    // Warm permission cache
    $user->hasPermissionTo('launch_instances');

    // Subsequent checks should hit cache (array cache in testing env)
    DB::flushQueryLog();
    DB::enableQueryLog();

    $user->hasPermissionTo('launch_instances');
    $user->hasPermissionTo('view_all_instances');

    $queries = DB::getQueryLog();

    // With cache warm, permission check queries go through cache driver, not DB
    // In test env CACHE_STORE=array: spatie reads from in-memory cache after first load
    $permissionQueries = collect($queries)->filter(
        fn ($q) => str_contains($q['query'], 'permissions')
    );

    expect($permissionQueries)->toHaveCount(0);
});
