<?php

use App\Events\StepCompleted;
use App\Listeners\HandleBeneficiaryAccountCreation;
use App\Mail\BeneficiaryAccountCreatedMail;
use App\Mail\BeneficiaryExistingAccountMail;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepExecution;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

test('it creates a new beneficiary user and sends welcome mail', function () {
    Mail::fake();

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance',
        'launched_by' => $manager->id,
        'status' => 'running',
        'template_snapshot_data' => [],
        'context_data' => ['beneficiary_email' => 'newuser@example.com'],
    ]);

    $step = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Create Account Step',
        'order' => 1,
        'status' => 'completed',
        'assigned_to' => $manager->id,
        'step_snapshot_data' => ['config_data' => ['is_account_creation_step' => true]],
        'deadline_at' => now()->addMinutes(60),
    ]);

    $event = new StepCompleted($step);
    $listener = new HandleBeneficiaryAccountCreation;
    $listener->handle($event);

    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('beneficiary'))->toBeTrue();
    expect($user->requires_password_reset)->toBe(1);

    $instance->refresh();
    expect($instance->created_for)->toBe($user->id);

    Mail::assertSent(BeneficiaryAccountCreatedMail::class, function ($mail) {
        return $mail->hasTo('newuser@example.com');
    });
});

test('it links existing beneficiary user and sends existing account mail', function () {
    Mail::fake();

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $existingUser->assignRole('beneficiary');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'Instance',
        'launched_by' => $manager->id,
        'status' => 'running',
        'template_snapshot_data' => [],
        'context_data' => ['beneficiary_email' => 'existing@example.com'],
    ]);

    $step = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Create Account Step',
        'order' => 1,
        'status' => 'completed',
        'assigned_to' => $manager->id,
        'step_snapshot_data' => ['config_data' => ['is_account_creation_step' => true]],
        'deadline_at' => now()->addMinutes(60),
    ]);

    $event = new StepCompleted($step);
    $listener = new HandleBeneficiaryAccountCreation;
    $listener->handle($event);

    $instance->refresh();
    expect($instance->created_for)->toBe($existingUser->id);

    Mail::assertSent(BeneficiaryExistingAccountMail::class, function ($mail) {
        return $mail->hasTo('existing@example.com');
    });
});

test('force password reset middleware intercepts correctly', function () {
    $user = User::factory()->create(['requires_password_reset' => true]);

    $response = actingAs($user)->get(route('dashboard'));
    $response->assertRedirect(route('password.force-reset'));

    $response = actingAs($user)->get(route('password.force-reset'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Auth/ForceResetPassword'));

    $response = actingAs($user)->post(route('password.force-reset'), [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->requires_password_reset)->toBe(0);
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});
