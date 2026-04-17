<?php

use App\Models\ProcessTemplate;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;

// ─── AC1: Process Designer xem danh sách templates ──────────────────────────

test('process designer can access template index', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $this->actingAs($designer)
        ->get(route('process-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Templates/Index'));
});

test('template list shows name, status, step count, created_at', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    ProcessTemplate::create([
        'name' => 'Quy trình onboarding',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->get(route('process-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Templates/Index')
            ->has('templates.0', fn ($template) => $template
                ->where('name', 'Quy trình onboarding')
                ->has('is_published')
                ->has('step_count')
                ->has('created_at')
                ->etc()
            )
        );
});

test('admin can also access template index', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('process-templates.index'))
        ->assertOk();
});

// ─── AC2: Tạo template mới ──────────────────────────────────────────────────

test('process designer can create a new template', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $this->actingAs($designer)
        ->post(route('process-templates.store'), [
            'name' => 'Template mới',
            'description' => 'Mô tả template',
        ])
        ->assertRedirect();

    $template = ProcessTemplate::where('name', 'Template mới')->first();
    expect($template)->not->toBeNull();
    expect($template->is_published)->toBeFalse();
    expect($template->created_by)->toBe($designer->id);
});

test('after creating template, user is redirected to show page', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $response = $this->actingAs($designer)
        ->post(route('process-templates.store'), [
            'name' => 'Template redirect test',
        ]);

    $template = ProcessTemplate::where('name', 'Template redirect test')->first();
    $response->assertRedirect(route('process-templates.show', $template));
});

test('new template is created with draft status', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $this->actingAs($designer)
        ->post(route('process-templates.store'), [
            'name' => 'Draft template',
        ]);

    $template = ProcessTemplate::where('name', 'Draft template')->first();
    expect($template->is_published)->toBeFalse();
    expect($template->published_at)->toBeNull();
});

// ─── AC3: RBAC Protection ───────────────────────────────────────────────────

test('non-designer gets 403 on template index', function () {
    $this->seed(RequiredDataSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get(route('process-templates.index'))
        ->assertForbidden();
});

test('executor gets 403 on template index', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $this->actingAs($executor)
        ->get(route('process-templates.index'))
        ->assertForbidden();
});

test('beneficiary gets 403 on template index', function () {
    $this->seed(RequiredDataSeeder::class);
    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiary');

    $this->actingAs($beneficiary)
        ->get(route('process-templates.index'))
        ->assertForbidden();
});

test('guest is redirected from template index', function () {
    $this->get(route('process-templates.index'))
        ->assertRedirect(route('login'));
});

test('non-designer gets 403 on template store', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $this->actingAs($executor)
        ->post(route('process-templates.store'), ['name' => 'Hack attempt'])
        ->assertForbidden();
});

test('process designer can show template', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Template to view',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->get(route('process-templates.show', $template))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Templates/Show'));
});

test('non-designer gets 403 on template show', function () {
    $this->seed(RequiredDataSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Template show 403',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($manager)
        ->get(route('process-templates.show', $template))
        ->assertForbidden();
});

// ─── AC4: Unique Name Validation ────────────────────────────────────────────

test('cannot create template with duplicate name', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    ProcessTemplate::create([
        'name' => 'Existing Template',
        'created_by' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->post(route('process-templates.store'), [
            'name' => 'Existing Template',
        ])
        ->assertSessionHasErrors(['name']);
});

test('duplicate name error message is in vietnamese', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    ProcessTemplate::create([
        'name' => 'Duplicate Name Test',
        'created_by' => $designer->id,
    ]);

    $response = $this->actingAs($designer)
        ->post(route('process-templates.store'), [
            'name' => 'Duplicate Name Test',
        ]);

    $response->assertSessionHasErrors(['name' => 'Tên template đã tồn tại.']);
});

test('soft-deleted template name can be reused', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Deleted Template',
        'created_by' => $designer->id,
    ]);
    $template->delete();

    $this->actingAs($designer)
        ->post(route('process-templates.store'), [
            'name' => 'Deleted Template',
        ])
        ->assertSessionHasNoErrors();
});

test('name is required when creating template', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $this->actingAs($designer)
        ->post(route('process-templates.store'), [
            'name' => '',
        ])
        ->assertSessionHasErrors(['name']);
});

// ─── Security: Template does not appear after soft delete ───────────────────

test('deleted templates do not appear in index', function () {
    $this->seed(RequiredDataSeeder::class);
    $designer = User::factory()->create();
    $designer->assignRole('process_designer');

    $template = ProcessTemplate::create([
        'name' => 'Template to delete',
        'created_by' => $designer->id,
    ]);
    $template->delete();

    $this->actingAs($designer)
        ->get(route('process-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('templates', [])
        );
});
