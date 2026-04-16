<?php

use App\Models\User;
use Database\Seeders\RequiredDataSeeder;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin can visit the dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('manager can visit the dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('process_designer can visit the dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('process_designer');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('executor cannot visit the dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('executor');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertForbidden();
});

test('beneficiary cannot visit the dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('beneficiary');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertForbidden();
});
