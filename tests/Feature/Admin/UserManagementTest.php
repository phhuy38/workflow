<?php

use App\Events\UserDeactivated;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Support\Facades\Event;

// ─── AC1: Admin can list users ────────────────────────────────────────────────

test('admin can access user management index', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Users/Index'));
});

test('non-admin cannot access user management index', function () {
    $this->seed(RequiredDataSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('guest is redirected from user management index', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

// ─── AC2: Admin can create users ─────────────────────────────────────────────

test('admin can create a new user', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'role' => 'executor',
        ])
        ->assertRedirect(route('admin.users.index'));

    $newUser = User::where('email', 'jane@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->hasRole('executor'))->toBeTrue();
});

test('store validates required fields', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [])
        ->assertSessionHasErrors(['full_name', 'email', 'password', 'role']);
});

test('store rejects admin role assignment', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'full_name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ])
        ->assertSessionHasErrors(['role']);
});

// ─── AC3: Admin can update users ─────────────────────────────────────────────

test('admin can update user basic info', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create(['full_name' => 'Old Name']);
    $target->assignRole('executor');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'full_name' => 'New Name',
            'email' => $target->email,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($target->fresh()->full_name)->toBe('New Name');
});

// ─── AC4: Deactivate / Reactivate ────────────────────────────────────────────

test('admin can deactivate another user', function () {
    Event::fake([UserDeactivated::class]);

    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole('executor');

    $this->actingAs($admin)
        ->post(route('admin.users.deactivate', $target))
        ->assertRedirect();

    expect($target->fresh()->is_active)->toBeFalse();
    Event::assertDispatched(UserDeactivated::class);
});

test('admin cannot deactivate themselves', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.deactivate', $admin))
        ->assertForbidden();
});

test('admin can reactivate a deactivated user', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create(['is_active' => false]);
    $target->assignRole('executor');

    $this->actingAs($admin)
        ->post(route('admin.users.reactivate', $target))
        ->assertRedirect();

    expect($target->fresh()->is_active)->toBeTrue();
});

// ─── AC5 extra: RBAC protection for specific roles ───────────────────────────

test('executor gets 403 on user management routes', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $this->actingAs($executor)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('deactivated user cannot login after admin deactivation', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('executor');

    // Admin deactivates user
    $this->actingAs($admin)
        ->post(route('admin.users.deactivate', $user))
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeFalse();

    // Deactivated user attempts login
    auth()->logout();
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('non-admin user cannot self-assign process_designer role', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $this->actingAs($executor)
        ->post(route('admin.users.assign-designer', $executor))
        ->assertForbidden();

    expect($executor->fresh()->hasRole('process_designer'))->toBeFalse();
});

// ─── AC5: Process Designer role assignment ───────────────────────────────────

test('admin can assign process_designer role', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();
    $target->assignRole('manager');

    $this->actingAs($admin)
        ->post(route('admin.users.assign-designer', $target))
        ->assertRedirect();

    expect($target->fresh()->hasRole('process_designer'))->toBeTrue();
});

test('admin can revoke process_designer role', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();
    $target->assignRole('manager');
    $target->assignRole('process_designer');

    $this->actingAs($admin)
        ->post(route('admin.users.revoke-designer', $target))
        ->assertRedirect();

    expect($target->fresh()->hasRole('process_designer'))->toBeFalse();
});
