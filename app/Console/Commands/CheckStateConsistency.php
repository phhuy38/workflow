<?php

namespace App\Console\Commands;

use App\Mail\StateConsistencyAlertMail;
use App\Models\ProcessInstance;
use App\Models\User;
use App\States\ProcessInstance\Completed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckStateConsistency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-state-consistency';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and alert for inconsistencies in process instance states.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ProcessInstance::where('status', 'running')
            ->has('stepExecutions')
            ->whereDoesntHave('stepExecutions', function ($query) {
                $query->whereIn('status', ['pending', 'in_progress', 'escalated']);
            })
            ->chunk(100, function ($instances) {
                $admins = User::role('admin')->get();

                foreach ($instances as $instance) {
                    try {
                        $instance->status->transitionTo(Completed::class);
                        $instance->update(['completed_at' => now()]);

                        activity()
                            ->performedOn($instance)
                            ->log('completed (auto-consistency)');

                        foreach ($admins as $admin) {
                            if ($admin->email) {
                                Mail::to($admin->email)->queue(new StateConsistencyAlertMail($instance));
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error("Failed to process consistency check for instance {$instance->id}", ['exception' => $e]);
                    }
                }
            });
    }
}
