<?php

use App\Events\MessageSent;
use App\Listeners\SendNewMessageNotification;
use App\Mail\NewMessageReceivedMail;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepExecution;
use App\Models\StepMessage;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
});

test('beneficiary can ping executor and mail is sent', function () {
    Mail::fake();
    Event::fake([MessageSent::class]);

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiary');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'created_for' => $beneficiary->id, 'status' => 'running', 'template_snapshot_data' => []]);

    $step = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Pending Task', 'order' => 1, 'status' => 'pending', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(60),
    ]);

    $response = actingAs($beneficiary)->post(route('step-messages.store', $step), [
        'body' => 'Xin hỏi tiến độ đến đâu rồi ạ?',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('step_messages', [
        'step_execution_id' => $step->id,
        'sender_id' => $beneficiary->id,
        'recipient_id' => $executor->id,
        'body' => 'Xin hỏi tiến độ đến đâu rồi ạ?',
    ]);

    Event::assertDispatched(MessageSent::class);

    // Call listener manually
    $message = StepMessage::first();
    $listener = new SendNewMessageNotification;
    $listener->handle(new MessageSent($message));

    Mail::assertSent(NewMessageReceivedMail::class, function ($mail) use ($executor) {
        return $mail->hasTo($executor->email);
    });
});

test('executor can reply to beneficiary', function () {
    Mail::fake();
    Event::fake([MessageSent::class]);

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiary');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'created_for' => $beneficiary->id, 'status' => 'running', 'template_snapshot_data' => []]);

    $step = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Pending Task', 'order' => 1, 'status' => 'pending', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(60),
    ]);

    $response = actingAs($executor)->post(route('step-messages.store', $step), [
        'body' => 'Tôi đang xử lý nhé.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('step_messages', [
        'step_execution_id' => $step->id,
        'sender_id' => $executor->id,
        'recipient_id' => $beneficiary->id,
        'body' => 'Tôi đang xử lý nhé.',
    ]);

    Event::assertDispatched(MessageSent::class);
});

test('unauthorized user gets 403 when trying to ping', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiary');

    $otherBeneficiary = User::factory()->create();
    $otherBeneficiary->assignRole('beneficiary');

    $template = ProcessTemplate::create(['name' => 'Template', 'created_by' => $manager->id, 'is_published' => true]);
    $instance = ProcessInstance::create(['template_id' => $template->id, 'name' => 'Instance', 'launched_by' => $manager->id, 'created_for' => $beneficiary->id, 'status' => 'running', 'template_snapshot_data' => []]);

    $step = StepExecution::create([
        'instance_id' => $instance->id, 'name' => 'Pending Task', 'order' => 1, 'status' => 'pending', 'assigned_to' => $executor->id, 'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(60),
    ]);

    $response = actingAs($otherBeneficiary)->post(route('step-messages.store', $step), [
        'body' => 'Cho tôi hỏi ké...',
    ]);

    $response->assertForbidden();
});
