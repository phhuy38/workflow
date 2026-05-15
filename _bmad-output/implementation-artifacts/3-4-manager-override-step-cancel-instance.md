# Story 3.4: Manager override step & cancel instance (FR13, FR14, FR15)

Status: ready-for-dev

## Story

As a Manager,
I want to be able to override (force complete) a stalled step or cancel an entire instance,
so that I can handle exceptions, stalled processes, or errors without waiting for the assigned Executor.

## Acceptance Criteria

1. **Given** một bước đang ở trạng thái 'pending' hoặc 'in_progress', **When** Manager của instance đó (người đã launch) nhấn "Override", nhập lý do bắt buộc và xác nhận, **Then** trạng thái của `step_execution` chuyển sang 'skipped' (hoặc 'completed' với cờ override), **And** ghi chú hoàn thành lưu lý do override, **And** `completed_by` lưu ID của Manager, **And** bước tiếp theo được tự động kích hoạt (FR13, FR12 tái sử dụng từ 3.3).
2. **Given** một instance đang chạy ('running' hoặc 'paused'), **When** Manager nhấn "Hủy quy trình" (Cancel), nhập lý do bắt buộc và xác nhận, **Then** trạng thái của `ProcessInstance` chuyển sang 'cancelled', **And** tất cả các bước đang 'pending'/'in_progress' bị hủy bỏ, **And** instance không còn tiếp tục. (FR14)
3. **Given** hành động override bước hoặc cancel quy trình, **When** hệ thống lưu dữ liệu, **Then** Activity Log ghi nhận đầy đủ hành động, người thực hiện (Manager) và lý do (FR15, ADR-005).
4. **Given** UI chi tiết Instance, **When** Manager xem, **Then** các nút "Override" (ở mỗi bước chưa hoàn thành) và "Hủy quy trình" (ở mức instance) có thể truy cập trực tiếp (Inline action pattern UX-DR3).
5. **Given** một người dùng không phải là Manager đã khởi tạo instance (hoặc không phải Admin), **When** họ cố gắng override hoặc cancel, **Then** hệ thống trả về 403 Forbidden.

## Tasks / Subtasks

- [x] **Domain Logic: Actions (ADR-017)**
  - [x] Tạo Action `App\Actions\Process\OverrideStep`. Logic: validate quyền -> cập nhật bước (chuyển state sang `Skipped` hoặc `Completed` kèm note) -> ghi Activity Log -> gọi `AdvanceProcessInstance` để đi tiếp.
  - [x] Tạo Action `App\Actions\Process\CancelInstance`. Logic: validate quyền -> chuyển state của Instance sang `Cancelled` -> chuyển state của các bước đang pending/in_progress sang `Cancelled` hoặc `Skipped` -> ghi Activity Log.
- [x] **State Machine Constraints (ADR-016)**
  - [x] Đảm bảo `ProcessInstanceState` hỗ trợ chuyển từ `Running` -> `Cancelled`.
  - [x] Đảm bảo `StepExecutionState` hỗ trợ chuyển từ `Pending`/`InProgress` -> `Skipped` (hoặc state tương ứng cho override).
- [x] **Web Layer: Controller & Routes (ADR-031)**
  - [x] Thêm các endpoint (POST) trong `ProcessInstanceController` (cho cancel) và `StepExecutionController` (cho override). Ví dụ: `/process-instances/{instance}/cancel` và `/step-executions/{step}/override`.
  - [x] Cập nhật `ProcessInstancePolicy` và `StepExecutionPolicy` để cấp quyền cho Manager (và Admin).
  - [x] Validate request data: bắt buộc có trường `reason` (string, min length).
- [x] **Frontend: UI Updates (Vue/Inertia)**
  - [x] Thêm form/modal/dialog trong `resources/js/pages/Instances/Show.vue` cho tính năng Override (trên từng dòng của Table các bước).
  - [x] Thêm form/modal/dialog trong `Show.vue` (hoặc header) cho tính năng Cancel Instance.
  - [x] Implement UI UX-DR3: Cung cấp nút ngay trên màn hình chi tiết, có visual feedback rõ ràng khi thao tác thành công.
- [x] **Testing**
  - [x] Viết Feature tests cho tính năng Override: Thành công tiến bước tiếp theo, bắt buộc có lý do, kiểm tra phân quyền (chỉ Manager sở hữu/Admin).
  - [x] Viết Feature tests cho tính năng Cancel: Thành công hủy quy trình, các bước chưa hoàn thành cũng bị dừng, bắt buộc có lý do, kiểm tra phân quyền.

## Dev Notes

### Architecture Compliance
- **ADR-016 (States):** Quản lý nghiêm ngặt các transition hợp lệ. Tránh để step có trạng thái in_progress khi instance đã cancelled.
- **ADR-005 (Audit Log):** Đặc biệt quan trọng với các hành động mang tính chất can thiệp như override/cancel. Phải truyền lý do vào log properties nếu có thể, hoặc ghép vào description.
- **Reusability:** Sử dụng lại `AdvanceProcessInstance` action từ story 3.3.

### Project Structure Notes
- Actions: `app/Actions/Process/`

### References
- [PRD: FR13, FR14, FR15, UX-DR3](_bmad-output/planning-artifacts/prd.md)
- [Epics: Story 3.4](_bmad-output/planning-artifacts/epics.md)
Status: done

...
- [x] [Review][Decision] Broad Manager Override/Cancel Policy — `ProcessInstancePolicy` allows *any* user with the 'manager' role to cancel/override, violating AC1/AC5 which restrict it to the instance owner (`launched_by`) or an Admin.
- [x] [Review][Patch] Incomplete Cancel Instance Action — `CancelInstance` only targets 'pending' and 'in_progress' steps. It leaves 'blocked' or 'escalated' steps active, creating zombie states.
- [x] [Review][Patch] Missing State Transitions — `ProcessInstanceState` is missing `Paused` -> `Cancelled`. `StepExecutionState` is missing `Blocked`/`Escalated` -> `Skipped`, causing `TransitionNotFound` crashes.
- [x] [Review][Patch] Inaccurate Progress Calculation — `ProcessInstanceResource::getProgress` ignores `Skipped` steps, breaking the completion percentage when overrides are used.
- [x] [Review][Patch] Missing Cancel UI — The "Cancel Instance" button and form were completely omitted from `Show.vue`. (Already present in code)
- [x] [Review][Patch] Missing Vue Script Logic — `Show.vue` added template logic for `showOverrideForm` and `submitOverride` but omitted the corresponding script definitions. (Already present in code)
- [x] [Review][Patch] Missing Policy Implementations — Policy files were completely omitted from the commit, leading to 403 Forbidden/MethodNotFound errors.
- [x] [Review][Defer] Double Update/Missing Transaction Lock in Override — `OverrideStep` mutates state then transitions without explicit locking, though `transitionTo` handles the save. Potential future refactor to explicit `save()` if Spatie library changes.


## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
N/A

### File List
- app/Actions/Process/OverrideStep.php (new)
- app/Actions/Process/CancelInstance.php (new)
- app/Http/Controllers/ProcessInstanceController.php (update)
- app/Http/Controllers/StepExecutionController.php (update)
- resources/js/pages/Instances/Show.vue (update)
- tests/Feature/Process/OverrideAndCancelTest.php (new)
