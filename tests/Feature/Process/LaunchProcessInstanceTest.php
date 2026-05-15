<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

// ─── Task 1: Infrastructure ──────────────────────────────────────────────────

test('process instance has step executions relationship', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Test Template',
        'created_by' => $designer->id,
    ]);

    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Relationship Test',
        'template_snapshot_data' => [],
        'launched_by' => $designer->id,
        'status' => 'running',
    ]);

    expect($instance->stepExecutions)->toBeInstanceOf(Collection::class);
});

// ─── AC1 & AC2: Launch Logic ───────────────────────────────────────────────

test('manager can launch a process instance from a published template', function () {
    $this->withoutExceptionHandling();
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Published Template',
        'created_by' => $designer->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'First Step',
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => $designer->id,
        'duration_hours' => 24,
    ]);

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $response = $this->actingAs($manager)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'New Instance',
            'context_data' => ['reason' => 'Onboarding'],
        ]);

    $instance = ProcessInstance::where('name', 'New Instance')->first();
    expect($instance)->not->toBeNull();
    $response->assertRedirect(route('process-instances.show', $instance));

    // AC1: Snapshot
    expect($instance->template_snapshot_data)->not->toBeEmpty();
    expect($instance->status->getValue())->toBe('running');

    // AC1 & AC2: First step created and assigned
    $firstStep = $instance->stepExecutions()->where('order', 1)->first();
    expect($firstStep)->not->toBeNull();
    expect($firstStep->status->getValue())->toBe('pending');
    expect($firstStep->assigned_to)->toBe($designer->id);
    expect($firstStep->deadline_at)->not->toBeNull();
});

test('manager can launch an instance with a role assigned step', function () {
    $this->withoutExceptionHandling();
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Published Role Template',
        'created_by' => $designer->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Role Step',
        'order' => 1,
        'assignee_type' => 'role',
        'assignee_id' => 'executor', // Assuming 'executor' role exists from seeder
        'duration_hours' => 24,
    ]);

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'Role Instance',
            'context_data' => [],
        ]);

    $instance = ProcessInstance::where('name', 'Role Instance')->first();
    expect($instance)->not->toBeNull();

    $firstStep = $instance->stepExecutions()->first();
    expect($firstStep)->not->toBeNull();
    // ResolveStepAssignee should pick the first user with 'executor' role, which might be our newly created user
    // or one from the seeder. As long as it is an ID, it's working.
    expect($firstStep->assigned_to)->not->toBeNull();
});

// ─── AC3: Template List Visibility ──────────────────────────────────────────

test('launch form only shows published templates', function () {
    $this->withoutExceptionHandling();
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    ProcessTemplate::create(['name' => 'Published', 'is_published' => true, 'created_by' => $designer->id]);
    ProcessTemplate::create(['name' => 'Draft', 'is_published' => false, 'created_by' => $designer->id]);

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get(route('process-instances.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('templates', 1)
            ->where('templates.0.name', 'Published')
        );
});

// ─── AC4: RBAC Protection ───────────────────────────────────────────────────

test('non-manager/admin cannot launch instance', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Published Template',
        'created_by' => $designer->id,
        'is_published' => true,
    ]);

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $this->actingAs($executor)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'Unauthorized Instance',
        ])
        ->assertForbidden();
});

// ─── AC5: Activity Log ──────────────────────────────────────────────────────

test('launching an instance records an activity log', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Log Template',
        'created_by' => $designer->id,
        'is_published' => true,
    ]);

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'Logged Instance',
        ]);

    $instance = ProcessInstance::where('name', 'Logged Instance')->first();

    $log = Activity::where('subject_id', $instance->id)
        ->where('subject_type', ProcessInstance::class)
        ->where('description', 'created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($manager->id);
});
