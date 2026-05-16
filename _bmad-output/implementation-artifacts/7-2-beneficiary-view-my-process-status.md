# Story 7.2: Beneficiary View — My Process Status (FR24, FR25)

Status: done

## Story

As a Beneficiary,
I want to see the status of the process being run on my behalf,
So that I know what's happening, who's responsible for the next step, and approximately when it will be done.

## Acceptance Criteria
1. **Given** Beneficiary đăng nhập, **When** họ mở trang chủ, **Then** họ được điều hướng đến trang danh sách quy trình của mình, và chỉ thấy các quy trình liên quan đến mình (nơi `created_for === user->id`).
2. **Given** Beneficiary xem danh sách quy trình của mình (FR24), **When** trang load, **Then** mỗi quy trình hiển thị: tên quy trình, trạng thái tổng thể, bước hiện tại, % hoàn thành.
3. **Given** Beneficiary click vào một quy trình (FR25), **When** trang detail load, **Then** họ thấy: timeline các bước đã hoàn thành và đang chờ, tên người phụ trách bước hiện tại, deadline ước tính, **And** **không thấy**: nút hành động "Hoàn thành", "Nhận việc", "Nhắc việc", hay "Cưỡng chế".
4. **Given** Beneficiary cố truy cập URL của instance không liên quan đến mình, **When** request được gửi, **Then** system trả về 403 Forbidden (Policy kiểm tra `instance->created_for === user->id`).
5. **Given** quy trình của Beneficiary đã hoàn thành, **When** họ xem, **Then** trạng thái "Đã hoàn thành" hiển thị rõ cùng timeline đầy đủ.

## Technical Requirements
- [x] **Backend / Controllers**:
  - [x] Update `DashboardController` or the root redirect in `routes/web.php`. If a user has the `beneficiary` role, they should be redirected to their process instances list or a specific beneficiary dashboard.
  - [x] Update `ProcessInstanceController@index` to filter by `created_for === auth()->id()` if the user is a beneficiary.
- [x] **Backend / Policy**:
  - [x] Update `ProcessInstancePolicy@view` to allow access if `$user->hasRole('beneficiary')` AND `$instance->created_for === $user->id`.
  - [x] Update `ProcessInstancePolicy@viewAny` to allow beneficiaries (so they can access the index page).
- [x] **Frontend / Vue**:
  - [x] Since beneficiaries don't need the complex Manager Dashboard filters, you can either reuse `Instances/Index.vue` with reduced features based on role, or create a dedicated `Beneficiary/Instances.vue`. (Reusing `Instances/Index.vue` is fine if you just pass a `$page.props.auth.user.roles` check to hide manager tabs).
  - [x] On `Instances/Show.vue`, ensure Action Buttons (Override, Ping, Complete) are strictly hidden for beneficiaries. (They are currently guarded by `instance.launched_by` and `step.assigned_to`, so it should naturally work, but verify no sensitive "edit" forms leak).

## Architecture Compliance
- [x] Keep authorization logic strictly inside `ProcessInstancePolicy`.
- [x] Ensure real-time Echo listeners in `Show.vue` do not break if the user doesn't have permission to certain private channels, though `user.{id}` and `instance.{id}` should be authorized in `channels.php`.

## Previous Story Intelligence
- [x] In Story 7.1, we implemented the auto-linking of `created_for` on `ProcessInstance` when a beneficiary account is created.
- [x] The `Show.vue` page was heavily refactored in Epic 4 and 5 to support Manager and Executor views. It is robust enough to serve Beneficiaries if the guards (`v-if`) are correct.

## File Structure Requirements
- `app/Http/Controllers/ProcessInstanceController.php` (update index filtering)
- `app/Policies/ProcessInstancePolicy.php` (update view and viewAny)
- `routes/web.php` or `DashboardController` (beneficiary redirect)
- `routes/channels.php` (verify channel authorization for beneficiaries)
- `resources/js/pages/Instances/Index.vue` (hide manager tools for beneficiaries)
- `resources/js/pages/Instances/Show.vue` (verify visual guards)

## Testing Requirements
- [x] Write a Pest feature test `tests/Feature/Beneficiary/ProcessStatusTest.php`.
- [x] Test that a beneficiary can see their own instance (`Status: 200`).
- [x] Test that a beneficiary gets `403 Forbidden` when trying to view someone else's instance.
- [x] Test that the index page only returns instances where `created_for` matches their ID.

---

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
- ✅ Modified `DashboardController` to automatically redirect beneficiaries to their instances list `/process-instances`.
- ✅ Updated `ProcessInstanceController@index` to specifically filter instances where `created_for === auth()->id()` if the user is a beneficiary.
- ✅ Updated `ProcessInstancePolicy@viewAny` and `@view` to allow beneficiary access strictly tied to `created_for` checks.
- ✅ Added logic in `Instances/Index.vue` to hide the "Launch Instance" button using Inertia auth `can` prop.
- ✅ Updated the empty state message in `Instances/Index.vue` to be role-agnostic ("Chưa có quy trình nào đang chạy liên quan đến bạn").
- ✅ Created Pest feature tests in `ProcessStatusTest.php` to prove access control logic isolates instances securely.

### File List
- app/Http/Controllers/DashboardController.php (update)
- app/Http/Controllers/ProcessInstanceController.php (update)
- app/Policies/ProcessInstancePolicy.php (update)
- resources/js/pages/Instances/Index.vue (update)
- tests/Feature/Beneficiary/ProcessStatusTest.php (new)

### Change Log
- Secure beneficiary dashboard isolation and role-based redirects.

### Review Findings

- [x] [Review][Patch] RBAC Priority Inversion in `ProcessInstanceController::index` — If a user has both `admin` and `beneficiary` roles, the first `if` statement scopes their query to only instances they own, stripping their administrative visibility. Restructure to prioritize higher privileges.
- [x] [Review][Patch] Strict Type Comparison Logic Bugs in `ProcessInstancePolicy` — `$instance->created_for === $user->id` uses strict comparison. If the database driver returns strings for bigints, this evaluates to false, locking users out. Change `===` to `==` for these foreign key checks.
- [x] [Review][Patch] Missing Eager Loading in `index` — `ProcessInstanceController@index` does not eager load `stepExecutions`, causing an N+1 query issue when `ProcessInstanceResource` attempts to calculate progress for the index view. Add `stepExecutions` to the `with()` array.
- [x] [Review][Patch] Missing `ping` authorization in frontend payload — In `ProcessInstanceController@show`, the `can` array does not include the `ping` policy check, requiring the frontend to guess whether the "Nhắc việc" button should be displayed based on data ownership instead of the definitive Policy.
- [x] [Review][Dismiss] DashboardController Redirect Priority — If a user is both an executor and a beneficiary, they are redirected to `/process-instances` and can't access `/inbox` easily from the dashboard URL. Dismissed for MVP as multi-role conflicts are handled via direct URL navigation.

---
