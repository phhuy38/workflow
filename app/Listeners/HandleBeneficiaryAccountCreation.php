<?php

namespace App\Listeners;

use App\Events\StepCompleted;
use App\Mail\BeneficiaryAccountCreatedMail;
use App\Mail\BeneficiaryAccountCreationFailedMail;
use App\Mail\BeneficiaryExistingAccountMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HandleBeneficiaryAccountCreation implements ShouldQueue
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

    public function handle(StepCompleted $event): void
    {
        $step = $event->step;
        $step->loadMissing(['instance.creator']);

        $configData = $step->step_snapshot_data['config_data'] ?? [];
        $isAccountCreationStep = $configData['is_account_creation_step'] ?? false;

        if (! $isAccountCreationStep) {
            return;
        }

        $contextData = $step->instance->context_data;
        $beneficiaryEmail = is_array($contextData) ? ($contextData['beneficiary_email'] ?? null) : null;

        if (! $beneficiaryEmail || is_array($beneficiaryEmail)) {
            Log::warning("Account creation step {$step->id} completed, but beneficiary_email is invalid or missing.", ['context' => $contextData]);

            return;
        }

        try {
            $user = User::where('email', $beneficiaryEmail)->first();
            $isNewOrNeverLogged = false;

            if (! $user) {
                $password = Str::password(12);
                $user = User::create([
                    'full_name' => "Beneficiary - {$step->instance->name}",
                    'email' => $beneficiaryEmail,
                    'password' => Hash::make($password),
                    'requires_password_reset' => true,
                    'is_active' => true,
                ]);

                $user->assignRole('beneficiary');
                $isNewOrNeverLogged = true;
            } elseif ($user->requires_password_reset && is_null($user->last_login_at)) {
                // Retry scenario: user was created but mail failed, they never logged in. Regenerate password.
                $password = Str::password(12);
                $user->update([
                    'password' => Hash::make($password),
                ]);
                $isNewOrNeverLogged = true;
            }

            if ($isNewOrNeverLogged) {
                Mail::to($user->email)->send(new BeneficiaryAccountCreatedMail($user, $password, $step->instance));
            } else {
                Mail::to($user->email)->send(new BeneficiaryExistingAccountMail($user, $step->instance));
            }

            // Link instance to beneficiary
            $step->instance->update(['created_for' => $user->id]);

        } catch (\Throwable $e) {
            Log::error("Failed to process Beneficiary Account Creation for {$beneficiaryEmail}", ['exception' => $e]);

            if ($step->instance->creator && $step->instance->creator->email) {
                Mail::to($step->instance->creator->email)->send(new BeneficiaryAccountCreationFailedMail($step->instance, $beneficiaryEmail));
            }

            throw $e;
        }
    }
}
