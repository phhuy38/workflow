<?php

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;

test('unauthenticated users are redirected to login', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('session lifetime is configured as a positive integer', function () {
    // Verify AC3: session timeout được cấu hình bởi SESSION_LIFETIME (minutes)
    expect(config('session.lifetime'))->toBeInt()->toBeGreaterThan(0);
});

test('auth.can permissions are shared in inertia props', function () {
    $this->seed(PermissionsSeeder::class);
    $this->seed(RolesSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('manager');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('auth.can')
        ->where('auth.can.launch_instances', true)
        ->where('auth.can.view_all_instances', true)
        ->where('auth.can.manage_users', false)
    );
});

test('admin has all permissions in auth.can', function () {
    $this->seed(PermissionsSeeder::class);
    $this->seed(RolesSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('auth.can.manage_users', true)
        ->where('auth.can.manage_system', true)
        ->where('auth.can.manage_templates', true)
    );
});

test('unauthenticated user has all-false auth.can in inertia props', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    // AC8: guest users receive auth.can as an object with all permissions = false
    $response->assertInertia(fn ($page) => $page
        ->where('auth.can.manage_templates', false)
        ->where('auth.can.manage_users', false)
        ->where('auth.can.manage_system', false)
    );
});
