<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Epic 1: tất cả roles → dashboard
        // TODO Epic 5: executor → route('executor.inbox')
        // TODO Epic 7: beneficiary → route('beneficiary.index')
        return redirect()->intended(route('dashboard'));
    }
}
