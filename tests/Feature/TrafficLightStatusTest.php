<?php

use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepExecution;
use App\Models\User;
use App\Services\InstanceStatusCalculator;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RequiredDataSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->designer = User::factory()->create();
    $this->designer->assignRole('process_designer');
    
    $this->template = ProcessTemplate::create([
        'name' => 'Traffic Light Template',
        'created_by' => $this->designer->id,
        'is_published' => true,
    ]);
});

function createInstanceWithStep(string $status, ?int $createdAt = null, ?int $deadlineAt = null): ProcessInstance
{
    $instance = ProcessInstance::create([
        'template_id' => test()->template->id,
        'name' => 'Instance ' . uniqid(),
        'template_snapshot_data' => [],
        'launched_by' => test()->designer->id,
        'status' => 'running',
    ]);

    $step = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => $status,
        'assigned_to' => test()->designer->id,
        'step_snapshot_data' => [],
        'deadline_at' => $deadlineAt ? now()->addMinutes($deadlineAt) : now()->addHours(24),
    ]);

    $step->created_at = $createdAt ? now()->subMinutes($createdAt) : now();
    $step->save(['timestamps' => false]);

    return $instance;
}

test('returns critical if step is overdue', function () {
    $instance = createInstanceWithStep('pending', createdAt: 60, deadlineAt: -10); // overdue 10 mins
    $instance->refresh();
    expect(InstanceStatusCalculator::calculate($instance))->toBe('critical');
});

test('returns warning if step is pending > 1 hour', function () {
    $instance = createInstanceWithStep('pending', createdAt: null, deadlineAt: 600);
    test()->travelTo(now()->addMinutes(65));
    $instance->refresh();
    expect(InstanceStatusCalculator::calculate($instance))->toBe('warning');
    test()->travelBack();
});

test('returns warning if step deadline is <= 30% of duration', function () {
    // Duration is 100 minutes.
    $instance = ProcessInstance::create([
        'template_id' => $this->template->id,
        'name' => 'Instance 1',
        'template_snapshot_data' => [],
        'launched_by' => $this->designer->id,
        'status' => 'running',
    ]);

    $step = StepExecution::create([
        'instance_id' => $instance->id,
        'name' => 'Step 1',
        'order' => 1,
        'status' => 'in_progress',
        'assigned_to' => $this->designer->id,
        'step_snapshot_data' => [],
        'deadline_at' => now()->addMinutes(100),
    ]);
    
    // Jump forward by 75 minutes. Remaining is 25 minutes (25%)
    test()->travelTo(now()->addMinutes(75));

    $instance->refresh();
    expect(InstanceStatusCalculator::calculate($instance))->toBe('warning');
    test()->travelBack();
});

test('returns normal if step is fine', function () {
    $instance = createInstanceWithStep('in_progress', createdAt: 10, deadlineAt: 600);
    $instance->refresh();
    expect(InstanceStatusCalculator::calculate($instance))->toBe('normal');
});
