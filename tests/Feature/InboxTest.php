<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepExecution;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

test('guest is redirected to login', function () {
    $this->get(route('inbox.index'))->assertRedirect(route('login'));
});

test('executor can access inbox', function () {
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $response = actingAs($executor)->get(route('inbox.index'));
    $response->assertOk();
});

test('inbox displays correctly sorted tasks', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'status' => 'running', 'template_snapshot_data' => []]);

    // Pending (Gray)
    $stepPending = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Pending Task', 'order' => 1, 'status' => 'pending', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(600)
    ]);
    $stepPending->created_at = now()->subMinutes(10);
    $stepPending->save(['timestamps' => false]);

    // In Progress (Green)
    $stepInProgress = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'In Progress Task', 'order' => 2, 'status' => 'in_progress', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(600)
    ]);
    $stepInProgress->created_at = now()->subMinutes(10);
    $stepInProgress->save(['timestamps' => false]);

    // Due Soon (Yellow)
    $stepDueSoon = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Due Soon Task', 'order' => 3, 'status' => 'pending', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(25)
    ]);
    $stepDueSoon->created_at = now()->subMinutes(75);
    $stepDueSoon->save(['timestamps' => false]);

    // Overdue (Red)
    $stepOverdue = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Overdue Task', 'order' => 4, 'status' => 'in_progress', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->subMinutes(10)
    ]);
    $stepOverdue->created_at = now()->subMinutes(110);
    $stepOverdue->save(['timestamps' => false]);

    // This one belongs to someone else
    $executor2 = User::factory()->create();
    StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Other Task', 'order' => 5, 'status' => 'pending', 'assigned_to' => $executor2->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(600)
    ]);

    test()->travelTo(now()->addMinutes(65)); // Ensure 1 hour passed for due_soon if we used that logic, wait we use 30% rule.

    $response = actingAs($executor)->get(route('inbox.index'));
    
    $response->assertInertia(fn ($page) => $page
        ->component('Inbox/Index')
        ->has('tasks', 4)
        ->where('tasks.0.name', 'Overdue Task')
        ->where('tasks.1.name', 'Due Soon Task')
        ->where('tasks.2.name', 'In Progress Task')
        ->where('tasks.3.name', 'Pending Task')
    );

    test()->travelBack();
});

test('executor can quick complete a pending task and auto-acknowledge', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'status' => 'running', 'template_snapshot_data' => []]);

    $step = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Pending Task', 'order' => 1, 'status' => 'pending', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(60)
    ]);

    $response = actingAs($executor)->post(route('step-executions.complete', $step), [
        'completion_notes' => 'Quick done',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $step->refresh();
    expect($step->status->getValue())->toBe('completed');
    expect($step->completion_notes)->toBe('Quick done');
});
