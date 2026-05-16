<?php

use App\Models\ProcessInstance;
use App\Models\User;
use App\Policies\ProcessInstancePolicy;
use Database\Seeders\RequiredDataSeeder;

// Security tests bắt buộc cho ProcessInstancePolicy (ADR-013)

test('admin can do everything with instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $instance = new ProcessInstance;

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->cancel($user, $instance))->toBeTrue();
    expect($policy->override($user, $instance))->toBeTrue();
    expect($policy->ping($user, $instance))->toBeTrue();
});

test('manager can launch and ping instances, but cancel/override only if they launched it', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');
    $instanceOwned = new ProcessInstance(['launched_by' => $user->id]);
    $instanceNotOwned = new ProcessInstance(['launched_by' => 999]);

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->ping($user, $instanceOwned))->toBeTrue();

    // Owned
    expect($policy->cancel($user, $instanceOwned))->toBeTrue();
    expect($policy->override($user, $instanceOwned))->toBeTrue();

    // Not owned
    expect($policy->cancel($user, $instanceNotOwned))->toBeFalse();
    expect($policy->override($user, $instanceNotOwned))->toBeFalse();
});

test('process_designer can view instances but not launch or override', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('process_designer');
    $instance = new ProcessInstance;

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
    $instance = new ProcessInstance;

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeFalse();
    expect($policy->cancel($user, $instance))->toBeFalse();
    expect($policy->override($user, $instance))->toBeFalse();
    expect($policy->ping($user, $instance))->toBeFalse();
});

test('beneficiary cannot manage instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('beneficiary');
    $instance = new ProcessInstance;

    $policy = new ProcessInstancePolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->create($user))->toBeFalse();
    expect($policy->cancel($user, $instance))->toBeFalse();
    expect($policy->override($user, $instance))->toBeFalse();
    expect($policy->ping($user, $instance))->toBeFalse();
});
