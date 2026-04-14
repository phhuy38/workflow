<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateUser
{
    public function __invoke(Request $request): ?User
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return null;
        }

        // is_active = false: trả về null (thông báo lỗi giống invalid credentials — tránh user enumeration)
        if (! $user->is_active) {
            return null;
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }
}
