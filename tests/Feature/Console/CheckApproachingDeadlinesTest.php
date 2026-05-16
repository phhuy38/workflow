<?php

use App\Mail\ApproachingDeadlineAlertMail;
use App\Mail\StateConsistencyAlertMail;
use App\Mail\UnacknowledgedStepAlertMail;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use App\Models\StepExecution;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

test('check approaching deadlines command sends unacknowledged email after 1 hour', function () {
    Mail::fake();

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'status' => 'running', 'template_snapshot_data' => []]);

    $step = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Notification Step', 'order' => 1, 'status' => 'pending', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(600)
    ]);

    test()->travelTo(now()->addMinutes(30));
    $this->artisan('app:check-approaching-deadlines')->assertSuccessful();
    Mail::assertNotQueued(UnacknowledgedStepAlertMail::class);

    test()->travelTo(now()->addMinutes(35)); // 65 mins total
    $this->artisan('app:check-approaching-deadlines')->assertSuccessful();
    
    Mail::assertQueued(UnacknowledgedStepAlertMail::class, function ($mail) use ($manager) {
        return $mail->hasTo($manager->email);
    });

    // Run again, should not send second email
    Mail::fake();
    $this->artisan('app:check-approaching-deadlines')->assertSuccessful();
    Mail::assertNotQueued(UnacknowledgedStepAlertMail::class);

    test()->travelBack();
});

test('check approaching deadlines command sends deadline email at 30 percent remaining', function () {
    Mail::fake();

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'status' => 'running', 'template_snapshot_data' => []]);

    $step = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Notification Step', 'order' => 1, 'status' => 'in_progress', 'assigned_to' => $executor->id, 
        'step_snapshot_data' => ['duration_hours' => 10], // 10 hours = 600 mins. 30% = 3 hours = 180 mins remaining.
        'deadline_at' => now()->addMinutes(600)
    ]);

    test()->travelTo(now()->addMinutes(400)); // 200 mins remaining. > 180 mins.
    $this->artisan('app:check-approaching-deadlines')->assertSuccessful();
    Mail::assertNotQueued(ApproachingDeadlineAlertMail::class);

    test()->travelTo(now()->addMinutes(30)); // 430 mins passed. 170 mins remaining. < 180 mins.
    $this->artisan('app:check-approaching-deadlines')->assertSuccessful();
    
    Mail::assertQueued(ApproachingDeadlineAlertMail::class, function ($mail) use ($manager) {
        return $mail->hasTo($manager->email);
    });
    Mail::assertQueued(ApproachingDeadlineAlertMail::class, function ($mail) use ($executor) {
        return $mail->hasTo($executor->email);
    });

    // Run again, should not send second email
    Mail::fake();
    $this->artisan('app:check-approaching-deadlines')->assertSuccessful();
    Mail::assertNotQueued(ApproachingDeadlineAlertMail::class);

    test()->travelBack();
});

test('state consistency command completes instance', function () {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'status' => 'running', 'template_snapshot_data' => []]);

    $step = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Notification Step', 'order' => 1, 'status' => 'completed', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(600)
    ]);

    $this->artisan('app:check-state-consistency')->assertSuccessful();
    
    $instance->refresh();
    expect($instance->status->getValue())->toBe('completed');

    Mail::assertQueued(StateConsistencyAlertMail::class, function ($mail) use ($admin) {
        return $mail->hasTo($admin->email);
    });
});
