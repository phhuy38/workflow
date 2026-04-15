<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateUser
{
    // Pre-computed bcrypt hash used to prevent timing-based user enumeration (P1).
    // Running a dummy hash when the user is not found ensures the response time
    // is indistinguishable from a failed password check for a real user.
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function __invoke(Request $request): ?User
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            // Always run a hash check to prevent timing-based user enumeration.
            Hash::check($request->password, self::DUMMY_HASH);

            return null;
        }

        if (! Hash::check($request->password, $user->password)) {
            return null;
        }

        // is_active = false: return null — same error as wrong password, no user enumeration (AC6, ADR-038).
        if (! $user->is_active) {
            return null;
        }

        // last_login_at is updated via the Authenticated event listener (UpdateLastLoginAt).
        return $user;
    }
}
