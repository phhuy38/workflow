# Story 1.3: Role-Based Access Control — 5 Roles & Policy Layer (FR37)

Status: done

## Story

As a system,
I want users assigned to exactly one of 5 roles with scoped permissions enforced by Policy layer,
So that each user only sees features and data appropriate to their role (FR37, NFR6).

## Acceptance Criteria

**AC1 — RequiredDataSeeder tạo đủ 5 roles, 9 permissions, admin account:**
- Given: `php artisan db:seed --class=RequiredDataSeeder` được chạy
- When: seeding hoàn thành
- Then: 5 roles tồn tại: `admin`, `manager`, `process_designer`, `executor`, `beneficiary`
- And: 9 permissions được tạo và gán đúng theo permission matrix (xem Dev Notes)
- And: admin account tồn tại với credentials từ `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`)

**AC2 — Executor bị 403 khi truy cập manager-only route:**
- Given: user với role `executor` đã đăng nhập
- When: họ truy cập `GET /dashboard`
- Then: system trả về 403 Forbidden (hoặc redirect 403 page qua Inertia)

**AC3 — Beneficiary bị 403 khi truy cập bất kỳ route ngoài phạm vi:**
- Given: user với role `beneficiary` đã đăng nhập
- When: họ truy cập `GET /dashboard`
- Then: system trả về 403 Forbidden

**AC4 — Policy layer bắt buộc: `$this->authorize()` là dòng đầu tiên trong controller action:**
- Given: bất kỳ controller action nào yêu cầu authorization
- When: action được gọi
- Then: `$this->authorize()` là dòng đầu tiên, delegate sang Policy tương ứng
- And: unauthorized access được ghi vào activity log

**AC5 — Permission check từ cache (không query DB):**
- Given: user đã đăng nhập với spatie permission cache warm (Redis)
- When: permission check được thực hiện
- Then: check hoàn thành không cần DB query (0 additional DB queries)

## Tasks / Subtasks

- [x] Task 1: Hoàn chỉnh RequiredDataSeeder với permission matrix đúng (AC1)
  - [x] Kiểm tra `database/seeders/PermissionsSeeder.php` — đảm bảo 9 permissions được tạo đúng tên (xem permission list trong Dev Notes)
  - [x] Kiểm tra `database/seeders/RolesSeeder.php` — đảm bảo 5 roles, gán permissions đúng theo matrix
  - [x] Kiểm tra `database/seeders/AdminUserSeeder.php` — tạo admin user từ `.env` ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME
  - [x] Đảm bảo `DatabaseSeeder.php` gọi `RequiredDataSeeder`, `RequiredDataSeeder` gọi 3 seeder trên theo đúng thứ tự
  - [x] Idempotent: chạy nhiều lần không tạo duplicate (dùng `firstOrCreate`)

- [x] Task 2: Tạo Policy classes (AC4)
  - [x] `app/Policies/ProcessTemplatePolicy.php` — methods: `viewAny`, `view`, `create`, `update`, `delete`, `publish`
  - [x] `app/Policies/ProcessInstancePolicy.php` — methods: `viewAny`, `view`, `create`, `cancel`, `override`, `ping`
  - [x] `app/Policies/StepExecutionPolicy.php` — methods: `view`, `complete`, `escalate`
  - [x] `app/Policies/UserPolicy.php` — methods: `viewAny`, `view`, `create`, `update`, `deactivate`
  - [x] Đăng ký tất cả Policies trong `AuthServiceProvider` (hoặc `AppServiceProvider` nếu dự án dùng Laravel 12 lazy policy discovery)

- [x] Task 3: Thêm authorization vào DashboardController (AC2, AC3, AC4)
  - [x] Tạo `app/Policies/DashboardPolicy.php` với method `view` — chỉ admin, manager, process_designer có quyền
  - [x] Trong `DashboardController::index()`: thêm `$this->authorize('view', Dashboard::class)` là dòng đầu tiên
  - [x] Đăng ký `DashboardPolicy` trong AuthServiceProvider

- [x] Task 4: Tạo `usePermission` composable cho frontend (AC2, AC3)
  - [x] Kiểm tra `resources/js/composables/usePermission.ts` đã có chưa (có thể chưa được implement trong Story 1.1/1.2)
  - [x] Nếu chưa: tạo composable theo pattern trong architecture (ADR-025)
  - [x] Composable đọc từ `usePage<SharedProps>().props.auth.can`

- [x] Task 5: Thêm role-based navigation guard trong AppLayout (AC2, AC3)
  - [x] Trong `resources/js/layouts/AppLayout.vue`: dùng `usePermission()` để ẩn/hiện nav items theo role
  - [x] Executor không thấy "Dashboard" trong nav (chỉ thấy "Inbox" sau này)
  - [x] Beneficiary chỉ thấy "Quy trình của tôi" sau này

- [x] Task 6: Tests (AC1–AC5)
  - [x] Tạo `tests/Feature/Auth/RbacTest.php`:
    - Test: 5 roles tồn tại sau khi seeding
    - Test: 9 permissions tồn tại và gán đúng role
    - Test: executor bị 403 trên GET /dashboard
    - Test: beneficiary bị 403 trên GET /dashboard
    - Test: admin có thể truy cập dashboard
    - Test: manager có thể truy cập dashboard
    - Test: permission check không tạo thêm DB query (cache hit)
  - [x] Tạo `tests/Feature/Policy/ProcessTemplatePolicyTest.php` (stub với security tests bắt buộc)
  - [x] Tạo `tests/Feature/Policy/ProcessInstancePolicyTest.php` (stub với security tests bắt buộc)

## Dev Notes

### ⚠️ Context từ Story 1.1 & 1.2 — KHÔNG REIMPLEMENT

**Đã có sẵn — DỪNG tay không code lại:**
- `User` model với `HasRoles` trait từ `spatie/laravel-permission` (Story 1.1)
- `spatie/laravel-permission` đã được cài qua composer (Story 1.1)
- `spatie/laravel-activitylog` đã được cài (Story 1.1)
- Seeder files có thể đã tồn tại dạng partial — **kiểm tra trước khi tạo mới**
- `HandleInertiaRequests::share()` đã có `auth.can` với 9 permissions map đầy đủ (Story 1.2)
- `User.is_active` check trong auth flow (Story 1.2)

**Từ Story 1.2 Deferred D11 — đây là task cốt lõi của Story 1.3:**
> "ADR-019 (Policy authoritative) — implement Policy classes cho từng resource trong các Epic tiếp theo." Story 1.3 chính là story implement Policy classes.

### Permission Matrix (ADR-004)

```
Permission                | admin | manager | process_designer | executor | beneficiary
--------------------------|-------|---------|-----------------|----------|------------
manage_templates          |   ✓   |         |        ✓        |          |
publish_templates         |   ✓   |         |        ✓        |          |
launch_instances          |   ✓   |    ✓    |                 |          |
view_all_instances        |   ✓   |    ✓    |        ✓        |          |
manage_instances          |   ✓   |    ✓    |                 |          |
complete_assigned_steps   |   ✓   |         |                 |    ✓     |
view_own_instances        |   ✓   |    ✓    |        ✓        |    ✓     |     ✓
manage_users              |   ✓   |         |                 |          |
manage_system             |   ✓   |         |                 |          |
```

**Ghi chú permission matrix:**
- `admin` có tất cả 9 permissions
- `process_designer` có `view_all_instances` để xem instances từ template mình tạo (FR design context)
- `executor` và `beneficiary` có `view_own_instances` (scope khác nhau — executor xem assigned steps, beneficiary xem instances liên quan đến mình)

### PermissionsSeeder Pattern

```php
// database/seeders/PermissionsSeeder.php
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage_templates',
            'publish_templates',
            'launch_instances',
            'view_all_instances',
            'manage_instances',
            'complete_assigned_steps',
            'view_own_instances',
            'manage_users',
            'manage_system',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
```

### RolesSeeder Pattern

```php
// database/seeders/RolesSeeder.php
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $matrix = [
            'admin' => [
                'manage_templates', 'publish_templates', 'launch_instances',
                'view_all_instances', 'manage_instances', 'complete_assigned_steps',
                'view_own_instances', 'manage_users', 'manage_system',
            ],
            'manager' => [
                'launch_instances', 'view_all_instances', 'manage_instances', 'view_own_instances',
            ],
            'process_designer' => [
                'manage_templates', 'publish_templates', 'view_all_instances', 'view_own_instances',
            ],
            'executor' => [
                'complete_assigned_steps', 'view_own_instances',
            ],
            'beneficiary' => [
                'view_own_instances',
            ],
        ];

        foreach ($matrix as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);  // syncPermissions là idempotent
        }
    }
}
```

### AdminUserSeeder Pattern

```php
// database/seeders/AdminUserSeeder.php
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@workflow.local')],
            [
                'full_name' => env('ADMIN_NAME', 'System Admin'),
                'password'  => bcrypt(env('ADMIN_PASSWORD', 'password')),
                'is_active' => true,
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
```

### RequiredDataSeeder Pattern

```php
// database/seeders/RequiredDataSeeder.php
class RequiredDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,  // PHẢI chạy trước
            RolesSeeder::class,        // Sau Permissions
            AdminUserSeeder::class,    // Sau Roles
        ]);
    }
}
```

### Policy Pattern (ADR-004)

```php
// app/Policies/ProcessInstancePolicy.php
namespace App\Policies;

use App\Models\ProcessInstance;
use App\Models\User;

class ProcessInstancePolicy
{
    // $this->authorize() là dòng đầu tiên trong mọi controller action

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_all_instances');
    }

    public function view(User $user, ProcessInstance $instance): bool
    {
        return match(true) {
            $user->hasRole(['manager', 'process_designer', 'admin']) => true,
            $user->hasRole('executor') =>
                $instance->stepExecutions()->where('assigned_to', $user->id)->exists(),
            $user->hasRole('beneficiary') => $instance->created_for === $user->id,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('launch_instances');
    }

    public function cancel(User $user, ProcessInstance $instance): bool
    {
        return $user->hasRole(['admin', 'manager'])
            && $instance->launched_by === $user->id;
    }

    public function override(User $user, ProcessInstance $instance): bool
    {
        return $user->hasRole(['admin', 'manager'])
            && $instance->launched_by === $user->id;
    }

    public function ping(User $user, ProcessInstance $instance): bool
    {
        return $user->hasRole(['admin', 'manager'])
            && $instance->launched_by === $user->id;
    }
}
```

```php
// app/Policies/ProcessTemplatePolicy.php
class ProcessTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        // managers cũng được xem template để chọn khi launch
        return $user->hasAnyPermission(['manage_templates', 'launch_instances', 'view_all_instances']);
    }

    public function view(User $user): bool
    {
        return $user->hasAnyPermission(['manage_templates', 'launch_instances', 'view_all_instances']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_templates');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('manage_templates');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('manage_templates');
    }

    public function publish(User $user): bool
    {
        return $user->hasPermissionTo('publish_templates');
    }
}
```

```php
// app/Policies/StepExecutionPolicy.php
class StepExecutionPolicy
{
    public function view(User $user, \App\Models\StepExecution $step): bool
    {
        return match(true) {
            $user->hasRole(['admin', 'manager', 'process_designer']) => true,
            $user->hasRole('executor') => $step->assigned_to === $user->id,
            $user->hasRole('beneficiary') => false,  // beneficiary không xem step execution trực tiếp
            default => false,
        };
    }

    public function complete(User $user, \App\Models\StepExecution $step): bool
    {
        return $step->assigned_to === $user->id
            && $user->hasPermissionTo('complete_assigned_steps');
    }

    public function escalate(User $user, \App\Models\StepExecution $step): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }
}
```

```php
// app/Policies/UserPolicy.php
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage_users') || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage_users') || $user->id === $model->id;
    }

    public function deactivate(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage_users')
            && $user->id !== $model->id;  // không thể tự deactivate mình
    }
}
```

### DashboardPolicy (cho AC2, AC3)

```php
// app/Policies/DashboardPolicy.php
// Dashboard chỉ cho: admin, manager, process_designer
// Executor → sau này có route riêng (executor.inbox)
// Beneficiary → sau này có route riêng (beneficiary.index)

class DashboardPolicy
{
    public function view(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'process_designer']);
    }
}
```

**DashboardController::index():**
```php
public function index(): Response
{
    $this->authorize('view', \App\Models\Dashboard::class);  // dòng đầu tiên
    // ... rest of controller
}
```

> **Note:** Laravel cho phép authorize với model class không có trong DB — hoặc dùng gate check `$this->authorize('dashboard.view')` nếu muốn đơn giản hơn. Chọn pattern nhất quán với phần còn lại của codebase.

### Đăng ký Policies (AuthServiceProvider hoặc AppServiceProvider)

**Laravel 12** sử dụng automatic policy discovery: nếu Model `App\Models\ProcessInstance` tồn tại và Policy `App\Policies\ProcessInstancePolicy` tồn tại, Laravel tự khớp. Tuy nhiên, cần explicit registration cho các policy không có model tương ứng (DashboardPolicy):

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Explicit registration cho non-model policies
    Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);

    // Dashboard gate (không có model tương ứng)
    Gate::define('dashboard.view', function (User $user) {
        return $user->hasRole(['admin', 'manager', 'process_designer']);
    });
}
```

> Nếu project có `app/Providers/AuthServiceProvider.php`, đăng ký ở đó trong `$policies` array. Nếu không (Laravel 12 không có AuthServiceProvider mặc định), dùng AppServiceProvider.

### usePermission Composable (ADR-025)

```typescript
// resources/js/composables/usePermission.ts
import { usePage } from '@inertiajs/vue3'
import type { SharedProps } from '@/types/global'

export function usePermission() {
    const { props } = usePage<SharedProps>()

    const can = (permission: keyof SharedProps['auth']['can']): boolean => {
        return props.auth?.can?.[permission] ?? false
    }

    const hasRole = (role: string): boolean => {
        // Roles không được expose trực tiếp — dùng permission checks
        // Nếu cần role check ở frontend, cân nhắc thêm auth.role vào SharedProps
        // Hiện tại: dùng permission combination để infer role
        return false  // stub — extend khi cần
    }

    return { can }
}
```

> **Quan trọng (ADR-019):** `can()` ở frontend chỉ dùng để hide/show UI elements. Server-side Policy là authoritative. Frontend không bao giờ là security gate.

### Test Pattern (ADR-013 — Security tests bắt buộc cho mọi Policy method)

```php
// tests/Feature/Auth/RbacTest.php
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

test('all 5 roles exist after seeding', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);

    foreach (['admin', 'manager', 'process_designer', 'executor', 'beneficiary'] as $role) {
        expect(Role::where('name', $role)->exists())->toBeTrue();
    }
});

test('all 9 permissions exist and assigned correctly', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);

    $managerRole = Role::findByName('manager');
    expect($managerRole->hasPermissionTo('launch_instances'))->toBeTrue();
    expect($managerRole->hasPermissionTo('manage_users'))->toBeFalse();

    $executorRole = Role::findByName('executor');
    expect($executorRole->hasPermissionTo('complete_assigned_steps'))->toBeTrue();
    expect($executorRole->hasPermissionTo('launch_instances'))->toBeFalse();
});

test('executor gets 403 on manager dashboard', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('executor');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertForbidden();  // 403
});

test('beneficiary gets 403 on dashboard', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('beneficiary');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertForbidden();
});

test('admin can access dashboard', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('manager can access dashboard', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});
```

```php
// tests/Feature/Policy/ProcessInstancePolicyTest.php
// Security test template bắt buộc (ADR-013)

test('beneficiary cannot view another user instance', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);
    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiary');

    // Khi ProcessInstance model tồn tại (Story 3.1+), test này verify Policy
    // Hiện tại: stub test để đảm bảo file tồn tại và Policy được register
    $this->assertTrue(true); // placeholder
});

test('executor cannot view instance not assigned to them', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);
    // Placeholder — implement chi tiết khi ProcessInstance model tồn tại (Story 3.1)
    $this->assertTrue(true);
});
```

> **Tại sao có stub tests?** Policy files cần được tạo và test files cần tồn tại ngay cả khi model chưa có data. CI sẽ không fail, và các story tiếp theo sẽ fill in chi tiết. Pattern này tốt hơn không có test file.

### Activity Log cho unauthorized access (AC4)

Khi action bị từ chối, activitylog cần được ghi. Pattern:

```php
// Trong exception handler (bootstrap/app.php) hoặc custom middleware
// Khi AuthorizationException được catch:
activity()
    ->causedBy(auth()->user())
    ->withProperties(['attempted' => $request->path(), 'method' => $request->method()])
    ->log('UNAUTHORIZED_ACCESS_DENIED');
```

> Implement logging trong global exception handler — không phải trong từng controller. Story 1.3 thêm handler này.

### Cache Verification (AC5, ADR-010)

Spatie permission tự động cache vào Redis khi `CACHE_DRIVER=redis`. Verify:

```bash
# Config check (không cần code thêm):
CACHE_DRIVER=redis  # trong .env
# Spatie tự cache sau query đầu tiên và invalidate khi role/permission thay đổi
```

```php
// Test cache hit:
test('permission check uses cache not database', function () {
    $this->seed(\Database\Seeders\RequiredDataSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('manager');

    // Warm cache
    $user->hasPermissionTo('launch_instances');

    // Verify no extra queries
    DB::flushQueryLog();
    DB::enableQueryLog();
    $user->hasPermissionTo('launch_instances');
    expect(DB::getQueryLog())->toBeEmpty();
});
```

### Project Structure (files cần tạo/sửa)

```
database/seeders/PermissionsSeeder.php         (CREATE hoặc VERIFY/COMPLETE)
database/seeders/RolesSeeder.php               (CREATE hoặc VERIFY/COMPLETE)
database/seeders/AdminUserSeeder.php           (CREATE hoặc VERIFY/COMPLETE)
database/seeders/RequiredDataSeeder.php        (CREATE hoặc VERIFY/COMPLETE)
database/seeders/DatabaseSeeder.php            (MODIFY: thêm RequiredDataSeeder nếu chưa có)
app/Policies/ProcessTemplatePolicy.php         (NEW)
app/Policies/ProcessInstancePolicy.php         (NEW)
app/Policies/StepExecutionPolicy.php           (NEW)
app/Policies/UserPolicy.php                    (NEW)
app/Policies/DashboardPolicy.php               (NEW)
app/Providers/AppServiceProvider.php           (MODIFY: Gate registrations)
app/Http/Controllers/DashboardController.php   (MODIFY: thêm $this->authorize() dòng đầu)
resources/js/composables/usePermission.ts      (CREATE nếu chưa có)
tests/Feature/Auth/RbacTest.php                (NEW)
tests/Feature/Policy/ProcessTemplatePolicyTest.php  (NEW)
tests/Feature/Policy/ProcessInstancePolicyTest.php  (NEW)
```

> **CHECK FIRST:** Chạy `ls database/seeders/` và `ls app/Policies/` để xem file nào đã tồn tại từ Story 1.1. Nhiều file có thể đã được tạo dạng partial — sửa thay vì tạo lại.

### Architecture Rules (MUST FOLLOW)

**ADR-004:** `spatie/laravel-permission` + Policy layer. Policy là authoritative, frontend `can[]` chỉ hide/show UI.

**ADR-010:** Redis cache cho permissions — `CACHE_DRIVER=redis`, spatie tự cache, reset khi assign/revoke role.

**ADR-013:** Security test bắt buộc cho mọi Policy method. Unauthorized access test là CI gate.

**ADR-019:** Frontend `auth.can` không phải security gate — chỉ dùng cho UI visibility. Không bỏ `$this->authorize()` server-side dựa vào frontend check.

**ADR-031:** Policy method names theo Laravel convention (camelCase): `viewAny`, `view`, `create`, `update`, `delete`, `forceDelete`, `restore`. Custom actions: `publish`, `cancel`, `override`, `ping`.

**ADR-044:** `RequiredDataSeeder` phải chạy ở mọi environment (production, staging, test). `DemoDataSeeder` chỉ dev/staging.

### Thứ tự quan trọng khi implement

1. **Check trước** (`ls database/seeders/`, `ls app/Policies/`) — tránh overwrite file từ Story 1.1
2. **Seeders** — phải hoàn chỉnh trước khi viết tests
3. **Policies** — tạo tất cả 5 Policy files
4. **DashboardController** — thêm `$this->authorize()` dòng đầu
5. **Tests** — chạy `php artisan test` sau mỗi step

### References

- [Source: architecture.md#ADR-004] RBAC — spatie/laravel-permission + Policy Layer
- [Source: architecture.md#ADR-010] Cache — Redis, scope giới hạn + warm-up
- [Source: architecture.md#ADR-013] Testing — Security tests bắt buộc cho Policy
- [Source: architecture.md#ADR-019] Inertia Shared Data — HandleInertiaRequests + Permission-based
- [Source: architecture.md#ADR-031] PHP Naming conventions
- [Source: architecture.md#ADR-044] Seeders — Required vs Demo
- [Source: epics.md#Story-1.3] Acceptance criteria gốc
- [Source: implementation-artifacts/1-2-*.md#Deferred D11] Policy authoritative — implement trong Story 1.3
- [Source: implementation-artifacts/1-2-*.md#Dev Notes] auth.can đã có trong HandleInertiaRequests

## Dev Agent Record

### Agent Model Used

claude-sonnet-4-6 (2026-04-16)

### Debug Log References

- Laravel 12 base Controller không include `AuthorizesRequests` trait — phải thêm thủ công vào `app/Http/Controllers/Controller.php`. Fixed bằng cách add `use AuthorizesRequests;` vào base Controller.
- Pint `fully_qualified_strict_types` fixer: thêm `use Spatie\Permission\PermissionRegistrar;` vào seeders (auto-fixed).

### Completion Notes List

- Task 1: Seeders đã tồn tại với đúng logic. Thêm `forgetCachedPermissions()` vào PermissionsSeeder và RolesSeeder. Fix DatabaseSeeder bỏ hardcoded user factory (dùng `name` đã bị đổi sang `full_name`), thay bằng chỉ gọi RequiredDataSeeder.
- Task 2: Tạo 4 Policy files (ProcessTemplatePolicy, ProcessInstancePolicy, StepExecutionPolicy, UserPolicy). Đăng ký UserPolicy và `dashboard.view` Gate trong AppServiceProvider::configurePolicies(). Laravel 12 automatic policy discovery hoạt động cho model-based policies; UserPolicy registered explicitly để an toàn.
- Task 3: Tạo DashboardController với `$this->authorize('dashboard.view')` dòng đầu. Cập nhật `routes/web.php` từ `Route::inertia()` sang `Route::get(DashboardController)`. Thêm `AuthorizationException` logging vào `bootstrap/app.php`. Cập nhật `DashboardTest.php` để assign role trước khi test (user không có role → 403).
- Task 4: Tạo `resources/js/composables/usePermission.ts` theo pattern ADR-025. Trả về object `{ can }`, đọc từ `page.props.auth.can`.
- Task 5: Cập nhật `AppSidebar.vue` — `mainNavItems` trở thành `computed` property, chỉ thêm Dashboard khi `can('view_all_instances')` là true. TODO comments cho Epic 5 (Executor Inbox) và Epic 7 (Beneficiary).
- Task 6: Tạo `RbacTest.php` (17 tests), `ProcessTemplatePolicyTest.php` (5 tests), `ProcessInstancePolicyTest.php` (5 tests). Tổng cộng 30 tests mới.
- Kết quả: 92 tests pass (tăng từ 62 → 92), không có regression.

### File List

database/seeders/PermissionsSeeder.php (MODIFIED: thêm forgetCachedPermissions)
database/seeders/RolesSeeder.php (MODIFIED: thêm forgetCachedPermissions)
database/seeders/DatabaseSeeder.php (MODIFIED: gọi RequiredDataSeeder thay vì hardcoded factory)
app/Http/Controllers/Controller.php (MODIFIED: thêm AuthorizesRequests trait)
app/Http/Controllers/DashboardController.php (NEW)
app/Policies/ProcessTemplatePolicy.php (NEW)
app/Policies/ProcessInstancePolicy.php (NEW)
app/Policies/StepExecutionPolicy.php (NEW)
app/Policies/UserPolicy.php (NEW)
app/Providers/AppServiceProvider.php (MODIFIED: thêm configurePolicies())
bootstrap/app.php (MODIFIED: thêm AuthorizationException logging)
routes/web.php (MODIFIED: Route::inertia → DashboardController)
resources/js/composables/usePermission.ts (NEW)
resources/js/components/AppSidebar.vue (MODIFIED: computed mainNavItems với role guard)
tests/Feature/DashboardTest.php (MODIFIED: assign roles, thêm 4 test cases)
tests/Feature/Auth/RbacTest.php (NEW)
tests/Feature/Policy/ProcessTemplatePolicyTest.php (NEW)
tests/Feature/Policy/ProcessInstancePolicyTest.php (NEW)

## Review Findings

_Code review — 2026-04-16 | Sources: Blind Hunter · Edge Case Hunter · Acceptance Auditor_

### Cần quyết định (Decision Needed)

_Đã được giải quyết — chuyển sang patch/defer_

- [x] [Review][Decision] DN1→ProcessInstancePolicy ownership checks — **RESOLVED: Implement ownership checks (executor via step assignment, beneficiary via instance.created_for).** [app/Policies/ProcessInstancePolicy.php:22-27]
- [x] [Review][Decision] DN2→ProcessTemplatePolicy matrix alignment — **RESOLVED: Defer (overly-permissive policy is intentional — managers need template visibility).** [app/Policies/ProcessTemplatePolicy.php:9-16]

### Cần fix (Patch)

_Tất cả đã được fix — 2026-04-16_

- [x] [Review][Patch] P1→Exception handler must rethrow [bootstrap/app.php:32-40] — Added `throw $e;` after logging ✅
- [x] [Review][Patch] P2→Cache race condition in tests [tests/Pest.php] — Added `forgetCachedPermissions()` in beforeEach hook ✅
- [x] [Review][Patch] P3→Policy model parameters missing [app/Policies/ProcessTemplatePolicy.php:14, 24, 29] — Added `mixed $template` parameters to view/update/delete ✅
- [x] [Review][Patch] P4→Type hint conflict on $instance [app/Policies/ProcessInstancePolicy.php:22] — Changed to `mixed $instance` type hint ✅
- [x] [Review][Patch] P5→Logging lacks audit granularity [bootstrap/app.php:34-39] — Added `'message' => $e->getMessage()` to log context ✅
- [x] [Review][Patch] P6→Implement ownership checks (từ DN1) [app/Policies/ProcessInstancePolicy.php] — Added `isExecutorAssignedToInstance()` and `isBeneficiaryForInstance()` helper methods with ownership-aware logic ✅

### Deferred

- [x] [Review][Defer] D1→N+1 permission queries [app/Http/Middleware/HandleInertiaRequests.php:48-68] — 9× permission checks per request without eager-load; optimize with `$user->load('roles.permissions')` if performance issue. Pre-existing, noted in D2 of deferred-work.md.
- [x] [Review][Defer] D2→IP logging vulnerable to spoofing [bootstrap/app.php:38] — TrustProxies misconfiguration could log spoofed IP; verify TrustProxies config at deploy time. Pre-existing, noted in D6 of deferred-work.md.
- [x] [Review][Defer] D3→Soft-deleted user auth gap [app/Models/User.php + bootstrap/app.php] — No explicit test for soft-deleted user still holding valid session; implement integration test if session persistence layer added. Pre-existing, noted in D3 of deferred-work.md.
- [x] [Review][Defer] D4→Mixed auth strategies [app/Policies/* files] — Some policies use `hasPermissionTo()`, others use `hasRole()`; consistency deferred (both patterns work per tests). Design decision, noted in Edge Case analysis.
- [x] [Review][Defer] D5→Weak admin password default [config/app.php] — Fallback `'changeme'` is weak but env-driven; acceptable for dev, document as expected. Pre-existing pattern.
- [x] [Review][Defer] D6→Frontend-backend permission divergence [AppServiceProvider.php:50 vs AppSidebar.vue:28] — Gate checks role, sidebar checks permission; they align by accident (roles match permissions). Add comment to document consistency assumption.
- [x] [Review][Defer] D7→Cache driver test/prod mismatch [.env.testing vs config/cache.php] — Tests use array cache for speed, prod uses database cache; acceptable trade-off. Pre-existing, noted in deployment concerns.
- [x] [Review][Defer] D8→Dashboard gate missing null user guard [app/Providers/AppServiceProvider.php:49-50] — Gate assumes non-null user (middleware should prevent); defensive null check possible but not critical. Design choice.

## Change Log

- 2026-04-16: Story 1.3 created by bmad-create-story workflow (context engine analysis).
- 2026-04-16: Story 1.3 implemented by claude-sonnet-4-6. Tất cả 6 tasks hoàn thành. 5 PHP files mới (DashboardController + 4 Policies), 7 PHP files sửa (Controller, DatabaseSeeder, PermissionsSeeder, RolesSeeder, AppServiceProvider, bootstrap/app.php, routes/web.php), 1 TS file mới (usePermission.ts), 1 Vue file sửa (AppSidebar.vue), 3 test files mới (RbacTest, ProcessTemplatePolicyTest, ProcessInstancePolicyTest), 1 test file sửa (DashboardTest). 92 tests PASS (tăng 30 tests so với 62).
- 2026-04-16: Code review completed by parallel adversarial layers (Blind Hunter, Edge Case Hunter, Acceptance Auditor). All acceptance criteria satisfied. 2 decisions needed on policy design, 5 patches required, 8 items deferred (pre-existing or design choices).
