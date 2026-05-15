<?php

use App\Models\User;
use App\Policies\ProcessInstancePolicy;
use Database\Seeders\RequiredDataSeeder;

// Security tests bắt buộc cho ProcessInstancePolicy (ADR-013)

test('admin can do everything with instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $instance = new \App\Models\ProcessInstance();

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->cancel($user, $instance))->toBeTrue();
    expect($policy->override($user, $instance))->toBeTrue();
    expect($policy->ping($user, $instance))->toBeTrue();
});

test('manager can launch, cancel, override, and ping instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');
    $instance = new \App\Models\ProcessInstance();

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->cancel($user, $instance))->toBeTrue();
    expect($policy->override($user, $instance))->toBeTrue();
    expect($policy->ping($user, $instance))->toBeTrue();
});

test('process_designer can view instances but not launch or override', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('process_designer');
    $instance = new \App\Models\ProcessInstance();

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();

    expect($policy->create($user))->toBeFalse();
    expect($policy->cancel($user, $instance))->toBeFalse();
    expect($policy->override($user, $instance))->toBeFalse();
    expect($policy->ping($user, $instance))->toBeFalse();
});

test('executor cannot launch, cancel, override, or ping instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('executor');
    $instance = new \App\Models\ProcessInstance();

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->cancel($user, $instance))->toBeFalse();
    expect($policy->override($user, $instance))->toBeFalse();
    expect($policy->ping($user, $instance))->toBeFalse();
});

test('beneficiary cannot manage instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('beneficiary');
    $instance = new \App\Models\ProcessInstance();

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->cancel($user, $instance))->toBeFalse();
    expect($policy->override($user, $instance))->toBeFalse();
    expect($policy->ping($user, $instance))->toBeFalse();
});
