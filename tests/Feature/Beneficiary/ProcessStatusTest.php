<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

test('beneficiary can see their own instances in the index page', function () {
    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiary');

    $otherUser = User::factory()->create();
    $otherUser->assignRole('beneficiary');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);

    // Instance for our beneficiary
    $myInstance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'My Instance',
        'launched_by' => $manager->id,
        'status' => 'running',
        'created_for' => $beneficiary->id,
        'template_snapshot_data' => [],
    ]);

    // Instance for other user
    $otherInstance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Other Instance',
        'launched_by' => $manager->id,
        'status' => 'running',
        'created_for' => $otherUser->id,
        'template_snapshot_data' => [],
    ]);

    $response = actingAs($beneficiary)->get(route('process-instances.index'));
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Instances/Index')
        ->has('instances', 1)
        ->where('instances.0.name', 'My Instance')
    );
});

test('beneficiary can view their own instance detail', function () {
    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiary');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);

    $myInstance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'My Instance',
        'launched_by' => $manager->id,
        'status' => 'running',
        'created_for' => $beneficiary->id,
        'template_snapshot_data' => [],
    ]);

    $response = actingAs($beneficiary)->get(route('process-instances.show', $myInstance));
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Instances/Show')
        ->where('instance.name', 'My Instance')
    );
});

test('beneficiary gets 403 when viewing someone elses instance', function () {
    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiary');

    $otherUser = User::factory()->create();
    $otherUser->assignRole('beneficiary');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);

    $otherInstance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Other Instance',
        'launched_by' => $manager->id,
        'status' => 'running',
        'created_for' => $otherUser->id,
        'template_snapshot_data' => [],
    ]);

    $response = actingAs($beneficiary)->get(route('process-instances.show', $otherInstance));
    $response->assertForbidden();
});
