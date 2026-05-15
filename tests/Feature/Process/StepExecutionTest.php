<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use App\Models\StepExecution;
use App\Models\User;
use App\States\ProcessInstance\Completed as ProcessCompleted;
use App\States\StepExecution\Completed as StepCompleted;
use App\States\StepExecution\InProgress;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

// ─── AC1: Acknowledge Logic ──────────────────────────────────────────────────

test('executor can acknowledge a pending step', function () {
    $this->withoutExceptionHandling();
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Test', 'created_by' => 1]);
    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [
            'steps' => $template->stepDefinitions->toArray(),
        ],
        'launched_by' => 1,
        'status' => 'running',
    ]);

    $step = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'pending',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $this->actingAs($executor)
        ->post(route('step-executions.acknowledge', $step))
        ->assertRedirect();

    $step->refresh();
    expect($step->status)->toBeInstanceOf(InProgress::class);
    expect($step->started_at)->not->toBeNull();
});

// ─── AC2 & AC3: Complete Logic ───────────────────────────────────────────────

test('executor can complete an in-progress step and trigger next step', function () {
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Test', 'created_by' => 1]);

    // Setup two step definitions for the template
    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Def 1',
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => $executor->id,
        'duration_hours' => 24,
    ]);
    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Def 2',
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
        'launched_by' => 1,
        'status' => 'running',
    ]);

    $step1 = StepExecution::create([
        'instance_id' => $instance->id,
        'step_definition_id' => $template->stepDefinitions[0]->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'in_progress',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $this->withoutExceptionHandling();
    $this->actingAs($executor)
        ->post(route('step-executions.complete', $step1), [
            'completion_notes' => 'Done perfectly',
        ])
        ->assertRedirect();

    $step1->refresh();
    expect($step1->status)->toBeInstanceOf(StepCompleted::class);
    expect($step1->completed_at)->not->toBeNull();
    expect($step1->completed_by)->toBe($executor->id);
    expect($step1->completion_notes)->toBe('Done perfectly');

    $log = Activity::where('subject_id', $step1->id)
        ->where('subject_type', StepExecution::class)
        ->where('description', 'completed')
        ->first();
    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($executor->id);

    // AC3: Check if next step is created
    $step2 = StepExecution::where('instance_id', $instance->id)->where('order', 2)->first();
    expect($step2)->not->toBeNull();
    expect($step2->status->getValue())->toBe('pending');
});

test('executor can complete an in-progress step and trigger next step with role assignee', function () {
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Test', 'created_by' => 1]);

    // Setup two step definitions for the template
    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Def 1',
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => $executor->id,
        'duration_hours' => 24,
    ]);
    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Def 2',
        'order' => 2,
        'assignee_type' => 'role',
        'assignee_id' => 'executor',
        'duration_hours' => 24,
    ]);

    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [
            'steps' => $template->stepDefinitions->toArray(),
        ],
        'launched_by' => 1,
        'status' => 'running',
    ]);

    $step1 = StepExecution::create([
        'instance_id' => $instance->id,
        'step_definition_id' => $template->stepDefinitions[0]->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'in_progress',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $this->withoutExceptionHandling();
    $this->actingAs($executor)
        ->post(route('step-executions.complete', $step1), [
            'completion_notes' => 'Done perfectly',
        ])
        ->assertRedirect();

    $step1->refresh();
    expect($step1->status)->toBeInstanceOf(StepCompleted::class);

    // AC3: Check if next step is created and assigned via role
    $step2 = StepExecution::where('instance_id', $instance->id)->where('order', 2)->first();
    expect($step2)->not->toBeNull();
    expect($step2->status->getValue())->toBe('pending');
    expect($step2->assigned_to)->not->toBeNull();
});

test('process completes when the last step is finished', function () {
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Test', 'created_by' => 1]);
    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Last Step',
        'order' => 1,
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
        'launched_by' => 1,
        'status' => 'running',
    ]);

    $step = StepExecution::create([
        'instance_id' => $instance->id,
        'step_definition_id' => $template->stepDefinitions[0]->id,
        'name' => 'Last Step',
        'order' => 1,
        'status' => 'in_progress',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $this->actingAs($executor)
        ->post(route('step-executions.complete', $step))
        ->assertRedirect();

    $instance->refresh();
    expect($instance->status)->toBeInstanceOf(ProcessCompleted::class);
    expect($instance->completed_at)->not->toBeNull();
});

// ─── AC6: Security ──────────────────────────────────────────────────────────

test('non-assigned user cannot acknowledge a step', function () {
    $executor = User::factory()->create();
    $otherUser = User::factory()->create();

    $template = ProcessTemplate::create(['name' => 'Test', 'created_by' => 1]);
    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [
            'steps' => $template->stepDefinitions->toArray(),
        ],
        'launched_by' => 1,
        'status' => 'running',
    ]);

    $step = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'pending',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $this->actingAs($otherUser)
        ->post(route('step-executions.acknowledge', $step))
        ->assertForbidden();
});

test('non-assigned user cannot complete a step', function () {
    $executor = User::factory()->create();
    $otherUser = User::factory()->create();

    $template = ProcessTemplate::create(['name' => 'Test', 'created_by' => 1]);
    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [
            'steps' => $template->stepDefinitions->toArray(),
        ],
        'launched_by' => 1,
        'status' => 'running',
    ]);

    $step = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'in_progress',
        'assigned_to' => $executor->id,
        'step_snapshot_data' => [],
    ]);

    $this->actingAs($otherUser)
        ->post(route('step-executions.complete', $step))
        ->assertForbidden();
});
