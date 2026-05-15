<?php

use App\Actions\Process\ResolveStepAssignee;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
    $this->action = new ResolveStepAssignee;
});

test('resolves user type correctly', function () {
    $user = User::factory()->create();

    $snapshot = [
        'assignee_type' => 'user',
        'assignee_id' => $user->id,
    ];

    $result = $this->action->handle($snapshot);

    expect($result)->toBe($user->id);
});

test('resolves role type correctly', function () {
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $snapshot = [
        'assignee_type' => 'role',
        'assignee_id' => 'executor', // Assuming assignee_id stores role name
    ];

    $result = $this->action->handle($snapshot);

    expect($result)->toBe($executor->id);
});

test('returns null and logs warning if assignee_type is missing', function () {
    Log::shouldReceive('warning')->once()->withArgs(function ($message) {
        return str_contains($message, 'Missing assignee_type or assignee_id');
    });

    $snapshot = [
        'assignee_id' => 1,
    ];

    $result = $this->action->handle($snapshot);

    expect($result)->toBeNull();
});

test('returns null and logs warning if role does not exist', function () {
    Log::shouldReceive('warning')->once()->withArgs(function ($message) {
        return str_contains($message, 'does not exist');
    });

    $snapshot = [
        'assignee_type' => 'role',
        'assignee_id' => 'non_existent_role',
    ];

    $result = $this->action->handle($snapshot);

    expect($result)->toBeNull();
});
