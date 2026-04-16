<?php

use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Spatie\Activitylog\Models\Activity;

// ─── AC1: Admin lưu SMTP settings ─────────────────────────────────────────────

test('admin can update smtp settings', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'user@example.com',
            'smtp_password' => 'secret123',
            'smtp_from_address' => 'no-reply@example.com',
            'smtp_from_name' => 'Workflow',
            'smtp_encryption' => 'tls',
            'session_lifetime' => 60,
        ])
        ->assertRedirect();

    expect(SystemSetting::get('smtp_host'))->toBe('smtp.example.com');
    expect(SystemSetting::get('smtp_port'))->toBe('587');
    expect(SystemSetting::get('session_lifetime'))->toBe('60');
});

test('admin update settings creates activity log', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'smtp_host' => 'smtp.test.com',
            'session_lifetime' => 90,
        ])
        ->assertRedirect();

    $log = Activity::where('description', 'system_settings_updated')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($admin->id);
});

// ─── AC2: Test Email ───────────────────────────────────────────────────────────

test('admin can send test email returns ok with inertia component', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.system.test-email'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/System/Index'));
});

test('test email response contains success result to admin email', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create(['email' => 'admin-test@example.com']);
    $admin->assignRole('admin');

    // Mail::raw() gửi sync — trong test env (MAIL_MAILER=array) mail được capture
    $this->actingAs($admin)
        ->post(route('admin.system.test-email'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/System/Index')
            ->has('testResult')
        );
});

// ─── AC3: Session Timeout ─────────────────────────────────────────────────────

test('admin can configure session timeout', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'session_lifetime' => 30,
        ])
        ->assertRedirect();

    expect(SystemSetting::get('session_lifetime'))->toBe('30');
});

test('session lifetime minimum is 5 minutes', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'session_lifetime' => 4,
        ])
        ->assertSessionHasErrors('session_lifetime');
});

// ─── AC4: RBAC Protection ─────────────────────────────────────────────────────

test('non-admin gets 403 on system settings index', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $this->actingAs($executor)
        ->get(route('admin.system.index'))
        ->assertForbidden();
});

test('non-admin gets 403 on system settings update', function () {
    $this->seed(RequiredDataSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->put(route('admin.system.update'), [
            'session_lifetime' => 60,
        ])
        ->assertForbidden();
});

test('non-admin gets 403 on test email', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $this->actingAs($executor)
        ->post(route('admin.system.test-email'))
        ->assertForbidden();
});

test('guest is redirected from system settings', function () {
    $this->get(route('admin.system.index'))
        ->assertRedirect(route('login'));
});

// ─── AC5: Hiển thị settings + password masking ────────────────────────────────

test('admin sees current settings on index', function () {
    $this->seed(RequiredDataSeeder::class);
    SystemSetting::set('smtp_host', 'smtp.example.com');
    SystemSetting::set('session_lifetime', '90');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/System/Index')
            ->where('settings.smtp_host', 'smtp.example.com')
            ->where('settings.session_lifetime', 90)
        );
});

test('smtp password is masked in view', function () {
    $this->seed(RequiredDataSeeder::class);
    SystemSetting::set('smtp_password', 'supersecret');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('settings.smtp_password', '__PRESERVE_EXISTING_PASSWORD__')
        );
});

// ─── Security: SMTP password không bị log ─────────────────────────────────────

test('smtp password not exposed in activity log', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'smtp_password' => 'supersecret',
            'session_lifetime' => 120,
        ]);

    $log = Activity::where('description', 'system_settings_updated')->latest()->first();
    expect(json_encode($log->properties))->not->toContain('supersecret');
});

test('smtp password not updated when placeholder sent', function () {
    $this->seed(RequiredDataSeeder::class);
    SystemSetting::set('smtp_password', 'original_password');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'smtp_password' => '__PRESERVE_EXISTING_PASSWORD__',
            'session_lifetime' => 120,
        ]);

    expect(SystemSetting::get('smtp_password'))->toBe('original_password');
});

test('smtp password not updated when empty string sent', function () {
    $this->seed(RequiredDataSeeder::class);
    SystemSetting::set('smtp_password', 'original_password');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'smtp_password' => '',
            'session_lifetime' => 120,
        ]);

    expect(SystemSetting::get('smtp_password'))->toBe('original_password');
});

// ─── Integration: Middleware applies settings ──────────────────────────────────

test('middleware applies smtp config at runtime', function () {
    $this->seed(RequiredDataSeeder::class);
    SystemSetting::set('smtp_host', 'smtp.example.com');
    SystemSetting::set('smtp_port', '587');
    SystemSetting::set('smtp_encryption', 'tls');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.system.index'));

    // Verify config was set by middleware
    expect(\Illuminate\Support\Facades\Config::get('mail.mailers.smtp.host'))->toBe('smtp.example.com');
    expect(\Illuminate\Support\Facades\Config::get('mail.mailers.smtp.port'))->toBe(587);
    expect(\Illuminate\Support\Facades\Config::get('mail.mailers.smtp.encryption'))->toBe('tls');
});

test('middleware applies session lifetime at runtime', function () {
    $this->seed(RequiredDataSeeder::class);
    SystemSetting::set('session_lifetime', '30');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.system.index'));

    // Verify session lifetime was set by middleware
    expect(\Illuminate\Support\Facades\Config::get('session.lifetime'))->toBe(30);
});
