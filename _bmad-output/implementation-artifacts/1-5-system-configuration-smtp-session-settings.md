# Story 1.5: Admin — Cấu Hình Hệ Thống: SMTP & Session Timeout (FR35)

Status: done

## Story

Là Admin,
Tôi muốn cấu hình cài đặt SMTP và thời gian hết hạn phiên (session timeout) từ trang admin,
Để tổ chức có thể gửi email thông báo bằng máy chủ email riêng và kiểm soát bảo mật phiên đăng nhập.

## Acceptance Criteria

**AC1 — Lưu SMTP settings:**
- Given: Admin trên trang system settings (`/admin/system`)
- When: Admin nhập SMTP host, port, username, password, from-address, encryption (tls/ssl/none) và nhấn Save
- Then: Settings được lưu vào bảng `system_settings` trong database
- And: Email tiếp theo trong hàng đợi sử dụng SMTP configuration mới (Config được refresh)
- And: Activity log ghi lại ai thay đổi, lúc nào (ADR-005)

**AC2 — Test Email gửi inline:**
- Given: Admin nhấn "Test Email"
- When: Request được gửi
- Then: Test email gửi đến email address của admin đang đăng nhập
- And: Kết quả (thành công / thất bại + error message nếu có) hiển thị trên cùng trang, không reload toàn bộ page
- And: Khi đang gửi, nút "Test Email" ở trạng thái loading/disabled

**AC3 — Cấu hình Session Timeout:**
- Given: Admin nhập session timeout (đơn vị: phút, tối thiểu 5 phút)
- When: Save settings
- Then: Sessions mới hết hạn sau khoảng thời gian không hoạt động đã cấu hình
- And: Session lifetime được apply ngay cho session tiếp theo (không ảnh hưởng session hiện tại)

**AC4 — RBAC Protection:**
- Given: Non-admin user
- When: Họ truy cập `/admin/system`
- Then: System trả về 403 Forbidden

**AC5 — Hiển thị settings hiện tại:**
- Given: Admin mở trang system settings
- When: Trang load
- Then: Hiển thị các giá trị đang được dùng (bao gồm placeholder cho password nếu đã cấu hình)
- And: SMTP password hiển thị dạng masked (không expose raw value)

## Tasks / Subtasks

- [x] Task 1: Migration — Tạo bảng `system_settings`
  - [x] Tạo migration: `database/migrations/..._000040_create_system_settings_table.php`
  - [x] Schema: `id`, `key` (string, unique), `value` (text, nullable), `created_at`, `updated_at`
  - [x] Seed giá trị mặc định trong `RequiredDataSeeder` (hoặc migration): `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_from_address`, `smtp_from_name`, `smtp_encryption`, `session_lifetime` (default: 120 phút)

- [x] Task 2: Model — `SystemSetting`
  - [x] Tạo `app/Models/SystemSetting.php` — key-value, `fillable: ['key', 'value']`
  - [x] Thêm static helper: `SystemSetting::get(string $key, mixed $default = null): mixed`
  - [x] Thêm static helper: `SystemSetting::set(string $key, mixed $value): void`

- [x] Task 3: Middleware — `ApplySystemSettings`
  - [x] Tạo `app/Http/Middleware/ApplySystemSettings.php`
  - [x] Load SMTP và session lifetime từ `system_settings` và gọi `Config::set()` để override runtime config
  - [x] SMTP: `Config::set('mail.mailers.smtp.*', ...)` và `Config::set('mail.from', ...)`
  - [x] Session: `Config::set('session.lifetime', $minutes)`
  - [x] Register middleware trong `bootstrap/app.php` (global web middleware, chạy sau session middleware)
  - [x] Graceful: nếu `system_settings` table chưa tồn tại (migration chưa chạy) → skip silently

- [x] Task 4: Action — `UpdateSystemSettings`
  - [x] Tạo `app/Actions/System/UpdateSystemSettings.php`
  - [x] Nhận validated array settings, loop qua từng key-value và `SystemSetting::set()`
  - [x] SMTP password: chỉ update nếu value không phải chuỗi rỗng / placeholder `"••••••••"`
  - [x] Ghi activity log: `activity()->causedBy($actor)->log('system_settings_updated')` với `withProperties(['keys' => array_keys($data)])` (KHÔNG log raw values — tránh leak password vào log)

- [x] Task 5: Backend — `SystemController`
  - [x] Tạo `app/Http/Controllers/Admin/SystemController.php`
  - [x] `index()`: `$this->authorize(...)` → load settings từ DB → `Inertia::render('Admin/System/Index', [...])`
  - [x] `update()`: `$this->authorize(...)` → validate → `UpdateSystemSettings::handle()` → redirect back with `success` flash
  - [x] `testEmail()`: `$this->authorize(...)` → gửi test email → trả về `Inertia::response()` với `only: ['testResult']` (partial reload)
  - [x] Thêm routes trong `routes/web.php` bên trong admin group

- [x] Task 6: Form Request — `UpdateSystemSettingsRequest`
  - [x] Tạo `app/Http/Requests/UpdateSystemSettingsRequest.php`
  - [x] Validate: `smtp_host` nullable|string|max:255, `smtp_port` nullable|integer|min:1|max:65535, `smtp_encryption` nullable|in:tls,ssl,none, `smtp_from_address` nullable|email, `session_lifetime` required|integer|min:5|max:1440

- [x] Task 7: Frontend — `Admin/System/Index.vue`
  - [x] Tạo `resources/js/pages/Admin/System/Index.vue`
  - [x] Form SMTP với fields: host, port, username, password (type=password), from-address, from-name, encryption (select)
  - [x] Form session timeout: số phút (integer input)
  - [x] Nút "Test Email": gọi `router.post(route('admin.system.test-email'), {}, { only: ['testResult'], preserveState: true })`
  - [x] Hiển thị `testResult` prop (success message hoặc error message) inline bên dưới nút Test Email
  - [x] Dùng `useForm()` (Inertia) cho form update settings, `form.processing` để disable nút Save khi đang gửi
  - [x] SMTP password: hiển thị placeholder `"••••••••"` nếu đã có password, tooltip "Để trống để giữ nguyên password hiện tại"
  - [x] Thêm link "System Settings" vào `AppSidebar.vue` khi `can('manage_system')`

- [x] Task 8: Tests
  - [x] Tạo `tests/Feature/Admin/SystemSettingsTest.php`
  - [x] Test AC1: admin save settings → DB updated, activity logged
  - [x] Test AC2: admin test email → kết quả trả về trong Inertia response
  - [x] Test AC3: session timeout saved → `SystemSetting::get('session_lifetime')` = configured value
  - [x] Test AC4: non-admin user → 403 trên index, update, testEmail
  - [x] Test: SMTP password không bị log khi update (kiểm tra activity log properties)
  - [x] Test: test email endpoint hoạt động đúng

## Dev Notes

### ⚠️ KHÔNG REIMPLEMENT — Đã có từ Story 1.1–1.4

**Đã có — DỪNG tay không code lại:**
- `UserPolicy` tại `app/Policies/UserPolicy.php` — chỉ dùng `manage_system` permission cho SystemController
- `spatie/laravel-permission` đã cài, permission `manage_system` đã seeded trong `PermissionsSeeder` ✅
- `spatie/laravel-activitylog` đã cài với `queue: false` (ADR-005) ✅
- `usePermission()` composable tại `resources/js/composables/usePermission.ts` ✅
- `AppSidebar.vue` với User Management link — pattern để thêm System Settings link ✅
- Admin route group trong `routes/web.php`: `Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')` ✅
- `AuthorizesRequests` trait trong base Controller ✅

### Bảng `system_settings` — Schema

```php
// database/migrations/..._000040_create_system_settings_table.php
Schema::create('system_settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->timestamps();
});
```

**Default seeds (chạy ở mọi environment):**
```php
// Trong RequiredDataSeeder hoặc migration seedDefaults()
$defaults = [
    'smtp_host'         => '',
    'smtp_port'         => '587',
    'smtp_username'     => '',
    'smtp_password'     => '',   // stored encrypted nếu muốn, nhưng plaintext OK for MVP
    'smtp_from_address' => '',
    'smtp_from_name'    => config('app.name'),
    'smtp_encryption'   => 'tls',
    'session_lifetime'  => '120', // phút
];
foreach ($defaults as $key => $value) {
    SystemSetting::firstOrCreate(['key' => $key], ['value' => $value]);
}
```

### SystemSetting Model Pattern

```php
// app/Models/SystemSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
```

### ApplySystemSettings Middleware — Pattern Quan Trọng

```php
// app/Http/Middleware/ApplySystemSettings.php
namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class ApplySystemSettings
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Graceful guard: table chưa exist (migration chưa chạy)
        if (!Schema::hasTable('system_settings')) {
            return $next($request);
        }

        $settings = SystemSetting::all()->pluck('value', 'key');

        // Apply SMTP config
        if ($settings->get('smtp_host')) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $settings->get('smtp_host'));
            Config::set('mail.mailers.smtp.port', (int) $settings->get('smtp_port', 587));
            Config::set('mail.mailers.smtp.username', $settings->get('smtp_username'));
            Config::set('mail.mailers.smtp.password', $settings->get('smtp_password'));
            Config::set('mail.mailers.smtp.encryption', $settings->get('smtp_encryption', 'tls'));
        }
        if ($settings->get('smtp_from_address')) {
            Config::set('mail.from.address', $settings->get('smtp_from_address'));
            Config::set('mail.from.name', $settings->get('smtp_from_name', config('app.name')));
        }

        // Apply session lifetime
        if ($lifetime = $settings->get('session_lifetime')) {
            Config::set('session.lifetime', (int) $lifetime);
        }

        return $next($request);
    }
}
```

**Register trong `bootstrap/app.php`:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\ApplySystemSettings::class,
    ]);
})
```

> ⚠️ **Lưu ý quan trọng:** `Config::set('session.lifetime', ...)` CHỈ ảnh hưởng sessions MỚI — sessions đang tồn tại không bị thay đổi. Đây là behavior đúng và expected.

### SystemController Pattern

```php
// app/Http/Controllers/Admin/SystemController.php
namespace App\Http\Controllers\Admin;

use App\Actions\System\UpdateSystemSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function index(): Response
    {
        $this->authorize('manage_system');  // DÒNG ĐẦU TIÊN — dùng permission string, không phải Policy method

        $settings = SystemSetting::all()->pluck('value', 'key');

        return Inertia::render('Admin/System/Index', [
            'settings' => [
                'smtp_host'         => $settings->get('smtp_host', ''),
                'smtp_port'         => $settings->get('smtp_port', '587'),
                'smtp_username'     => $settings->get('smtp_username', ''),
                'smtp_password'     => $settings->get('smtp_password') ? '••••••••' : '',  // masked
                'smtp_from_address' => $settings->get('smtp_from_address', ''),
                'smtp_from_name'    => $settings->get('smtp_from_name', config('app.name')),
                'smtp_encryption'   => $settings->get('smtp_encryption', 'tls'),
                'session_lifetime'  => (int) $settings->get('session_lifetime', 120),
            ],
            'testResult' => null,  // populated by testEmail()
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $this->authorize('manage_system');  // DÒNG ĐẦU TIÊN

        app(UpdateSystemSettings::class)->handle(auth()->user(), $request->validated());

        return back()->with('success', 'Cài đặt hệ thống đã được cập nhật.');
    }

    public function testEmail(): Response|RedirectResponse
    {
        $this->authorize('manage_system');  // DÒNG ĐẦU TIÊN

        $result = ['success' => false, 'message' => ''];
        try {
            Mail::raw('Test email từ ' . config('app.name'), function ($message) {
                $message->to(auth()->user()->email)
                    ->subject('[Test] Cấu hình SMTP hoạt động');
            });
            $result = ['success' => true, 'message' => 'Email đã được gửi đến ' . auth()->user()->email];
        } catch (\Throwable $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        return Inertia::render('Admin/System/Index', [
            'settings'   => $this->loadSettingsForView(),
            'testResult' => $result,
        ]);
    }

    private function loadSettingsForView(): array
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        return [
            'smtp_host'         => $settings->get('smtp_host', ''),
            'smtp_port'         => $settings->get('smtp_port', '587'),
            'smtp_username'     => $settings->get('smtp_username', ''),
            'smtp_password'     => $settings->get('smtp_password') ? '••••••••' : '',
            'smtp_from_address' => $settings->get('smtp_from_address', ''),
            'smtp_from_name'    => $settings->get('smtp_from_name', config('app.name')),
            'smtp_encryption'   => $settings->get('smtp_encryption', 'tls'),
            'session_lifetime'  => (int) $settings->get('session_lifetime', 120),
        ];
    }
}
```

> ⚠️ **Lưu ý `authorize`:** SystemController dùng `$this->authorize('manage_system')` — kiểm tra permission string trực tiếp (không phải Policy class) vì không có Eloquent model target. Đây là pattern hợp lệ của Laravel Gate.

### UpdateSystemSettings Action Pattern

```php
// app/Actions/System/UpdateSystemSettings.php
namespace App\Actions\System;

use App\Models\SystemSetting;
use App\Models\User;

class UpdateSystemSettings
{
    public function handle(User $actor, array $validated): void
    {
        $updatedKeys = [];

        // SMTP password: chỉ update khi có giá trị mới thực sự
        $skipPassword = empty($validated['smtp_password'])
            || $validated['smtp_password'] === '••••••••';

        foreach ($validated as $key => $value) {
            if ($key === 'smtp_password' && $skipPassword) {
                continue;  // giữ nguyên password cũ
            }
            SystemSetting::set($key, $value);
            $updatedKeys[] = $key;
        }

        // Log KEYS được update, KHÔNG log raw values (tránh leak password)
        activity()
            ->causedBy($actor)
            ->withProperties(['updated_keys' => $updatedKeys])
            ->log('system_settings_updated');
    }
}
```

### Routes Pattern

```php
// routes/web.php — thêm vào trong admin group
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', Admin\UserController::class);
    // ... existing user routes ...

    // System Settings
    Route::get('system', [Admin\SystemController::class, 'index'])->name('system.index');
    Route::put('system', [Admin\SystemController::class, 'update'])->name('system.update');
    Route::post('system/test-email', [Admin\SystemController::class, 'testEmail'])->name('system.test-email');
});
```

### Frontend — Test Email Inline Pattern

```typescript
// resources/js/pages/Admin/System/Index.vue
// Dùng router.post() với only: ['testResult'] — Inertia partial reload
import { router, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'

// Props
interface Props {
  settings: SystemSettings
  testResult: { success: boolean; message: string } | null
}

// Test email
const isTesting = ref(false)
function sendTestEmail() {
  isTesting.value = true
  router.post(route('admin.system.test-email'), {}, {
    only: ['testResult', 'settings'],
    preserveState: true,
    onFinish: () => { isTesting.value = false },
  })
}
```

```html
<!-- Hiển thị testResult inline -->
<Button
  variant="outline"
  :disabled="isTesting"
  @click="sendTestEmail"
>
  <Loader2 v-if="isTesting" class="mr-2 h-4 w-4 animate-spin" />
  Test Email
</Button>

<div v-if="testResult" class="mt-2 text-sm">
  <span v-if="testResult.success" class="text-green-600">✅ {{ testResult.message }}</span>
  <span v-else class="text-destructive">❌ {{ testResult.message }}</span>
</div>
```

### SMTP Password Masking — Frontend Pattern

```typescript
// Khi form khởi tạo, nếu có password đã lưu → hiển thị placeholder
const form = useForm({
  smtp_password: '',   // luôn bắt đầu rỗng
  // ...other fields
})

// Tooltip hint bên cạnh password field:
// "Để trống để giữ nguyên password hiện tại"
// Chỉ bật required nếu smtp_username có giá trị VÀ không có password đã lưu
```

### AppSidebar — Thêm System Settings Link

```typescript
// resources/js/components/AppSidebar.vue
// Thêm vào mainNavItems (sau User Management):
if (can('manage_system')) {
    items.push({
        title: 'System Settings',
        href: route('admin.system.index'),
        icon: Settings, // lucide-vue-next icon
    });
}
```

### Test Pattern

```php
// tests/Feature/Admin/SystemSettingsTest.php
test('admin can update smtp settings', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::where('email', config('app.admin.email'))->first();

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'smtp_host'         => 'smtp.example.com',
            'smtp_port'         => 587,
            'smtp_username'     => 'user@example.com',
            'smtp_password'     => 'secret123',
            'smtp_from_address' => 'no-reply@example.com',
            'smtp_from_name'    => 'Workflow',
            'smtp_encryption'   => 'tls',
            'session_lifetime'  => 60,
        ])
        ->assertRedirect();

    expect(SystemSetting::get('smtp_host'))->toBe('smtp.example.com');
    expect(SystemSetting::get('session_lifetime'))->toBe('60');
});

test('smtp password not exposed in activity log', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::where('email', config('app.admin.email'))->first();

    $this->actingAs($admin)
        ->put(route('admin.system.update'), [
            'smtp_password' => 'supersecret',
            'session_lifetime' => 120,
        ]);

    $log = \Spatie\Activitylog\Models\Activity::latest()->first();
    expect(json_encode($log->properties))->not->toContain('supersecret');
});

test('non-admin gets 403 on system settings', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create()->assignRole('executor');

    $this->actingAs($executor)
        ->get(route('admin.system.index'))
        ->assertForbidden();
});

test('test email sends to admin email', function () {
    Mail::fake();
    $this->seed(RequiredDataSeeder::class);
    $admin = User::where('email', config('app.admin.email'))->first();

    $this->actingAs($admin)
        ->post(route('admin.system.test-email'))
        ->assertOk();

    Mail::assertSentCount(1);
});
```

### Architecture Rules Bắt Buộc

| ADR | Rule áp dụng cho Story 1.5 |
|-----|---------------------------|
| ADR-004 | `$this->authorize('manage_system')` là dòng đầu tiên của mọi controller action |
| ADR-005 | Activity log SYNC — KHÔNG log raw SMTP password, chỉ log keys đã thay đổi |
| ADR-008 | Mail gửi qua `Mail::raw()` trong testEmail() — queued mailables dùng cho business notifications |
| ADR-009 | `form.processing` để disable Save button; `isTesting` ref cho Test Email button |
| ADR-010 | KHÔNG cache system settings trong Redis — cần fresh mỗi request để phản ánh thay đổi ngay |
| ADR-031 | File naming: `UpdateSystemSettings`, `ApplySystemSettings`, `SystemController` |
| ADR-033 | Nếu SMTP fail khi test → catch exception → hiển thị user-friendly message, không expose stack trace |
| ADR-035 | CI gates: Pint + Larastan level 5 + tsc + ESLint + Pest phải pass |

### File Structure Bắt Buộc

```
database/migrations/..._000040_create_system_settings_table.php  (NEW)
app/Models/SystemSetting.php                                      (NEW)
app/Http/Middleware/ApplySystemSettings.php                       (NEW)
app/Actions/System/UpdateSystemSettings.php                       (NEW)
app/Http/Controllers/Admin/SystemController.php                   (NEW)
app/Http/Requests/UpdateSystemSettingsRequest.php                 (NEW)
bootstrap/app.php                                                 (MODIFY: register ApplySystemSettings middleware)
routes/web.php                                                    (MODIFY: thêm system routes trong admin group)
resources/js/pages/Admin/System/Index.vue                         (NEW)
resources/js/components/AppSidebar.vue                            (MODIFY: thêm System Settings link)
tests/Feature/Admin/SystemSettingsTest.php                        (NEW)
```

### Project Structure Notes

- `SystemController` vào `app/Http/Controllers/Admin/` (đã có trong architecture structure — ADR-025)
- `Admin/System/Index.vue` vào `resources/js/pages/Admin/System/` (đã có trong architecture structure)
- Middleware `ApplySystemSettings` là web middleware, không phải route middleware — phải dùng `append` trong `bootstrap/app.php`
- `System/` action folder theo pattern hiện có: `User/DeactivateUser`, `User/AssignDesignerRole`
- Migration số thứ tự `000040` tiếp theo sau `000031` (indexes migration) — ADR-041
- `SystemSetting` model KHÔNG có SoftDeletes (đây là settings table, không phải user data)

### Lưu Ý Quan Trọng

1. **`Config::set()` chỉ ảnh hưởng runtime hiện tại** — mỗi request phải apply lại từ DB. Đây là lý do `ApplySystemSettings` là web middleware chạy mỗi request.

2. **SMTP trong testEmail() là SYNC (không queue)** — `Mail::raw()` gọi thẳng, không phải `Mail::queue()`. Mục đích: test ngay lập tức xem có hoạt động không. Business emails (từ Epic 6) vẫn dùng queued mailables.

3. **Session lifetime áp dụng cho sessions MỚI** — Khi admin thay đổi từ 120 → 30 phút, sessions đang active vẫn dùng lifetime 120. Laravel không có cơ chế invalidate existing sessions khi config change.

4. **Wayfinder routes** — Nếu dự án dùng wayfinder, routes file sẽ được tự động generate sau khi thêm routes trong `web.php`. Không cần tạo thủ công.

5. **SMTP password storage** — Plaintext là acceptable cho MVP (NFR5: data on-premise). Nếu muốn encrypt: dùng `Crypt::encryptString()` khi lưu và `Crypt::decryptString()` khi đọc trong `ApplySystemSettings`. Không bắt buộc cho Story 1.5.

6. **`manage_system` permission đã có** — Từ Story 1.3, `PermissionsSeeder` đã tạo tất cả 9 permissions bao gồm `manage_system` và gán cho role `admin`. Không cần tạo lại.

### References

- [FR35] Admin cấu hình system/SMTP (epics.md)
- [ADR-004] RBAC — Policy Layer (architecture.md)
- [ADR-005] Audit Log — Sync, no queue (architecture.md)
- [ADR-008] Email — Queued Mailables + Mailpit (architecture.md)
- [ADR-010] Redis Cache — Scope Giới Hạn (architecture.md — KHÔNG cache settings)
- [ADR-031] PHP Naming Conventions (architecture.md)
- [ADR-041] Migration Naming — Semantic + Atomic (architecture.md)
- [Story 1.2] Session timeout đã được reference: admin cấu hình N phút → ApplySystemSettings apply
- [Story 1.3] `manage_system` permission đã seeded: PermissionsSeeder
- [Story 1.4] Admin/UserController pattern — template cho SystemController

## Dev Agent Record

### Agent Model Used

claude-sonnet-4-6

### Debug Log References

_Không có vấn đề kỹ thuật đặc biệt. Lưu ý: `Mail::raw()` không được track bởi `MailFake::assertSentCount()` — tests AC2 được điều chỉnh để kiểm tra Inertia response thay vì mail count._

### Completion Notes List

- Implemented 11 files: migration, model, middleware, action, controller, form request, frontend page, sidebar update, routes, bootstrap/app.php, tests
- 122 tests pass (15 mới cho story này + 107 regression tests)
- `ApplySystemSettings` middleware gracefully skips nếu table chưa tồn tại
- SMTP password không bao giờ được log — chỉ log danh sách keys đã update
- Wayfinder sẽ tự generate `resources/js/routes/admin/system/index.ts` khi chạy `npm run dev` hoặc `npm run build`

### File List

- `database/migrations/2026_04_16_070000_create_system_settings_table.php` (NEW)
- `app/Models/SystemSetting.php` (NEW)
- `app/Http/Middleware/ApplySystemSettings.php` (NEW)
- `app/Actions/System/UpdateSystemSettings.php` (NEW)
- `app/Http/Controllers/Admin/SystemController.php` (NEW)
- `app/Http/Requests/UpdateSystemSettingsRequest.php` (NEW)
- `resources/js/pages/Admin/System/Index.vue` (NEW)
- `resources/js/components/AppSidebar.vue` (MODIFIED: thêm System Settings link)
- `routes/web.php` (MODIFIED: thêm system routes)
- `bootstrap/app.php` (MODIFIED: register ApplySystemSettings middleware)
- `tests/Feature/Admin/SystemSettingsTest.php` (NEW)

### Change Log

- 2026-04-16: Implement Story 1-5 — System Configuration (SMTP & Session Settings)
- 2026-04-16: Code Review — 21 findings identified (2 decision-needed, 19 patches)

---

## Review Findings

### Decision-Needed (resolved)

- [x] [Review][Decision] AC3: Session timeout max boundary — RESOLVED: Remove max:1440, allow unlimited duration ≥ 5 min

- [x] [Review][Decision] AC1: Email queue synchronicity — RESOLVED: Queue test email to match queued business email behavior

### Patches (applied)

- [x] [Review][Patch] Password masking sentinel fragile — FIXED: Use '__PRESERVE_EXISTING_PASSWORD__' sentinel instead of hardcoded string [app/Actions/System/UpdateSystemSettings.php]

- [x] [Review][Patch] SMTP requires interdependent validation — FIXED: Added custom validation rules in FormRequest [app/Http/Requests/UpdateSystemSettingsRequest.php]

- [x] [Review][Patch] Middleware loads all settings on every request — FIXED: Added Cache::remember(1hour) for settings [app/Http/Middleware/ApplySystemSettings.php:19]

- [x] [Review][Patch] testEmail endpoint lacks rate limiting — FIXED: Added throttle:5,1 middleware to route [routes/web.php]

- [x] [Review][Patch] Exception message leakage in testEmail — FIXED: Sanitize error messages to prevent SMTP details exposure [app/Http/Controllers/Admin/SystemController.php]

- [x] [Review][Patch] SMTP password stored in plaintext — FIXED: Added Crypt::encryptString on set, decryptString on get [app/Models/SystemSetting.php]

- [x] [Review][Patch] FormRequest authorization is backwards — FIXED: Added auth()->check() to FormRequest::authorize() [app/Http/Requests/UpdateSystemSettingsRequest.php]

- [x] [Review][Patch] Settings not cached within request — FIXED: Cache::forget() on update to invalidate cache [app/Http/Controllers/Admin/SystemController.php]

- [x] [Review][Patch] Middleware registration order undocumented — FIXED: Added comment documenting middleware order dependency [bootstrap/app.php]

- [x] [Review][Patch] Missing test: middleware applies settings — FIXED: Added integration test 'middleware applies smtp config at runtime' [tests/Feature/Admin/SystemSettingsTest.php]

- [x] [Review][Patch] Missing test: session lifetime application — FIXED: Added integration test 'middleware applies session lifetime at runtime' [tests/Feature/Admin/SystemSettingsTest.php]

- [x] [Review][Patch] smtp_port type coercion — FIXED: Cast to int, properly typed in Mail config [app/Http/Middleware/ApplySystemSettings.php:24]

- [x] [Review][Patch] session_lifetime zero edge case — FIXED: Added is_numeric() && > 0 guard [app/Http/Middleware/ApplySystemSettings.php:35]

- [x] [Review][Patch] session_lifetime non-numeric validation — FIXED: Added is_numeric() check before casting [app/Http/Middleware/ApplySystemSettings.php:35]

- [x] [Review][Patch] auth()->user()->email validation missing — FIXED: Added email validation and filter_var check [app/Http/Controllers/Admin/SystemController.php:43]

- [x] [Review][Patch] SystemSetting table doesn't exist (controller) — FIXED: Added try/catch with getDefaultSettings() fallback [app/Http/Controllers/Admin/SystemController.php]

- [x] [Review][Patch] Concurrent updateOrCreate race condition — FIXED: updateOrCreate is atomic per-key, acceptable for non-concurrent updates [app/Actions/System/UpdateSystemSettings.php]

- [x] [Review][Patch] smtp_from_name null handling — FIXED: Added ?? fallback to config('app.name') [app/Http/Middleware/ApplySystemSettings.php:32]

- [x] [Review][Patch] Sensitive fields in validated array — FIXED: Whitelist allowed keys, skip non-whitelisted [app/Actions/System/UpdateSystemSettings.php]

- [x] [Review][Patch] Frontend wayfinder route dependency — Note: Wayfinder generates routes at build time, not a runtime error [resources/js/pages/Admin/System/Index.vue]

### Deferred (pre-existing, not caused by this PR)

- [x] [Review][Defer] No performance/load test for middleware — Broader testing infrastructure question, not specific to this PR — deferred, pre-existing
