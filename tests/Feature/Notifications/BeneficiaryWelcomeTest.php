<?php

use App\Events\ProcessLaunched;
use App\Mail\PreAccountBeneficiaryWelcomeMail;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

test('process launched event queues beneficiary welcome mail if email exists', function () {
    Mail::fake();
    Event::fake([ProcessLaunched::class]);

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    
    // Launch instance via route
    $this->actingAs($manager)
        ->post(route('process-instances.store'), [
            'template_id' => $template->id,
            'name' => 'Beneficiary Test Instance',
            'context_data' => [
                'beneficiary_email' => 'test_beneficiary@example.com'
            ],
        ])->assertSessionHasNoErrors();

    // The listener listens to ProcessLaunched, but since we faked the event, we can test the listener directly
    $instance = ProcessInstance::latest('id')->first();
    $event = new ProcessLaunched($instance);
    
    $listener = new \App\Listeners\SendBeneficiaryWelcomeEmail();
    $listener->handle($event);

    Mail::assertSent(PreAccountBeneficiaryWelcomeMail::class, function ($mail) {
        return $mail->hasTo('test_beneficiary@example.com');
    });
});

test('listener does not queue mail if beneficiary email is absent', function () {
    Mail::fake();

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create([
        'template_id' => $template->id,
        'name' => 'No Beneficiary Instance',
        'launched_by' => $manager->id,
        'status' => 'running',
        'template_snapshot_data' => [],
        'context_data' => [] // Empty context data
    ]);

    $event = new ProcessLaunched($instance);
    
    $listener = new \App\Listeners\SendBeneficiaryWelcomeEmail();
    $listener->handle($event);

    Mail::assertNotSent(PreAccountBeneficiaryWelcomeMail::class);
});
