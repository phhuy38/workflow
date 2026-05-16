<?php

namespace App\Console\Commands;

use App\Mail\ApproachingDeadlineAlertMail;
use App\Mail\UnacknowledgedStepAlertMail;
use App\Models\StepExecution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckApproachingDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-approaching-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and alert for steps nearing their deadline or unacknowledged steps.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->checkUnacknowledgedSteps();
        $this->checkApproachingDeadlines();
    }

    private function checkUnacknowledgedSteps(): void
    {
        $oneHourAgo = now()->subHour();

        StepExecution::with(['instance.creator', 'assignee'])
            ->where('status', 'pending')
            ->where('created_at', '<=', $oneHourAgo)
            ->whereNull('unacknowledged_notified_at')
            ->chunk(100, function ($steps) {
                foreach ($steps as $step) {
                    try {
                        $manager = $step->instance->creator;
                        if ($manager && $manager->email) {
                            Mail::to($manager->email)->queue(new UnacknowledgedStepAlertMail($step));
                        }
                        $step->update(['unacknowledged_notified_at' => now()]);
                    } catch (\Throwable $e) {
                        Log::error("Failed to process unacknowledged step {$step->id}", ['exception' => $e]);
                    }
                }
            });
    }

    private function checkApproachingDeadlines(): void
    {
        StepExecution::with(['instance.creator', 'assignee'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('deadline_at')
            ->whereNull('deadline_notified_at')
            ->chunk(100, function ($steps) {
                foreach ($steps as $step) {
                    try {
                        $now = now();
                        $deadline = $step->deadline_at;
                        $durationHours = $step->step_snapshot_data['duration_hours'] ?? 24;
                        $totalDurationSeconds = $durationHours * 3600;

                        $thresholdTime = $deadline->copy()->subSeconds($totalDurationSeconds * 0.3);

                        if ($now->greaterThanOrEqualTo($thresholdTime)) {
                            $executor = $step->assignee;
                            $manager = $step->instance->creator;

                            if ($executor && $executor->email) {
                                Mail::to($executor->email)->queue(new ApproachingDeadlineAlertMail($step));
                            }

                            if ($manager && $manager->email && $manager->id !== $executor?->id) {
                                Mail::to($manager->email)->queue(new ApproachingDeadlineAlertMail($step));
                            }

                            $step->update(['deadline_notified_at' => now()]);
                        }
                    } catch (\Throwable $e) {
                        Log::error("Failed to process approaching deadline step {$step->id}", ['exception' => $e]);
                    }
                }
            });
    }
}
