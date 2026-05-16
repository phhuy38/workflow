<?php

namespace App\Actions\Process;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class ResolveStepAssignee
{
    /**
     * Resolves the user ID to assign a step to, based on the assignee_type and assignee_id.
     *
     * @param  array  $stepSnapshot  The step definition data from the snapshot.
     * @return int|null The User ID, or null if no appropriate user is found.
     */
    public function handle(array $stepSnapshot): ?int
    {
        $type = $stepSnapshot['assignee_type'] ?? null;
        $idOrRole = $stepSnapshot['assignee_id'] ?? null;

        if (empty($type) || is_null($idOrRole) || $idOrRole === '') {
            Log::warning('ResolveStepAssignee: Missing assignee_type or assignee_id', ['step' => $stepSnapshot]);

            return null;
        }

        if ($type === 'user') {
            // Direct user assignment - validate existence
            $user = User::find($idOrRole);
            if ($user) {
                return $user->id;
            }
            Log::warning("ResolveStepAssignee: User ID '{$idOrRole}' not found.", ['step' => $stepSnapshot]);

            return null;
        }

        if ($type === 'role') {
            try {
                $user = User::role($idOrRole)->inRandomOrder()->first();

                if ($user) {
                    return $user->id;
                }

                Log::warning("ResolveStepAssignee: No users found with role '{$idOrRole}' for step.", ['step' => $stepSnapshot]);
            } catch (RoleDoesNotExist $e) {
                Log::warning("ResolveStepAssignee: Role '{$idOrRole}' does not exist.", ['step' => $stepSnapshot]);
            }

            return null;
        }

        Log::warning("ResolveStepAssignee: Unknown assignee_type '{$type}'", ['step' => $stepSnapshot]);

        return null;
    }
}
