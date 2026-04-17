<?php

namespace App\Http\Controllers\Admin;

use App\Actions\System\UpdateSystemSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    // Sentinel used to preserve existing password without overwriting
    private const PASSWORD_PLACEHOLDER = '__PRESERVE_EXISTING_PASSWORD__';

    public function index(): Response
    {
        $this->authorize('manage_system');

        try {
            $settings = $this->loadSettingsForView();
        } catch (\Exception $e) {
            // Settings table doesn't exist yet (migration not run)
            $settings = $this->getDefaultSettings();
        }

        return Inertia::render('Admin/System/Index', [
            'settings' => $settings,
            'testResult' => null,
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $this->authorize('manage_system');

        // Invalidate settings cache when updated
        Cache::forget('system_settings');

        app(UpdateSystemSettings::class)->handle(auth()->user(), $request->validated());

        return back()->with('success', 'Cài đặt hệ thống đã được cập nhật.');
    }

    public function testEmail(): Response
    {
        $this->authorize('manage_system');

        $result = ['success' => false, 'message' => ''];

        try {
            // Validate admin email exists
            $adminEmail = auth()->user()->email;
            if (! $adminEmail || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Admin email address is not configured or invalid.');
            }

            // Queue test email (matches behavior of business emails)
            Mail::queue(function ($message) use ($adminEmail) {
                $message->to($adminEmail)
                    ->subject('[Test] '.config('app.name').' — SMTP Configuration')
                    ->text('Test email from '.config('app.name').'. If you received this, SMTP is configured correctly.');
            });

            $result = ['success' => true, 'message' => 'Test email queued and will be sent to '.$adminEmail];
        } catch (\Exception $e) {
            // Sanitize error message to avoid leaking SMTP server details
            $message = $e->getMessage();
            if (str_contains($message, 'SMTP')) {
                $message = 'SMTP configuration error. Please verify your settings.';
            } elseif (str_contains($message, 'Connection') || str_contains($message, 'Connection refused')) {
                $message = 'Cannot connect to mail server. Please check your SMTP configuration.';
            } elseif (str_contains($message, 'password') || str_contains($message, 'authentication')) {
                $message = 'Email authentication failed. Please verify your SMTP credentials.';
            }
            $result = ['success' => false, 'message' => $message];
        }

        try {
            $settings = $this->loadSettingsForView();
        } catch (\Exception $e) {
            $settings = $this->getDefaultSettings();
        }

        return Inertia::render('Admin/System/Index', [
            'settings' => $settings,
            'testResult' => $result,
        ]);
    }

    private function loadSettingsForView(): array
    {
        $settings = SystemSetting::all()->pluck('value', 'key');

        return [
            'smtp_host' => $settings->get('smtp_host', ''),
            'smtp_port' => $settings->get('smtp_port', '587'),
            'smtp_username' => $settings->get('smtp_username', ''),
            'smtp_password' => $settings->get('smtp_password') ? self::PASSWORD_PLACEHOLDER : '',
            'smtp_from_address' => $settings->get('smtp_from_address', ''),
            'smtp_from_name' => $settings->get('smtp_from_name', config('app.name')),
            'smtp_encryption' => $settings->get('smtp_encryption', 'tls'),
            'session_lifetime' => (int) $settings->get('session_lifetime', 120),
        ];
    }

    private function getDefaultSettings(): array
    {
        return [
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_address' => '',
            'smtp_from_name' => config('app.name'),
            'smtp_encryption' => 'tls',
            'session_lifetime' => 120,
        ];
    }
}
