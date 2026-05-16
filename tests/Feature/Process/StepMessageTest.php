<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepExecution;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

test('manager can send ping to executor', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'status' => 'running', 'template_snapshot_data' => []]);
    $step = StepExecution::create(['instance_id' => $instance->id, 'name' => 'Step 1', 'order' => 1, 'status' => 'in_progress', 'assigned_to' => $executor->id, 'step_snapshot_data' => []]);

    $response = $this->actingAs($manager)->post(route('step-messages.store', $step), [
        'body' => 'Please hurry up',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('step_messages', [
        'step_execution_id' => $step->id,
        'sender_id' => $manager->id,
        'recipient_id' => $executor->id,
        'body' => 'Please hurry up',
    ]);
});

test('manager cannot send ping to instance launched by someone else', function () {
    $manager1 = User::factory()->create();
    $manager1->assignRole('manager');

    $manager2 = User::factory()->create();
    $manager2->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager1->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager1->id, 'status' => 'running', 'template_snapshot_data' => []]);
    $step = StepExecution::create(['instance_id' => $instance->id, 'name' => 'Step 1', 'order' => 1, 'status' => 'in_progress', 'assigned_to' => $executor->id, 'step_snapshot_data' => []]);

    $response = $this->actingAs($manager2)->post(route('step-messages.store', $step), [
        'body' => 'Please hurry up',
    ]);

    $response->assertForbidden();
});

test('executor can reply to manager', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'status' => 'running', 'template_snapshot_data' => []]);
    $step = StepExecution::create(['instance_id' => $instance->id, 'name' => 'Step 1', 'order' => 1, 'status' => 'in_progress', 'assigned_to' => $executor->id, 'step_snapshot_data' => []]);

    $response = $this->actingAs($executor)->post(route('step-messages.store', $step), [
        'body' => 'I am working on it',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('step_messages', [
        'step_execution_id' => $step->id,
        'sender_id' => $executor->id,
        'recipient_id' => $manager->id,
        'body' => 'I am working on it',
    ]);
});
