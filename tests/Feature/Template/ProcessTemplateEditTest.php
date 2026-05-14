<?php

use App\Models\ProcessTemplate;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

// ─── Task 1: Metadata Update ────────────────────────────────────────────────

test('process designer can update template metadata', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Old Name',
        'description' => 'Old Description',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->patch(route('process-templates.update', $template), [
            'name' => 'New Name',
            'description' => 'New Description',
        ])
        ->assertRedirect();

    $template->refresh();
    expect($template->name)->toBe('New Name');
    expect($template->description)->toBe('New Description');
});

test('cannot update template with duplicate name', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    ProcessTemplate::create([
        'name' => 'Existing Name',
        'created_by' => $designer->id,
    ]);

    $template = ProcessTemplate::create([
        'name' => 'My Template',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->patch(route('process-templates.update', $template), [
            'name' => 'Existing Name',
        ])
        ->assertSessionHasErrors(['name']);
});

test('updating with same name is allowed', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Same Name',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->patch(route('process-templates.update', $template), [
            'name' => 'Same Name',
            'description' => 'Updated Description',
        ])
        ->assertSessionHasNoErrors();
});

test('non-designer cannot update template', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create([
        'name' => 'Original Name',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($executor)
        ->patch(route('process-templates.update', $template), [
            'name' => 'Hacked Name',
        ])
        ->assertForbidden();

    expect($template->refresh()->name)->toBe('Original Name');
});

// ─── Delete Functionality (AC6 Part 1) ──────────────────────────────────────

test('process designer can delete a template', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'To Be Deleted',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->delete(route('process-templates.destroy', $template))
        ->assertRedirect(route('process-templates.index'));

    expect(ProcessTemplate::find($template->id))->toBeNull();
    // Verify soft delete if used
    expect(ProcessTemplate::withTrashed()->find($template->id))->not->toBeNull();
});

// ─── Task 3: Delete Protection & Integrity (AC5, AC6) ──────────────────────

test('cannot delete template if instances exist', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Template with Instances',
        'created_by' => $designer->id,
    ]);

    \App\Models\ProcessInstance::create([
        'template_id' => $template->id,
        'template_snapshot_data' => ['name' => $template->name],
        'launched_by' => $designer->id,
    ]);

    $response = $this->actingAs($designer)
        ->delete(route('process-templates.destroy', $template));

    // It should either fail with an error or redirect back with a warning
    // AC6: system từ chối với message: "Template đang có quy trình đang chạy, không thể xóa"
    $response->assertRedirect()
        ->assertSessionHas('error', 'Template đang có quy trình đang chạy, không thể xóa.');

    expect(ProcessTemplate::find($template->id))->not->toBeNull();
});

test('editing template does not affect existing instance snapshots', function () {
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Original Template Name',
        'created_by' => $designer->id,
    ]);

    $instance = \App\Models\ProcessInstance::create([
        'template_id' => $template->id,
        'template_snapshot_data' => ['name' => 'Original Template Name'],
        'launched_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->patch(route('process-templates.update', $template), [
            'name' => 'Updated Template Name',
        ]);

    $instance->refresh();
    expect($instance->template_snapshot_data['name'])->toBe('Original Template Name');
    expect($template->refresh()->name)->toBe('Updated Template Name');
});
