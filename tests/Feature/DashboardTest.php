<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepExecution;
use App\Models\User;
use App\States\ProcessInstance\Completed;
use Database\Seeders\RequiredDataSeeder;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin can visit the dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('manager can visit the dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('process_designer can visit the dashboard', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('process_designer');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('executor cannot visit the manager dashboard section', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('executor');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('can_view_manager_dashboard', false)
    );
});

test('beneficiary cannot visit the manager dashboard section', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('beneficiary');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('can_view_manager_dashboard', false)
    );
});

test('manager sees sorted instances by traffic light status', function () {
    $this->seed(RequiredDataSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'T', 'created_by' => $manager->id, 'is_published' => true]);

    $instanceNormal = ProcessInstance::create(['name' => 'Normal', 'template_id' => $template->id, 'status' => 'running', 'launched_by' => $manager->id, 'template_snapshot_data' => []]);
    StepExecution::create(['instance_id' => $instanceNormal->id, 'name' => '1', 'order' => 1, 'status' => 'in_progress', 'assigned_to' => $manager->id, 'step_snapshot_data' => [], 'deadline_at' => now()->addMinutes(600)]);

    $instanceWarning = ProcessInstance::create(['name' => 'Warning', 'template_id' => $template->id, 'status' => 'running', 'launched_by' => $manager->id, 'template_snapshot_data' => []]);
    StepExecution::create(['instance_id' => $instanceWarning->id, 'name' => '1', 'order' => 1, 'status' => 'pending', 'assigned_to' => $manager->id, 'step_snapshot_data' => [], 'deadline_at' => now()->addMinutes(600)]);

    $instanceCritical = ProcessInstance::create(['name' => 'Critical', 'template_id' => $template->id, 'status' => 'running', 'launched_by' => $manager->id, 'template_snapshot_data' => []]);
    StepExecution::create(['instance_id' => $instanceCritical->id, 'name' => '1', 'order' => 1, 'status' => 'in_progress', 'assigned_to' => $manager->id, 'step_snapshot_data' => [], 'deadline_at' => now()->addMinutes(10)]);

    // fast forward 65 minutes
    $this->travelTo(now()->addMinutes(65));

    $response = $this->actingAs($manager)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('instances', 3)
        ->where('instances.0.name', 'Critical')
        ->where('instances.1.name', 'Warning')
        ->where('instances.2.name', 'Normal')
    );
    $this->travelBack();
});

test('manager can filter instances', function () {
    $this->seed(RequiredDataSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor1 = User::factory()->create();
    $executor1->assignRole('executor');

    $executor2 = User::factory()->create();
    $executor2->assignRole('executor');

    $template1 = ProcessTemplate::create(['name' => 'T1', 'created_by' => $manager->id, 'is_published' => true]);
    $template2 = ProcessTemplate::create(['name' => 'T2', 'created_by' => $manager->id, 'is_published' => true]);

    // Instance 1: T1, Running, Executor 1, name: "Alpha"
    $instance1 = ProcessInstance::create(['name' => 'Alpha', 'template_id' => $template1->id, 'status' => 'running', 'launched_by' => $manager->id, 'template_snapshot_data' => []]);
    StepExecution::create(['instance_id' => $instance1->id, 'name' => '1', 'order' => 1, 'status' => 'in_progress', 'assigned_to' => $executor1->id, 'step_snapshot_data' => [], 'deadline_at' => now()->addMinutes(600)]);

    // Instance 2: T2, Running, Executor 2, name: "Beta"
    $instance2 = ProcessInstance::create(['name' => 'Beta', 'template_id' => $template2->id, 'status' => 'running', 'launched_by' => $manager->id, 'template_snapshot_data' => []]);
    StepExecution::create(['instance_id' => $instance2->id, 'name' => '1', 'order' => 1, 'status' => 'in_progress', 'assigned_to' => $executor2->id, 'step_snapshot_data' => [], 'deadline_at' => now()->addMinutes(600)]);

    // Instance 3: T1, Completed, Executor 1, name: "Gamma"
    $instance3 = ProcessInstance::create(['name' => 'Gamma', 'template_id' => $template1->id, 'status' => 'completed', 'launched_by' => $manager->id, 'template_snapshot_data' => []]);
    StepExecution::create(['instance_id' => $instance3->id, 'name' => '1', 'order' => 1, 'status' => 'completed', 'assigned_to' => $executor1->id, 'step_snapshot_data' => [], 'deadline_at' => now()->addMinutes(600)]);

    // Test default filter (only Running)
    $response = $this->actingAs($manager)->get(route('dashboard'));
    $response->assertInertia(fn ($page) => $page->has('instances', 2));

    // Test filter by template
    $response = $this->actingAs($manager)->get(route('dashboard', ['template_id' => $template2->id]));
    $response->assertInertia(fn ($page) => $page->has('instances', 1)->where('instances.0.name', 'Beta'));

    // Test filter by executor
    $response = $this->actingAs($manager)->get(route('dashboard', ['executor_id' => $executor1->id]));
    $response->assertInertia(fn ($page) => $page->has('instances', 1)->where('instances.0.name', 'Alpha'));

    // Test filter by search
    $response = $this->actingAs($manager)->get(route('dashboard', ['search' => 'alp']));
    $response->assertInertia(fn ($page) => $page->has('instances', 1)->where('instances.0.name', 'Alpha'));

    // Test filter by status (Completed)
    $response = $this->actingAs($manager)->get(route('dashboard', ['status' => Completed::class]));
    $response->assertInertia(fn ($page) => $page->has('instances', 1)->where('instances.0.name', 'Gamma'));

    // Test combined filters (T1 AND Completed)
    $response = $this->actingAs($manager)->get(route('dashboard', ['template_id' => $template1->id, 'status' => Completed::class]));
    $response->assertInertia(fn ($page) => $page->has('instances', 1)->where('instances.0.name', 'Gamma'));
});
