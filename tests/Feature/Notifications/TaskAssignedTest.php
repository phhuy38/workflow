<?php

use App\Events\TaskAssigned;
use App\Mail\TaskAssignedMail;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use App\Models\StepExecution;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

test('task assigned event triggers mail to be queued', function () {
    Event::fake([TaskAssigned::class]);

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    
    StepDefinition::create([
        'template_id' => $template->id,
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => (string) $executor->id,
        'name' => 'First Step',
        'duration_hours' => 24,
    ]);

    // Launch instance should fire the event and queue the mail
    $response = $this->actingAs($manager)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'Mail Test Instance',
            'context_data' => [],
        ]);

    $response->assertSessionHasNoErrors();

    Event::assertDispatched(TaskAssigned::class, function (TaskAssigned $event) use ($executor) {
        return $event->step->assigned_to === $executor->id && 
               $event->step->name === 'First Step';
    });
});

test('listener queues task assigned email', function () {
    Mail::fake();

    $executor = User::factory()->create();
    $manager = User::factory()->create();
    
    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'status' => 'running', 'template_snapshot_data' => []]);

    $step = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Notification Step', 'order' => 1, 'status' => 'pending', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(60)
    ]);

    $event = new TaskAssigned($step);
    $listener = new \App\Listeners\SendTaskAssignedNotification();
    $listener->handle($event);

    Mail::assertSent(TaskAssignedMail::class, function (TaskAssignedMail $mail) use ($executor) {
        return $mail->hasTo($executor->email) && 
               $mail->step->name === 'Notification Step';
    });
});
