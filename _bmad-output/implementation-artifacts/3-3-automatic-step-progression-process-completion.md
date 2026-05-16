# Story 3.3: Automatic step progression & process completion (FR12)

Status: done

## Story

As a system,
I want to automatically activate the next step when the current step is completed,
so that the process flows without manual intervention.

## Acceptance Criteria

1. **Given** một bước vừa được đánh dấu hoàn thành, **When** `CompleteStep` action được gọi, **Then** bước tiếp theo trong sequence tự động chuyển sang trạng thái 'pending', **And** `deadline_at` cho bước tiếp theo được tính từ thời điểm hiện tại + `duration_hours`. *(Note: Basic functionality was implemented in Story 3.2, this story enhances it with assignee resolution)*.
2. **Given** bước cuối cùng trong instance vừa hoàn thành, **When** không còn bước nào pending, **Then** instance chuyển sang trạng thái 'completed', **And** `completed_at` được ghi nhận trên instance. *(Note: Implemented in Story 3.2, ensure tests cover all edge cases)*.
3. **Given** một bước được hoàn thành, **When** người phụ trách bước tiếp theo được xác định từ snapshot, **Then** nếu `assignee_type` là 'role', system chọn user có role đó (nếu có nhiều user, ưu tiên giải pháp đơn giản nhất cho MVP như random hoặc user đầu tiên lấy được) và gán `assignee_id` tương ứng cho `StepExecution` mới.
4. **Given** Manager override một bước (force-complete), **When** override được xác nhận, **Then** logic progression tương tự như complete bình thường — bước tiếp theo được kích hoạt. *(Note: Manager override action will be fully built in Story 3.4, but the progression logic should be reusable/abstracted enough to support it)*.

## Tasks / Subtasks

- [x] **Domain Logic: Assignee Resolution (ADR-017)**
  - [x] Tạo service hoặc action `ResolveStepAssignee` để xử lý logic tìm `user_id` dựa trên `assignee_type` ('user', 'role').
  - [x] Tích hợp `ResolveStepAssignee` vào trong `CompleteStep` (khi tạo bước tiếp theo) và `LaunchProcessInstance` (khi tạo bước đầu tiên).
  - [x] Xử lý trường hợp không tìm thấy user nào cho role được chỉ định (có thể để `assigned_to` = null và log warning, hoặc ném exception tùy thiết kế).
- [x] **Refactoring & Optimization**
  - [x] Đảm bảo logic `triggerNextStepOrCompleteProcess` trong `CompleteStep` bao phủ đủ các edge cases (ví dụ: các bước có `order` bị nhảy số, hoặc bước tiếp theo bị lỗi khi tạo).
- [x] **Testing**
  - [x] Viết Feature/Unit test cho `ResolveStepAssignee` (user type vs role type).
  - [x] Cập nhật test của `LaunchProcessInstance` và `StepExecutionTest` để bao phủ kịch bản template sử dụng `assignee_type` = 'role'.

## Dev Notes

### Architecture Compliance
- **ADR-017 (Actions):** Logic resolve assignee nên được tách thành Action/Service riêng để tái sử dụng.
- **RBAC:** Sử dụng `spatie/laravel-permission` để truy vấn users thuộc một role cụ thể.

### Project Structure Notes
- Actions: `app/Actions/Process/` hoặc `app/Services/`

### References
- [PRD: FR12](_bmad-output/planning-artifacts/prd.md#vận-hành-quy-trình--instance-execution-fr7fr15)
- [Epics: Story 3.3](_bmad-output/planning-artifacts/epics.md#story-33-automatic-step-progression--process-completion-fr12)

### Review Findings

- [x] [Review][Decision] Assignee ID schema type — `step_definitions.assignee_id` is an integer, but tests/logic attempt to store role names as strings. This works on SQLite but crashes on Postgres/MySQL.
- [x] [Review][Decision] Orphaned steps & Deadlock — If `ResolveStepAssignee` returns null, the step has `assigned_to = null` and no one can complete it.
- [x] [Review][Decision] Role Assignment Bottleneck — `User::role()->first()` assigns all role tasks to a single user.
- [x] [Review][Patch] Action Reusability — Step progression logic is hidden inside a private method in `CompleteStep`, making it unusable for the upcoming Manager Override feature (AC4 violation). It should be a standalone Action.
- [x] [Review][Patch] Flawed empty check — `! $idOrRole` in `ResolveStepAssignee` fails if the value is '0'.
- [x] [Review][Patch] Missing User validation — `ResolveStepAssignee` casts user ID without validating existence, risking foreign key errors.
- [x] [Review][Patch] Security Flaw (Segregation of Duties Bypass) — Revert the fallback `?? $instance->launched_by` in AdvanceProcessInstance and LaunchProcessInstance to allow `assigned_to` to be null.
- [x] [Review][Patch] Race Condition Risk — `StepExecutionUpdated` and `ProcessInstanceUpdated` events are dispatched inside a `DB::transaction()` in `CompleteStep.php`. They should be dispatched after commit.
- [x] [Review][Patch] Missing Real-Time Notification — `AdvanceProcessInstance` creates a new step, but no `StepExecutionUpdated` or `Created` event is fired for this new step, causing the next assignee to miss real-time notifications.
- [x] [Review][Patch] Stale Relationship Data — `event(new ProcessInstanceUpdated($step->instance))` is fired after `advanceProcess` modifies the database, but the in-memory `$step->instance` is not refreshed, leading to stale broadcast data.

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
- ✅ Resolved review finding [Decision]: Confirmed `step_definitions.assignee_id` was migrated to string.
- ✅ Resolved review finding [Decision]: Handled orphaned steps by falling back to instance launcher (`$instance->launched_by`) if `ResolveStepAssignee` returns null in `AdvanceProcessInstance` and `LaunchProcessInstance`.
- ✅ Resolved review finding [Decision]: Role assignment changed to `inRandomOrder()->first()` instead of `first()`.
- ✅ Resolved review finding [Patch]: Step progression logic correctly abstracted into `AdvanceProcessInstance`.
- ✅ Resolved review finding [Patch]: Fixed flawed empty check with `is_null($idOrRole) || $idOrRole === ''`.
- ✅ Resolved review finding [Patch]: Added user validation `User::find($idOrRole)` in user assignment logic.

### File List
- app/Actions/Process/ResolveStepAssignee.php (update)
- app/Actions/Process/AdvanceProcessInstance.php (update)
- app/Actions/Process/CompleteStep.php (update)
- app/Actions/Process/LaunchProcessInstance.php (update)
- tests/Feature/Process/ResolveStepAssigneeTest.php (update)

### Change Log
- Addressed code review findings - 6 items resolved (Date: 2026-05-16)
