<?php

use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

// ─── Task 1: Publish Logic ──────────────────────────────────────────────────

test('designer can publish a valid template', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Valid Template',
        'created_by' => $designer->id,
    ]);

    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Step 1',
        'order' => 1,
        'assignee_type' => 'user',
        'assignee_id' => $designer->id,
        'duration_hours' => 24,
    ]);

    $this->actingAs($designer)
        ->post(route('process-templates.publish', $template))
        ->assertRedirect();

    $template->refresh();
    expect($template->is_published)->toBeTrue();
    expect($template->published_at)->not->toBeNull();
});

test('cannot publish template without steps', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Empty Template',
        'created_by' => $designer->id,
    ]);

    $response = $this->actingAs($designer)
        ->post(route('process-templates.publish', $template));

    $response->assertSessionHas('error', 'Template phải có ít nhất 1 bước để publish.');
    expect($template->refresh()->is_published)->toBeFalse();
});

test('cannot publish template with incomplete steps', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Incomplete Template',
        'created_by' => $designer->id,
    ]);

    StepDefinition::create([
        'template_id' => $template->id,
        'name' => 'Incomplete Step',
        'order' => 1,
        'assignee_type' => null, // Missing assignee type
        'duration_hours' => 24,
    ]);

    $response = $this->actingAs($designer)
        ->post(route('process-templates.publish', $template));

    $response->assertSessionHas('error', 'Bước 1: chưa có loại người phụ trách.');
    expect($template->refresh()->is_published)->toBeFalse();
});

// ─── Task 2: Unpublish Logic ────────────────────────────────────────────────

test('designer can unpublish a template', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Published Template',
        'is_published' => true,
        'published_at' => now(),
        'created_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->post(route('process-templates.unpublish', $template))
        ->assertRedirect();

    $template->refresh();
    expect($template->is_published)->toBeFalse();
});

// ─── Task 3: Visibility & Permissions ───────────────────────────────────────

test('manager only sees published templates', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    ProcessTemplate::create([
        'name' => 'Draft Template',
        'is_published' => false,
        'created_by' => $designer->id,
    ]);

    ProcessTemplate::create([
        'name' => 'Published Template',
        'is_published' => true,
        'created_by' => $designer->id,
    ]);

    $this->actingAs($manager)
        ->get(route('process-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Templates/Index')
            ->has('templates', 1)
            ->where('templates.0.name', 'Published Template')
        );
});

test('non-publisher cannot publish template', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create([
        'name' => 'My Template',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($executor)
        ->post(route('process-templates.publish', $template))
        ->assertForbidden();
});
