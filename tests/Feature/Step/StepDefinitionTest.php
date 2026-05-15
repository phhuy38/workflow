<?php

use App\Models\ProcessTemplate;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;

// ─── AC2: Add step ────────────────────────────────────────────────────────────

test('designer can add a step to a template', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);

    $this->actingAs($designer)
        ->post(route('step-definitions.store'), [
            'template_id' => $template->id,
            'name' => 'Review Documents',
            'description' => 'Check all submitted documents',
            'assignee_type' => 'role',
            'assignee_id' => null,
            'duration_hours' => 24,
            'is_required' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('step_definitions', [
        'template_id' => $template->id,
        'name' => 'Review Documents',
        'order' => 1,
        'duration_hours' => 24,
    ]);
});

test('step order is appended at end', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);

    $template->stepDefinitions()->create(['name' => 'Step A', 'order' => 1, 'duration_hours' => 24]);
    $template->stepDefinitions()->create(['name' => 'Step B', 'order' => 2, 'duration_hours' => 24]);

    $this->actingAs($designer)
        ->post(route('step-definitions.store'), [
            'template_id' => $template->id,
            'name' => 'Step C',
            'duration_hours' => 8,
            'is_required' => true,
        ]);

    $this->assertDatabaseHas('step_definitions', ['name' => 'Step C', 'order' => 3]);
});

// ─── AC5: Edit step ───────────────────────────────────────────────────────────

test('designer can edit a step', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);
    $step = $template->stepDefinitions()->create([
        'name' => 'Original Name',
        'order' => 1,
        'duration_hours' => 24,
    ]);

    $this->actingAs($designer)
        ->put(route('step-definitions.update', $step), [
            'name' => 'Updated Name',
            'description' => 'New description',
            'assignee_type' => 'department',
            'assignee_id' => null,
            'duration_hours' => 48,
            'is_required' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('step_definitions', [
        'id' => $step->id,
        'name' => 'Updated Name',
        'duration_hours' => 48,
        'is_required' => false,
    ]);
});

// ─── AC4: Delete step + re-index ─────────────────────────────────────────────

test('designer can delete a step and remaining steps are renumbered', function () {
    $this->withoutExceptionHandling();
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);

    $step1 = $template->stepDefinitions()->create(['name' => 'S1', 'order' => 1, 'duration_hours' => 24]);
    $step2 = $template->stepDefinitions()->create(['name' => 'S2', 'order' => 2, 'duration_hours' => 24]);
    $step3 = $template->stepDefinitions()->create(['name' => 'S3', 'order' => 3, 'duration_hours' => 24]);

    $this->actingAs($designer)
        ->delete(route('step-definitions.destroy', $step1))
        ->assertRedirect();

    $this->assertSoftDeleted('step_definitions', ['id' => $step1->id]);
    expect($step2->fresh()->order)->toBe(1);
    expect($step3->fresh()->order)->toBe(2);
});

// ─── AC3: Reorder steps ───────────────────────────────────────────────────────

test('designer can reorder steps via reorder endpoint', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);

    $step1 = $template->stepDefinitions()->create(['name' => 'S1', 'order' => 1, 'duration_hours' => 24]);
    $step2 = $template->stepDefinitions()->create(['name' => 'S2', 'order' => 2, 'duration_hours' => 24]);

    $this->actingAs($designer)
        ->patchJson(route('step-definitions.reorder', $step1), ['new_order' => 2])
        ->assertOk()
        ->assertJsonStructure(['steps']);

    expect($step1->fresh()->order)->toBe(2);
    expect($step2->fresh()->order)->toBe(1);
});

// ─── AC8: Validation ──────────────────────────────────────────────────────────

test('step name is required', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);

    $this->actingAs($designer)
        ->post(route('step-definitions.store'), [
            'template_id' => $template->id,
            'name' => '',
            'duration_hours' => 24,
            'is_required' => true,
        ])
        ->assertSessionHasErrors(['name']);
});

test('step duration must be at least 1', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);

    $this->actingAs($designer)
        ->post(route('step-definitions.store'), [
            'template_id' => $template->id,
            'name' => 'Invalid Step',
            'duration_hours' => 0,
            'is_required' => true,
        ])
        ->assertSessionHasErrors(['duration_hours']);
});

test('assignee_type must be valid enum value', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);

    $this->actingAs($designer)
        ->post(route('step-definitions.store'), [
            'template_id' => $template->id,
            'name' => 'Invalid Assignee Type',
            'duration_hours' => 24,
            'is_required' => true,
            'assignee_type' => 'invalid_type',
        ])
        ->assertSessionHasErrors(['assignee_type']);
});

// ─── RBAC / Security ─────────────────────────────────────────────────────────

test('executor cannot add a step (403)', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'Protected', 'created_by' => $designer->id]);

    $this->actingAs($executor)
        ->post(route('step-definitions.store'), [
            'template_id' => $template->id,
            'name' => 'Unauthorized Step',
            'duration_hours' => 24,
            'is_required' => true,
        ])
        ->assertForbidden();
});

test('executor cannot delete a step (403)', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'Protected2', 'created_by' => $designer->id]);
    $step = $template->stepDefinitions()->create(['name' => 'S', 'order' => 1, 'duration_hours' => 24]);

    $this->actingAs($executor)
        ->delete(route('step-definitions.destroy', $step))
        ->assertForbidden();
});

test('guest is redirected from step store', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'GuestTest', 'created_by' => $designer->id]);

    $this->post(route('step-definitions.store'), [
        'template_id' => $template->id,
        'name' => 'x',
        'duration_hours' => 1,
        'is_required' => true,
    ])->assertRedirect(route('login'));
});

// ─── AC1: Template show includes steps ────────────────────────────────────────

test('template show page receives steps in props', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');
    $template = ProcessTemplate::create(['name' => 'T'.uniqid(), 'created_by' => $designer->id]);
    $template->stepDefinitions()->create(['name' => 'First Step', 'order' => 1, 'duration_hours' => 24]);

    $this->actingAs($designer)
        ->get(route('process-templates.show', $template))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Templates/Show')
            ->has('steps', 1)
            ->has('steps.0', fn ($step) => $step
                ->where('name', 'First Step')
                ->where('order', 1)
                ->etc()
            )
        );
});
