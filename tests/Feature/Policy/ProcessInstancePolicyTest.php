<?php

use App\Models\User;
use App\Policies\ProcessInstancePolicy;
use Database\Seeders\RequiredDataSeeder;

// Security tests bắt buộc cho ProcessInstancePolicy (ADR-013)

test('admin can do everything with instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->cancel($user))->toBeTrue();
    expect($policy->override($user))->toBeTrue();
    expect($policy->ping($user))->toBeTrue();
});

test('manager can launch, cancel, override, and ping instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->cancel($user))->toBeTrue();
    expect($policy->override($user))->toBeTrue();
    expect($policy->ping($user))->toBeTrue();
});

test('process_designer can view instances but not launch or override', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('process_designer');

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();

    expect($policy->create($user))->toBeFalse();
    expect($policy->cancel($user))->toBeFalse();
    expect($policy->override($user))->toBeFalse();
    expect($policy->ping($user))->toBeFalse();
});

test('executor cannot launch, cancel, override, or ping instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('executor');

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->cancel($user))->toBeFalse();
    expect($policy->override($user))->toBeFalse();
    expect($policy->ping($user))->toBeFalse();
});

test('beneficiary cannot manage instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('beneficiary');

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->cancel($user))->toBeFalse();
    expect($policy->override($user))->toBeFalse();
    expect($policy->ping($user))->toBeFalse();
});
