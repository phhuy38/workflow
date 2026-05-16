<?php

namespace App\Listeners;

use App\Events\ProcessLaunched;
use App\Mail\PreAccountBeneficiaryWelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBeneficiaryWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public bool $deleteWhenMissingModels = true;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function viaConnection(): string
    {
        return config('queue.default');
    }

    public function viaQueue(): string
    {
        return 'notifications';
    }

    public function handle(ProcessLaunched $event): void
    {
        $event->instance->loadMissing('creator');

        $contextData = $event->instance->context_data;
        $beneficiaryEmail = is_array($contextData) ? ($contextData['beneficiary_email'] ?? null) : null;

        if (! $beneficiaryEmail) {
            return;
        }

        $emailString = is_array($beneficiaryEmail) ? implode(', ', $beneficiaryEmail) : $beneficiaryEmail;

        try {
            Mail::to($beneficiaryEmail)->send(new PreAccountBeneficiaryWelcomeMail($event->instance));
        } catch (\Throwable $e) {
            Log::error("Failed to send Beneficiary Welcome email to {$emailString}", ['exception' => $e]);
            throw $e;
        }
    }
}
