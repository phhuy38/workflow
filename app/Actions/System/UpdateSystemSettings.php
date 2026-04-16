<?php

namespace App\Actions\System;

use App\Models\SystemSetting;
use App\Models\User;

class UpdateSystemSettings
{
    // Sentinel used by frontend to indicate "don't change password"
    // Using Laravel's cache key format to avoid collision with real passwords
    private const PASSWORD_PLACEHOLDER = '__PRESERVE_EXISTING_PASSWORD__';

    public function handle(User $actor, array $validated): void
    {
        $updatedKeys = [];

        // Whitelist of allowed setting keys to prevent injection via future form additions
        $allowedKeys = [
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_from_address',
            'smtp_from_name',
            'smtp_encryption',
            'session_lifetime',
        ];

        // Determine if password should be skipped (not changed)
        $skipPassword = empty($validated['smtp_password'])
            || $validated['smtp_password'] === self::PASSWORD_PLACEHOLDER;

        foreach ($validated as $key => $value) {
            // Skip non-whitelisted keys
            if (! in_array($key, $allowedKeys)) {
                continue;
            }

            // Skip password if unchanged
            if ($key === 'smtp_password' && $skipPassword) {
                continue;
            }

            SystemSetting::set($key, $value);
            $updatedKeys[] = $key;
        }

        // Log only the keys updated, never the values (prevents password leakage)
        activity()
            ->causedBy($actor)
            ->withProperties(['updated_keys' => $updatedKeys])
            ->log('system_settings_updated');
    }
}
