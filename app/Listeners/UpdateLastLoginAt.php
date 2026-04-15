<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLoginAt
{
    public function handle(Login $event): void
    {
        $event->user->updateQuietly(['last_login_at' => now()]);
    }
}
