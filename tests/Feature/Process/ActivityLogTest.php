<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

test('manager sees full activity log timeline on instance show', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create([
        'name' => 'Timeline Test',
        'created_by' => 1,
        'is_published' => true,
    ]);

    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Step 1',
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => $executor->id,
        'duration_hours' => 24,
    ]);

    // 1. Launch (creates Instance log)
    $this->actingAs($manager)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'Instance Timeline',
            'context_data' => [],
        ]);

    $instance = ProcessInstance::where('name', 'Instance Timeline')->first();
    $step = $instance->stepExecutions()->first();

    // Give it a tiny sleep to ensure order of created_at
    sleep(1);

    // 2. Acknowledge (creates Step log)
    $this->actingAs($executor)
        ->post(route('step-executions.acknowledge', $step));

    sleep(1);

    // 3. Complete (creates Step log and Instance complete log)
    $this->actingAs($executor)
        ->post(route('step-executions.complete', $step), [
            'completion_notes' => 'Done',
        ]);

    // Now visit show page as manager
    $response = $this->actingAs($manager)
        ->get(route('process-instances.show', $instance));

    $response->assertOk();

    // Check Inertia props
    $response->assertInertia(fn ($page) => $page
        ->has('activities', 4) // 1 launch, 1 acknowledge, 1 step complete, 1 instance complete
        ->where('can.view_full_log', true)
    );

    // Get activities from inertia props to check order
    $activities = $response->viewData('page')['props']['activities'];

    // The first activity should be the most recent one (instance complete)
    expect($activities[0]['description'])->toBe('Hoàn thành');
    expect($activities[0]['subject_type'])->toBe('Quy trình');

    // The last activity should be the oldest one (instance created)
    expect($activities[3]['description'])->toBe('Khởi tạo');
    expect($activities[3]['subject_type'])->toBe('Quy trình');
});

test('executor cannot see full activity log timeline', function () {
    $this->withoutExceptionHandling();
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create([
        'name' => 'Timeline Security Test',
        'created_by' => 1,
        'is_published' => true,
    ]);

    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Step 1',
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => $executor->id,
        'duration_hours' => 24,
    ]);

    $this->actingAs($manager)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'Instance Timeline Security',
            'context_data' => [],
        ]);

    $instance = ProcessInstance::where('name', 'Instance Timeline Security')->first();

    // Visit show page as executor
    $response = $this->actingAs($executor)
        ->get(route('process-instances.show', $instance));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->has('activities', 0) // Should be empty
        ->where('can.view_full_log', false)
    );
});
