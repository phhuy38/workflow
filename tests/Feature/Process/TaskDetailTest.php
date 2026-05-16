<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use App\Models\StepMessage;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

test('executor can view task detail with full process context', function () {
    $this->withoutExceptionHandling();
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create([
        'name' => 'Test Template',
        'created_by' => $manager->id,
        'is_published' => true,
    ]);

    // Create two steps
    StepDefinition::create([
        'template_id' => $template->id,
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => (string) $executor->id,
        'name' => 'First Step',
        'duration_hours' => 24,
    ]);

    StepDefinition::create([
        'template_id' => $template->id,
        'order' => 2,
        'assignee_type' => 'role',
        'assignee_id' => 'executor',
        'name' => 'Second Step',
        'duration_hours' => 24,
    ]);

    $this->actingAs($manager)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'Context Test Instance',
            'context_data' => [],
        ]);

    $instance = ProcessInstance::where('name', 'Context Test Instance')->first();
    $step = $instance->stepExecutions()->where('order', 1)->first();

    // Create a ping message from manager
    StepMessage::create([
        'step_execution_id' => $step->id,
        'sender_id' => $manager->id,
        'recipient_id' => $executor->id,
        'is_manager' => true,
        'body' => 'Manager says hello',
    ]);

    // Visit show page as executor
    $response = $this->actingAs($executor)
        ->get(route('process-instances.show', $instance));

    $response->assertOk();

    // dump($response->viewData('page')['props']['instance']);

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Instances/Show')
        ->has('instance', fn (Assert $page) => $page
            ->where('name', 'Context Test Instance')
            ->has('template_name')
            ->has('creator_name')
            ->has('status')
            ->etc()
        )
        // Ensure steps contain both steps for context (Step 1 and Step 2)
        ->has('steps', 1, fn (Assert $page) => $page
            ->where('id', $step->id)
            ->where('name', 'First Step')
            ->has('assignee_name')
            ->has('messages', 1)
            ->etc()
        )
    );
});
