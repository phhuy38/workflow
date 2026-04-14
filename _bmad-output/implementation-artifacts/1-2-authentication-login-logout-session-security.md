# Story 1.2: Authentication — Login, Logout & Session Security

Status: review

## Story

As a user (tất cả vai trò),
I want to log in with email and password and have my session managed securely,
so that I can access the system and my session expires automatically when I'm inactive.

## Acceptance Criteria

**AC1 — Login redirect theo vai trò:**
- Given: user với credentials hợp lệ
- When: họ submit login form
- Then: họ được xác thực và redirect đến `/dashboard` (trang phù hợp với vai trò; tất cả vai trò dùng dashboard trong Epic 1)

**AC2 — Logout huỷ session và redirect về login:**
- Given: authenticated user
- When: họ POST `/logout`
- Then: session bị hủy và redirect về `/login` (không phải `/`)

**AC3 — Session timeout tự động:**
- Given: `SESSION_LIFETIME` được cấu hình N phút
- When: user không hoạt động sau N phút
- Then: session hết hạn và request tiếp theo redirect về `/login`

**AC4 — Security headers trên mọi response (ĐÃ DONE trong Story 1.1 — chỉ verify):**
- Given: bất kỳ HTTP response nào
- When: browser nhận được
- Then: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin` có mặt

**AC5 — Unauthenticated user redirect về login:**
- Given: unauthenticated user
- When: họ truy cập bất kỳ protected route
- Then: redirect về `/login`

**AC6 — Block inactive users (ADR-038):**
- Given: user với `is_active = false`
- When: họ submit login form với credentials đúng
- Then: login thất bại với thông báo lỗi tương đương "credentials do not match" (không tiết lộ lý do)

**AC7 — Cập nhật last_login_at (ADR-038):**
- Given: user đăng nhập thành công
- When: authentication xác nhận
- Then: `users.last_login_at` được cập nhật lên timestamp hiện tại

**AC8 — Shared auth.can permissions trong Inertia (ADR-019):**
- Given: authenticated user trên bất kỳ Inertia page
- When: page props được share qua `HandleInertiaRequests`
- Then: `auth.can` object chứa đủ 9 permissions với boolean đúng theo role

## Tasks / Subtasks

- [x] Task 1: Custom `authenticateUsing` — is_active check + last_login_at (AC1, AC6, AC7)
  - [x] Tạo `app/Actions/Fortify/AuthenticateUser.php` (implement logic bên dưới Dev Notes)
  - [x] Register trong `FortifyServiceProvider::configureActions()`: `Fortify::authenticateUsing(app(AuthenticateUser::class))`

- [x] Task 2: Custom `LoginResponse` — role-based redirect (AC1)
  - [x] Tạo `app/Http/Responses/LoginResponse.php` implementing `Laravel\Fortify\Contracts\LoginResponse`
    - Dùng `redirect()->intended(route('dashboard'))` cho tất cả roles hiện tại
    - Thêm comment TODO cho Epic 5 (executor.inbox) và Epic 7 (beneficiary.index)
  - [x] Register trong `FortifyServiceProvider::register()` (KHÔNG phải `boot()`)

- [x] Task 3: Custom `LogoutResponse` — redirect về /login (AC2)
  - [x] Tạo `app/Http/Responses/LogoutResponse.php` implementing `Laravel\Fortify\Contracts\LogoutResponse`
    - Return `redirect(route('login'))`
  - [x] Register trong `FortifyServiceProvider::register()`

- [x] Task 4: Cập nhật `HandleInertiaRequests` với `auth.can` (AC8, ADR-019)
  - [x] Modify `app/Http/Middleware/HandleInertiaRequests.php` — explicit 9-permission map
  - [x] Đảm bảo `can` = `[]` (empty array) khi user chưa authenticate

- [x] Task 5: Fix TypeScript `User` type + thêm `AuthPermissions` (ADR-023)
  - [x] Update `resources/js/types/auth.ts`: `User.name` → `User.full_name`, thêm `is_active`, `last_login_at`, thêm `can` vào `Auth` type
  - [x] Update `resources/js/types/global.d.ts`: `sharedPageProps.auth` reflect type mới
  - [x] Fix tất cả Vue components dùng `user.name` → `user.full_name`:
    - `resources/js/components/AppHeader.vue` (dòng 254: `alt="auth.user.name"`)
    - `resources/js/components/UserInfo.vue` (dòng 26, 28, 33: `user.name`)
    - `resources/js/pages/Settings/Profile.vue` (dòng 59: `:default-value="user.name"`)

- [x] Task 6: Tests
  - [x] Update `tests/Feature/Auth/AuthenticationTest.php`:
    - Update existing `users can logout` test: assert redirect về `route('login')` (không phải `route('home')`)
    - Thêm: inactive user cannot login (AC6)
    - Thêm: `last_login_at` updated on successful login (AC7)
    - Thêm: active user can login (AC1)
  - [x] Tạo `tests/Feature/Auth/SessionTest.php`:
    - Test: unauthenticated user redirect về `/login` (AC5)
    - Test: `auth.can` present và đúng trong Inertia shared data (AC8)
    - Test: session driver = database, lifetime đọc từ config (AC3 config verification)

## Dev Notes

### ⚠️ Context từ Story 1.1 — KHÔNG REIMPLEMENT

**Đã có sẵn — DỪNG tay không code lại:**
- `SecurityHeaders` middleware (`app/Http/Middleware/SecurityHeaders.php`) — AC4 ĐÃ DONE
- Rate limiter tên `login` (5/min per email+IP combo) trong `FortifyServiceProvider::configureRateLimiting()`
- `sessions` table migration trong `0001_01_01_000000_create_users_table.php`
- `User` model với `full_name`, `is_active`, `last_login_at`, `SoftDeletes`, `HasRoles` trait
- Spatie roles và permissions seeded qua `RequiredDataSeeder`
- `Auth/Login.vue` page đầy đủ
- `config/session.php`: `driver = database`, `lifetime = env('SESSION_LIFETIME', 120)`

**Bug từ Story 1.1 cần fix trong Task 5:**
Story 1.1 fix `full_name` ở PHP layer nhưng Vue types vẫn dùng `User.name` (từ starter kit defaults). Mọi chỗ hiển thị tên user trên UI sẽ bị `undefined`. Task 5 fix điều này.

**Fortify route names hiện tại:**
- GET `/login` → `route('login')`
- POST `/login` → `route('login.store')` (Fortify naming)
- POST `/logout` → `route('logout')`
- GET `/dashboard` → `route('dashboard')`

### Architecture Rules (MUST FOLLOW)

**ADR-026:** Session auth qua web middleware. KHÔNG dùng Sanctum, KHÔNG dùng API token.

**ADR-019:** `auth.can` dùng để hide/show UI elements — KHÔNG phải security gate. Server-side Policy check là authoritative cho mọi authorization decision.

**ADR-038:** `is_active` check và `last_login_at` update bắt buộc trong auth flow.

### Code Patterns

**`AuthenticateUser` action:**
```php
// app/Actions/Fortify/AuthenticateUser.php
namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateUser
{
    public function __invoke(Request $request): ?User
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return null;
        }

        // is_active = false: trả về null (thông báo lỗi giống invalid credentials — tránh user enumeration)
        if (!$user->is_active) {
            return null;
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }
}
```

**Register trong `FortifyServiceProvider::configureActions()`:**
```php
Fortify::authenticateUsing(app(AuthenticateUser::class));
```

**`LoginResponse`:**
```php
// app/Http/Responses/LoginResponse.php
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
```

**`LogoutResponse`:**
```php
// app/Http/Responses/LogoutResponse.php
namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        return redirect(route('login'));
    }
}
```

**Register responses trong `FortifyServiceProvider::register()` (KHÔNG phải `boot()`):**
```php
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

public function register(): void
{
    $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
}
```

**`HandleInertiaRequests::share()` — explicit 9-permission map:**
```php
public function share(Request $request): array
{
    $user = $request->user();

    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'auth' => [
            'user' => $user,
            'can' => $user ? [
                'manage_templates'      => $user->can('manage_templates'),
                'publish_templates'     => $user->can('publish_templates'),
                'launch_instances'      => $user->can('launch_instances'),
                'view_all_instances'    => $user->can('view_all_instances'),
                'manage_instances'      => $user->can('manage_instances'),
                'complete_assigned_steps' => $user->can('complete_assigned_steps'),
                'view_own_instances'    => $user->can('view_own_instances'),
                'manage_users'          => $user->can('manage_users'),
                'manage_system'         => $user->can('manage_system'),
            ] : [],
        ],
        'sidebarOpen' => !$request->hasCookie('sidebar_state')
            || $request->cookie('sidebar_state') === 'true',
    ];
}
```

Spatie permission check (`$user->can()`) đọc từ Redis cache (ADR-010) — không có thêm DB query.

**TypeScript `auth.ts` (UPDATE toàn bộ file):**
```typescript
export type User = {
    id: number;
    full_name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AuthPermissions = {
    manage_templates: boolean;
    publish_templates: boolean;
    launch_instances: boolean;
    view_all_instances: boolean;
    manage_instances: boolean;
    complete_assigned_steps: boolean;
    view_own_instances: boolean;
    manage_users: boolean;
    manage_system: boolean;
};

export type Auth = {
    user: User;
    can: AuthPermissions;
};

// Giữ nguyên TwoFactorConfigContent nếu đang dùng
export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
```

**Vue components fix (Task 5) — thay `user.name` → `user.full_name`:**

`UserInfo.vue` lines 26, 28, 33: thay `:alt="user.name"`, `getInitials(user.name)`, `{{ user.name }}` → `user.full_name`.

`AppHeader.vue` line 254: thay `:alt="auth.user.name"` → `:alt="auth.user.full_name"`.

`Settings/Profile.vue` line 59: thay `:default-value="user.name"` → `:default-value="user.full_name"`. Check nếu form field vẫn dùng `name="name"` — nếu `ProfileUpdateRequest` đã đổi sang `full_name`, form field cũng phải thay sang `name="full_name"`.

**Tests pattern:**
```php
// tests/Feature/Auth/AuthenticationTest.php — UPDATE existing test
test('users can logout', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->post(route('logout'));
    $this->assertGuest();
    $response->assertRedirect(route('login')); // WAS: route('home')
});

// THÊM các test mới:
test('inactive users cannot login', function () {
    $user = User::factory()->create(['is_active' => false]);
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $this->assertGuest();
});

test('last_login_at is updated on successful login', function () {
    $user = User::factory()->create(['last_login_at' => null]);
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});
```

```php
// tests/Feature/Auth/SessionTest.php — TẠO MỚI
use App\Models\User;

test('unauthenticated users are redirected to login', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('session driver is database', function () {
    expect(config('session.driver'))->toBe('database');
});

test('auth.can permissions are shared in inertia props', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('auth.can')
        ->where('auth.can.launch_instances', true)
        ->where('auth.can.view_all_instances', true)
        ->where('auth.can.manage_users', false)
    );
});

test('admin has all permissions in auth.can', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('auth.can.manage_users', true)
        ->where('auth.can.manage_system', true)
        ->where('auth.can.manage_templates', true)
    );
});
```

### Project Structure

```
app/Actions/Fortify/AuthenticateUser.php     (NEW)
app/Http/Responses/LoginResponse.php         (NEW)
app/Http/Responses/LogoutResponse.php        (NEW)
app/Providers/FortifyServiceProvider.php     (MODIFY: register() + configureActions())
app/Http/Middleware/HandleInertiaRequests.php (MODIFY: share() thêm auth.can)
resources/js/types/auth.ts                   (MODIFY: User.full_name, Auth.can)
resources/js/types/global.d.ts               (MODIFY nếu cần: sharedPageProps.auth type)
resources/js/components/UserInfo.vue         (MODIFY: user.name → user.full_name)
resources/js/components/AppHeader.vue        (MODIFY: auth.user.name → auth.user.full_name)
resources/js/pages/Settings/Profile.vue      (MODIFY: user.name → user.full_name)
tests/Feature/Auth/AuthenticationTest.php    (MODIFY: update logout test + 2 new tests)
tests/Feature/Auth/SessionTest.php           (NEW)
```

### References

- [Source: architecture.md#ADR-026] Session auth via web middleware
- [Source: architecture.md#ADR-019] Inertia SharedProps với auth.can
- [Source: architecture.md#ADR-038] User model: is_active, last_login_at, deactivation handler
- [Source: architecture.md#ADR-010] Redis cache cho permissions — spatie auto-cache
- [Source: architecture.md#ADR-028] Rate limiting tiers (login: 5/min per IP — đã có từ Story 1.1)
- [Source: architecture.md#ADR-013] Security tests bắt buộc cho Policy methods
- [Source: epics.md#Story-1.2] Acceptance criteria gốc
- [Source: implementation-artifacts/1-1-*.md#Review Findings] Bug: `full_name` mismatch giữa PHP và Vue

## Dev Agent Record

### Agent Model Used

claude-sonnet-4-6 (2026-04-13)

### Debug Log References

_Không có blocking issues. Style fix duy nhất: pint auto-fixed spacing trong HandleInertiaRequests.php và trailing newline trong AuthenticationTest.php._

### Completion Notes List

- Task 1: Tạo `AuthenticateUser` action với is_active check (null nếu inactive — tránh user enumeration) và `last_login_at` update via forceFill. Đăng ký trong configureActions().
- Task 2: Tạo `LoginResponse` redirect→dashboard với TODO comments cho Epic 5/7. Đăng ký singleton trong register().
- Task 3: Tạo `LogoutResponse` redirect→login (thay vì home). Đăng ký singleton trong register().
- Task 4: Cập nhật HandleInertiaRequests::share() với explicit 9-permission map dùng Spatie can(). Empty array khi unauthenticated (AC8, ADR-019).
- Task 5: Update auth.ts — User.full_name, is_active, last_login_at, AuthPermissions type, Auth.can. Fix 3 Vue components (UserInfo, AppHeader, Profile). Profile.vue: đổi cả name attr → full_name (vì ProfileUpdateRequest validates 'full_name').
- Task 6: Update AuthenticationTest (logout→login, 3 tests mới). Tạo SessionTest (5 tests: redirect, lifetime, auth.can cho manager/admin, guest check).
- Tổng: 61 tests PASS (không regression), TypeScript clean, Pint clean.

### File List

app/Actions/Fortify/AuthenticateUser.php (NEW)
app/Http/Responses/LoginResponse.php (NEW)
app/Http/Responses/LogoutResponse.php (NEW)
app/Providers/FortifyServiceProvider.php (MODIFIED)
app/Http/Middleware/HandleInertiaRequests.php (MODIFIED)
resources/js/types/auth.ts (MODIFIED)
resources/js/components/UserInfo.vue (MODIFIED)
resources/js/components/AppHeader.vue (MODIFIED)
resources/js/pages/Settings/Profile.vue (MODIFIED)
tests/Feature/Auth/AuthenticationTest.php (MODIFIED)
tests/Feature/Auth/SessionTest.php (NEW)

## Change Log

- 2026-04-13: Story 1.2 implemented by claude-sonnet-4-6. Tất cả 6 tasks hoàn thành. 3 files PHP mới (AuthenticateUser, LoginResponse, LogoutResponse), 2 files PHP sửa (FortifyServiceProvider, HandleInertiaRequests), 1 file TS sửa (auth.ts), 3 Vue components sửa (UserInfo, AppHeader, Profile), 1 test file sửa (AuthenticationTest), 1 test file mới (SessionTest). 61 tests PASS.
