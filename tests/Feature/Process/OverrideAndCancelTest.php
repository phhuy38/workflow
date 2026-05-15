<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use App\Models\StepExecution;
use App\Models\User;
use App\States\ProcessInstance\Cancelled as ProcessCancelled;
use App\States\StepExecution\Skipped as StepSkipped;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

// ─── AC1: Override Step ───────────────────────────────────────────────────────

test('manager can override a step and trigger the next step', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Override Test', 'created_by' => 1]);

    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Step 1',
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => $executor->id,
        'duration_hours' => 24,
    ]);
    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Step 2',
        'order' => 2,
        'assignee_type' => 'user',
        'assignee_id' => $executor->id,
        'duration_hours' => 24,
    ]);

    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [
            'steps' => $template->stepDefinitions->toArray(),
        ],
        'launched_by' => $manager->id,
        'status' => 'running',
    ]);

    $step1 = StepExecution::create([
        'instance_id' => $instance->id,
        'step_definition_id' => $template->stepDefinitions[0]->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'pending',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $response = $this->actingAs($manager)
        ->post(route('step-executions.override', $step1), [
            'reason' => 'Executor is on leave',
        ]);

    $response->assertRedirect()->assertSessionHas('success');

    $step1->refresh();
    expect($step1->status)->toBeInstanceOf(StepSkipped::class);
    expect($step1->completed_by)->toBe($manager->id);
    expect($step1->completion_notes)->toBe('OVERRIDDEN: Executor is on leave');

    // AC3: Activity log check
    $log = Activity::where('subject_id', $step1->id)
        ->where('subject_type', StepExecution::class)
        ->where('description', 'overridden')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($manager->id);
    expect($log->properties['reason'])->toBe('Executor is on leave');

    // Check if step 2 is created
    $step2 = StepExecution::where('instance_id', $instance->id)->where('order', 2)->first();
    expect($step2)->not->toBeNull();
    expect($step2->status->getValue())->toBe('pending');
});

// ─── AC2: Cancel Instance ─────────────────────────────────────────────────────

test('manager can cancel an instance and all active steps are cancelled', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Cancel Test', 'created_by' => 1]);

    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [],
        'launched_by' => $manager->id,
        'status' => 'running',
    ]);

    $step1 = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'in_progress',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $response = $this->actingAs($manager)
        ->post(route('process-instances.cancel', $instance), [
            'reason' => 'Duplicate request',
        ]);

    $response->assertRedirect()->assertSessionHas('success');

    $instance->refresh();
    expect($instance->status)->toBeInstanceOf(ProcessCancelled::class);
    expect($instance->completed_at)->not->toBeNull();

    $step1->refresh();
    expect($step1->status)->toBeInstanceOf(StepSkipped::class);
    expect($step1->completion_notes)->toBe('CANCELLED WITH INSTANCE: Duplicate request');

    // AC3: Activity log checks
    $instanceLog = Activity::where('subject_id', $instance->id)
        ->where('subject_type', ProcessInstance::class)
        ->where('description', 'cancelled')
        ->first();

    expect($instanceLog)->not->toBeNull();
    expect($instanceLog->causer_id)->toBe($manager->id);
    expect($instanceLog->properties['reason'])->toBe('Duplicate request');
});

// ─── AC5: Security/Permissions ────────────────────────────────────────────────

test('non-manager cannot override or cancel', function () {
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'Security Test', 'created_by' => 1]);

    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [],
        'launched_by' => $manager->id,
        'status' => 'running',
    ]);

    $step1 = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'pending',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $this->actingAs($executor)
        ->post(route('step-executions.override', $step1), ['reason' => 'Try override'])
        ->assertForbidden();

    $this->actingAs($executor)
        ->post(route('process-instances.cancel', $instance), ['reason' => 'Try cancel'])
        ->assertForbidden();
});

test('reason is required for override and cancel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'Validation Test', 'created_by' => 1]);

    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [],
        'launched_by' => $manager->id,
        'status' => 'running',
    ]);

    $step1 = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'pending',
        'assigned_to' => 1,
        'step_snapshot_data' => [],
    ]);

    $this->actingAs($manager)
        ->post(route('step-executions.override', $step1), [])
        ->assertSessionHasErrors(['reason']);

    $this->actingAs($manager)
        ->post(route('process-instances.cancel', $instance), [])
        ->assertSessionHasErrors(['reason']);
});
