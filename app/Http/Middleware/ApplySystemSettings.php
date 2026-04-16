<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class ApplySystemSettings
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Schema::hasTable('system_settings')) {
            return $next($request);
        }

        // Cache all settings for 1 hour to reduce DB load. Invalidated when settings updated.
        $settings = Cache::remember('system_settings', 3600, function () {
            return SystemSetting::all()->pluck('value', 'key');
        });

        if ($settings->get('smtp_host')) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $settings->get('smtp_host'));
            Config::set('mail.mailers.smtp.port', (int) $settings->get('smtp_port', 587));
            Config::set('mail.mailers.smtp.username', $settings->get('smtp_username'));

            // Decrypt password if encrypted, otherwise use as-is (backward compat)
            $password = $settings->get('smtp_password', '');
            if ($password && str_starts_with($password, 'eyJpdiI6')) {
                try {
                    $password = Crypt::decryptString($password);
                } catch (\Exception $e) {
                    // If decryption fails, use as-is (plaintext fallback for migration period)
                }
            }
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $settings->get('smtp_encryption', 'tls'));
        }

        if ($settings->get('smtp_from_address')) {
            Config::set('mail.from.address', $settings->get('smtp_from_address'));
            // Use configured from_name or fall back to app name
            Config::set('mail.from.name', $settings->get('smtp_from_name') ?? config('app.name'));
        }

        // Apply session lifetime — guard against zero or non-numeric values
        $lifetime = $settings->get('session_lifetime');
        if ($lifetime && is_numeric($lifetime) && (int) $lifetime > 0) {
            Config::set('session.lifetime', (int) $lifetime);
        }

        return $next($request);
    }
}
