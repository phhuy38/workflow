<?php

use App\Models\User;
use App\Policies\ProcessTemplatePolicy;
use Database\Seeders\RequiredDataSeeder;

// Security tests bắt buộc cho ProcessTemplatePolicy (ADR-013)

test('admin can manage templates', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $policy = new ProcessTemplatePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->update($user))->toBeTrue();
    expect($policy->delete($user))->toBeTrue();
    expect($policy->publish($user))->toBeTrue();
});

test('process_designer can manage and publish templates', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('process_designer');

    $policy = new ProcessTemplatePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->update($user))->toBeTrue();
    expect($policy->delete($user))->toBeTrue();
    expect($policy->publish($user))->toBeTrue();
});

test('manager can view templates but cannot create or publish', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');

    $policy = new ProcessTemplatePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user))->toBeTrue();

    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user))->toBeFalse();
    expect($policy->delete($user))->toBeFalse();
    expect($policy->publish($user))->toBeFalse();
});

test('executor cannot manage or view templates', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('executor');

    $policy = new ProcessTemplatePolicy;

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user))->toBeFalse();
    expect($policy->delete($user))->toBeFalse();
    expect($policy->publish($user))->toBeFalse();
});

test('beneficiary cannot access templates at all', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('beneficiary');

    $policy = new ProcessTemplatePolicy;

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->publish($user))->toBeFalse();
});
