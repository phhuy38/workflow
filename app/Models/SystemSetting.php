<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::where('key', $key)->value('value') ?? $default;

        // Auto-decrypt SMTP password if encrypted
        if ($key === 'smtp_password' && $value && str_starts_with($value, 'eyJpdiI6')) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, return as-is (backward compat for plaintext)
                return $value;
            }
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        // Encrypt SMTP password for storage
        if ($key === 'smtp_password' && $value) {
            $value = Crypt::encryptString($value);
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
