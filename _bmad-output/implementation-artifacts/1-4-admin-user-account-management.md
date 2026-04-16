# Story 1.4: Admin — Quản Lý Tài Khoản Người Dùng (FR33, FR34)

Status: done

## Story

Là Admin,
Tôi muốn tạo, chỉnh sửa, deactivate tài khoản người dùng và gán/thu hồi role Process Designer,
Để kiểm soát ai có quyền truy cập hệ thống và họ có thể làm gì (FR33, FR34).

## Acceptance Criteria

**AC1 — Tạo tài khoản người dùng mới:**
- Given: Admin trên trang user management
- When: Admin tạo user mới với full_name, email, password, và role
- Then: tài khoản được tạo, user có thể đăng nhập

**AC2 — Deactivate user (FR33) với cascading effects:**
- Given: Admin deactivate một user
- When: deactivation được xác nhận
- Then: user đó không thể đăng nhập (`is_active = false`)
- And: tất cả `step_executions` đang assigned cho user có `assigned_to = null`, `status = 'pending'` _(xử lý gracefully nếu bảng chưa tồn tại — Story 3.x)_
- And: log activity ghi lại admin_id, user_id, timestamp

**AC3 — Gán role Process Designer (FR34):**
- Given: Admin gán role `process_designer` cho user
- When: assignment được lưu
- Then: user có thể truy cập Template management features
- And: activity log ghi lại ai gán, cho ai, lúc nào

**AC4 — Thu hồi role Process Designer (FR34):**
- Given: Admin thu hồi role `process_designer`
- When: revocation được lưu
- Then: user mất quyền truy cập Template features ngay lập tức
- And: activity log ghi lại ai thu hồi, thời điểm

**AC5 — RBAC Protection:**
- Given: non-admin user
- When: họ truy cập bất kỳ user management route
- Then: system trả về 403 Forbidden

**AC6 — Admin không thể deactivate chính mình:**
- Given: Admin đang đăng nhập
- When: họ cố gắng deactivate tài khoản của chính mình
- Then: system từ chối với error message rõ ràng (422)

**AC7 — Xem danh sách users:**
- Given: Admin truy cập `/admin/users`
- When: trang load
- Then: hiển thị danh sách users với: Name, Email, Role, Status (active/inactive), Last Login, Actions

## Tasks / Subtasks

- [x] Task 1: Backend — UserController và Routes (AC1, AC5, AC7)
  - [x] Tạo `app/Http/Controllers/Admin/UserController.php` với các actions: index, create, store, edit, update, show
  - [x] Tạo `app/Http/Requests/StoreUserRequest.php` — validate: full_name required, email unique, password min 8, role phải là valid role
  - [x] Tạo `app/Http/Requests/UpdateUserRequest.php` — validate tương tự nhưng email unique ignore self
  - [x] Tạo `app/Http/Resources/UserResource.php` — expose: id, full_name, email, is_active, last_login_at, roles, created_at
  - [x] Thêm routes trong `routes/web.php` trong group `admin` với middleware `['auth', 'verified']`
  - [x] Mỗi action phải có `$this->authorize()` là dòng đầu tiên (ADR-004)

- [x] Task 2: Backend — Deactivate User Action (AC2, AC6)
  - [x] Tạo `app/Actions/User/DeactivateUser.php` — nhận User $actor, User $target, kiểm tra actor !== target
  - [x] Set `$target->is_active = false`, call `$target->save()`
  - [x] Dispatch `UserDeactivated` event (tạo event class)
  - [x] Tạo `app/Events/UserDeactivated.php`
  - [x] Tạo `app/Listeners/ReassignOpenStepsOnUserDeactivated.php` — graceful: check if `step_executions` table exists trước khi query; nếu có, set assigned_to = null, status = 'pending'
  - [x] Register event→listener trong `AppServiceProvider::boot()`
  - [x] Thêm route `POST /admin/users/{user}/deactivate` → `UserController@deactivate`
  - [x] Thêm route `POST /admin/users/{user}/reactivate` → `UserController@reactivate`
  - [x] Ghi activity log: `activity()->causedBy($actor)->performedOn($target)->log('user_deactivated')` (ADR-005)

- [x] Task 3: Backend — Role Assignment/Revocation (AC3, AC4)
  - [x] Tạo `app/Actions/User/AssignDesignerRole.php` — chỉ assign `process_designer` role, clear cache sau khi assign
  - [x] Tạo `app/Actions/User/RevokeDesignerRole.php` — revoke `process_designer` role, clear cache
  - [x] Thêm route `POST /admin/users/{user}/assign-designer` → `UserController@assignDesigner`
  - [x] Thêm route `POST /admin/users/{user}/revoke-designer` → `UserController@revokeDesigner`
  - [x] Ghi activity log cho assign và revoke (ADR-005)

- [x] Task 4: Frontend — Vue Pages (AC1, AC7)
  - [x] Tạo `resources/js/pages/Admin/Users/Index.vue` — table với columns: Name, Email, Role badges, Status chip, Actions (Edit, Deactivate/Reactivate)
  - [x] Tạo `resources/js/pages/Admin/Users/Create.vue` — form: full_name, email, password, role selector
  - [x] Tạo `resources/js/pages/Admin/Users/Edit.vue` — form chỉnh sửa + role assignment toggles + deactivate button
  - [x] Thêm link "User Management" vào AppSidebar.vue khi `can('manage_users')` (tương tự pattern Dashboard)
  - [x] Dùng `usePermission()` composable đã có từ Story 1.3

- [x] Task 5: Tests (AC1–AC7)
  - [x] Tạo `tests/Feature/Admin/UserManagementTest.php` — 12 tests, 35 assertions, tất cả pass

## Dev Notes

### ⚠️ KHÔNG REIMPLEMENT — Đã có từ Story 1.1, 1.2, 1.3

**Đã có — DỪNG tay không code lại:**
- `UserPolicy` tại `app/Policies/UserPolicy.php` — methods: viewAny, view, create, update, deactivate ✅
- `spatie/laravel-permission` đã cài, roles/permissions đã seeded via RequiredDataSeeder ✅
- `spatie/laravel-activitylog` đã cài ✅
- `User` model với `HasRoles` trait, `is_active`, `last_login_at`, SoftDeletes ✅
- `usePermission()` composable tại `resources/js/composables/usePermission.ts` ✅
- `AuthorizesRequests` trait trong base `Controller` (thêm ở Story 1.3) ✅

### UserPolicy Đã Có (Không Tạo Lại)

```php
// app/Policies/UserPolicy.php — ĐÃ TỒN TẠI
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_users');  // chỉ admin
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
            && $user->id !== $model->id;  // không tự deactivate
    }
}
```

### UserController Pattern (ADR-004)

```php
// app/Http/Controllers/Admin/UserController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);  // DÒNG ĐẦU TIÊN

        $users = User::with('roles')
            ->orderBy('full_name')
            ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => UserResource::collection($users),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);  // DÒNG ĐẦU TIÊN

        $user = User::create([
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);  // DÒNG ĐẦU TIÊN

        app(DeactivateUser::class)->handle(auth()->user(), $user);

        return back()->with('success', "User {$user->full_name} deactivated.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);  // DÒNG ĐẦU TIÊN

        $user->update(['is_active' => true]);

        activity()->causedBy(auth()->user())
            ->performedOn($user)
            ->log('user_reactivated');

        return back()->with('success', "User {$user->full_name} reactivated.");
    }
}
```

### DeactivateUser Action Pattern

```php
// app/Actions/User/DeactivateUser.php
namespace App\Actions\User;

use App\Events\UserDeactivated;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DeactivateUser
{
    public function handle(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw new AuthorizationException('Admin cannot deactivate themselves.');
        }

        $target->update(['is_active' => false]);

        activity()->causedBy($actor)
            ->performedOn($target)
            ->withProperties(['action' => 'deactivate'])
            ->log('user_deactivated');

        UserDeactivated::dispatch($target);
    }
}
```

### ReassignOpenStepsOnUserDeactivated Listener (Graceful — Story 3.x)

```php
// app/Listeners/ReassignOpenStepsOnUserDeactivated.php
namespace App\Listeners;

use App\Events\UserDeactivated;
use Illuminate\Support\Facades\Schema;

class ReassignOpenStepsOnUserDeactivated
{
    public function handle(UserDeactivated $event): void
    {
        // Graceful: step_executions bảng chưa tồn tại cho đến Story 3.x
        if (!Schema::hasTable('step_executions')) {
            return;
        }

        // TODO Story 3.x: Notify Manager about reassigned steps
        \DB::table('step_executions')
            ->where('assigned_to', $event->user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->update([
                'assigned_to' => null,
                'status'      => 'pending',
            ]);
    }
}
```

### Role Assignment Action Pattern

```php
// app/Actions/User/AssignDesignerRole.php
namespace App\Actions\User;

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class AssignDesignerRole
{
    public function handle(User $actor, User $target): void
    {
        $target->assignRole('process_designer');

        // Clear permission cache (ADR-010)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        activity()->causedBy($actor)
            ->performedOn($target)
            ->withProperties(['role' => 'process_designer'])
            ->log('role_assigned');
    }
}
```

### Routes Pattern

```php
// routes/web.php — thêm vào nhóm auth middleware
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', Admin\UserController::class);
    Route::post('users/{user}/deactivate', [Admin\UserController::class, 'deactivate'])
        ->name('users.deactivate');
    Route::post('users/{user}/reactivate', [Admin\UserController::class, 'reactivate'])
        ->name('users.reactivate');
    Route::post('users/{user}/assign-designer', [Admin\UserController::class, 'assignDesigner'])
        ->name('users.assign-designer');
    Route::post('users/{user}/revoke-designer', [Admin\UserController::class, 'revokeDesigner'])
        ->name('users.revoke-designer');
});
```

### UserResource Pattern

```php
// app/Http/Resources/UserResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'full_name'     => $this->full_name,
            'email'         => $this->email,
            'is_active'     => $this->is_active,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at'    => $this->created_at->toISOString(),
            'roles'         => $this->roles->pluck('name'),
        ];
    }
}
```

### StoreUserRequest Pattern

```php
// app/Http/Requests/StoreUserRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;  // Authorization handled via Policy in controller
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', Password::min(8)],
            'role'      => ['required', Rule::in(['manager', 'process_designer', 'executor', 'beneficiary'])],
            // Admin role không thể được gán qua form tạo user thông thường
        ];
    }
}
```

### Activity Log Pattern (ADR-005 — SYNC, KHÔNG QUEUE)

```php
// ĐÚNG (sync):
activity()->causedBy($actor)->performedOn($target)->log('user_deactivated');

// SAI (không dùng queue/dispatch):
// dispatch(new LogActivity(...));
```

### AppSidebar — Thêm User Management Link

```typescript
// resources/js/components/AppSidebar.vue — thêm vào mainNavItems computed
import { usersIndex } from '@/routes'; // hoặc dùng route helper

if (can('manage_users')) {
    items.push({
        title: 'User Management',
        href: route('admin.users.index'),
        icon: Users, // lucide-vue-next icon
    });
}
```

### Test Pattern cho AC2 (Deactivate)

```php
test('deactivated user cannot login', function () {
    $this->seed(RequiredDataSeeder::class);
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('executor');

    $admin = User::where('email', config('app.admin.email'))->first();

    // Admin deactivates user
    $this->actingAs($admin)
        ->post(route('admin.users.deactivate', $user))
        ->assertRedirect();

    // User cannot login
    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);
    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('admin cannot deactivate themselves', function () {
    $this->seed(RequiredDataSeeder::class);
    $admin = User::where('email', config('app.admin.email'))->first();

    $this->actingAs($admin)
        ->post(route('admin.users.deactivate', $admin))
        ->assertForbidden();
});
```

### Architecture Rules Bắt Buộc

| ADR | Rule |
|-----|------|
| ADR-004 | `$this->authorize()` là dòng đầu tiên của mọi controller action |
| ADR-005 | Activity log SYNC — `queue: false` — không bao giờ dispatch async |
| ADR-010 | Gọi `forgetCachedPermissions()` sau khi assign/revoke role |
| ADR-031 | File naming: `UserController`, `DeactivateUser`, `UserDeactivated`, `ReassignOpenStepsOnUserDeactivated` |
| ADR-033 | Domain exceptions → 422/403; không expose stack trace |
| ADR-038 | `is_active = false` → user không thể login (đã implement ở Story 1.2) |

### File Structure Bắt Buộc

```
app/Http/Controllers/Admin/UserController.php    (NEW)
app/Http/Requests/StoreUserRequest.php           (NEW)
app/Http/Requests/UpdateUserRequest.php          (NEW)
app/Http/Resources/UserResource.php              (NEW)
app/Actions/User/DeactivateUser.php              (NEW)
app/Actions/User/AssignDesignerRole.php          (NEW)
app/Actions/User/RevokeDesignerRole.php          (NEW)
app/Events/UserDeactivated.php                   (NEW)
app/Listeners/ReassignOpenStepsOnUserDeactivated.php (NEW)
app/Policies/UserPolicy.php                      (ALREADY EXISTS — chỉ verify, không sửa)
routes/web.php                                   (MODIFY: thêm admin routes)
resources/js/pages/Admin/Users/Index.vue         (NEW)
resources/js/pages/Admin/Users/Create.vue        (NEW)
resources/js/pages/Admin/Users/Edit.vue          (NEW)
resources/js/components/AppSidebar.vue           (MODIFY: thêm User Management link)
tests/Feature/Admin/UserManagementTest.php       (NEW)
```

### Lưu Ý Quan Trọng

1. **`step_executions` chưa tồn tại** — Listener `ReassignOpenStepsOnUserDeactivated` phải kiểm tra `Schema::hasTable('step_executions')` trước khi query. Không được throw exception nếu bảng chưa có.

2. **Chỉ gán/thu hồi `process_designer` role** — Theo FR34, admin chỉ quản lý role này qua assign/revoke. Các role khác (manager, executor, beneficiary) được gán khi tạo user qua form. Admin role được gán qua AdminUserSeeder.

3. **SoftDeletes** — User model có SoftDeletes. Deactivate ≠ Delete. Deactivate chỉ set `is_active = false`. Không dùng `delete()`.

4. **Password trong UserResource** — KHÔNG expose password, two_factor_secret, two_factor_recovery_codes, remember_token trong resource.

5. **Pagination** — Sử dụng `->paginate(20)` trong index, đừng `->get()` cho user list.

### References

- [ADR-004] RBAC — Policy Layer (architecture.md)
- [ADR-005] Audit Log — Sync, no queue (architecture.md)
- [ADR-010] Redis Cache (architecture.md)
- [ADR-031] PHP Naming Conventions (architecture.md)
- [ADR-038] User Deactivation Handler (architecture.md)
- [Story 1.3] UserPolicy đã implement: app/Policies/UserPolicy.php
- [Story 1.2] is_active check trong auth flow: app/Actions/Fortify/AuthenticateUser.php
- [deferred-work.md] D11 — Policy đã implement; D2 — N+1 cần eager-load roles

## Dev Agent Record

### Agent Model Used

claude-haiku-4-5-20251001

### Debug Log References

- Cần publish và run migration `create_activity_log_table` trước khi test — migration không có sẵn trong project, phải chạy `php artisan vendor:publish --tag="activitylog-migrations"` rồi `APP_ENV=testing php artisan migrate`

### Completion Notes List

- Tất cả 5 tasks hoàn tất, 104 tests pass (bao gồm 12 tests mới cho Story 1.4)
- Migration `create_activity_log_table` đã được publish và chạy
- AppSidebar cập nhật với `Users` icon từ lucide-vue-next, chỉ visible khi `can('manage_users')`

### File List

- `app/Http/Controllers/Admin/UserController.php` (NEW)
- `app/Http/Requests/StoreUserRequest.php` (NEW)
- `app/Http/Requests/UpdateUserRequest.php` (NEW)
- `app/Http/Resources/UserResource.php` (NEW)
- `app/Actions/User/DeactivateUser.php` (NEW)
- `app/Actions/User/AssignDesignerRole.php` (NEW)
- `app/Actions/User/RevokeDesignerRole.php` (NEW)
- `app/Events/UserDeactivated.php` (NEW)
- `app/Listeners/ReassignOpenStepsOnUserDeactivated.php` (NEW)
- `routes/web.php` (MODIFIED)
- `app/Providers/AppServiceProvider.php` (MODIFIED)
- `resources/js/pages/Admin/Users/Index.vue` (NEW)
- `resources/js/pages/Admin/Users/Create.vue` (NEW)
- `resources/js/pages/Admin/Users/Edit.vue` (NEW)
- `resources/js/components/AppSidebar.vue` (MODIFIED)
- `resources/js/routes/admin/users/index.ts` (AUTO-GENERATED by wayfinder)
- `tests/Feature/Admin/UserManagementTest.php` (NEW)
- `database/migrations/2026_04_16_065255_create_activity_log_table.php` (NEW — vendor publish)

### Review Findings

- [ ] [Review][Decision] DN1: Self-deactivate ném 403 thay vì 422 như AC6 spec yêu cầu — `DeactivateUser` throws `AuthorizationException` → 403, nhưng spec nói "422 Unprocessable Entity" cho business rule violation. Chọn: giữ 403 (authorization semantics) hay đổi sang 422?
- [x] [Review][Patch] P1: Session active user không bị invalidate sau deactivation — fixed: thêm `DB::table('sessions')->where('user_id', $target->id)->delete()` trong `DeactivateUser::handle()` [app/Actions/User/DeactivateUser.php]
- [x] [Review][Patch] P2: Vue `isDesigner` computed đọc prop stale sau role change — fixed: thêm `preserveState: false` vào `handleAssignDesigner` và `handleRevokeDesigner` [resources/js/pages/Admin/Users/Edit.vue]
- [x] [Review][Patch] P3: `assignDesigner` dùng policy `update` → user có thể self-assign process_designer — fixed: đổi sang `authorize('viewAny', User::class)` (admin-only) [app/Http/Controllers/Admin/UserController.php]
- [x] [Review][Patch] P4: Thiếu test AC2 (deactivated user không login được) và AC5 (executor nhận 403 trên user management routes) — fixed: thêm 3 tests, 107 tests pass [tests/Feature/Admin/UserManagementTest.php]
- [x] [Review][Defer] D1: `reactivate()` inline — không có `UserReactivated` event, pattern không nhất quán với `deactivate()` [app/Http/Controllers/Admin/UserController.php:217] — deferred, pre-existing design choice
- [x] [Review][Defer] D2: `Schema::hasTable()` DDL query mỗi lần `UserDeactivated` fire — không cached [app/Listeners/ReassignOpenStepsOnUserDeactivated.php:13] — deferred, intentional graceful guard per Story 3.x
- [x] [Review][Defer] D3: Pagination: deactivate user trên last page → redirect back() hiển thị "Page N of N-1" — deferred, UX edge case
- [x] [Review][Defer] D4: Race condition: deactivate + assign-designer đồng thời để lại user inactive với process_designer role — deferred, concurrent admin scenario ngoài MVP scope
- [x] [Review][Defer] D5: Soft-deleted user email vẫn block `unique:users,email` validation — deferred, pre-existing, không có delete UI trong Story 1.4
- [x] [Review][Defer] D6: Thiếu activity log khi tạo user mới — deferred, AC1 không yêu cầu tường minh, inconsistency nhỏ
- [x] [Review][Defer] D7: Double-deactivate re-fires `UserDeactivated` event và log — không idempotent — deferred, low risk

## Change Log

- 2026-04-16: Story 1.4 created by bmad-create-story. Context analysis from epics.md, architecture.md, prd.md, ux-design-specification.md, và story 1.3 patterns.
