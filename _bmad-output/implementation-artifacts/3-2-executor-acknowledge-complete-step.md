# Story 3.2: Executor acknowledge/complete step (FR10, FR11, FR12, FR15)

Status: in-progress

## Story

As an Executor,
I want to acknowledge and complete the steps assigned to me,
so that I can fulfill my tasks and move the process forward.

## Acceptance Criteria

1. **Given** Executor được giao một task, **When** họ nhấn "Xác nhận nhận việc" (Acknowledge), **Then** trạng thái của `step_execution` chuyển từ `pending` sang `in_progress`, **And** `started_at` được ghi nhận với thời điểm hiện tại.
2. **Given** Executor đang thực hiện một task (in_progress), **When** họ nhấn "Hoàn thành" kèm ghi chú (tùy chọn), **Then** trạng thái của `step_execution` chuyển sang `completed`, **And** `completed_at` được ghi nhận, **And** `completed_by` lưu ID của Executor đó.
3. **Given** một bước vừa được hoàn thành, **When** hệ thống lưu dữ liệu, **Then** hệ thống tự động kích hoạt bước tiếp theo (nếu có) bằng cách tạo `step_execution` mới cho bước có `order` tiếp theo (FR12), **And** nếu không còn bước nào, trạng thái của `ProcessInstance` chuyển sang `completed` và ghi nhận `completed_at`.
4. **Given** Executor truy cập chi tiết task, **When** họ xem thông tin, **Then** họ thấy đầy đủ context của quy trình (tên instance, ghi chú context).
5. **Given** một hành động xác nhận hoặc hoàn thành, **When** hệ thống lưu dữ liệu, **Then** Activity Log ghi nhận chi tiết hành động đó (FR15, ADR-005).
6. **Given** User không phải là người được gán cho task, **When** họ cố xác nhận hoặc hoàn thành task, **Then** hệ thống trả về 403 Forbidden (NFR6).

## Tasks / Subtasks

- [x] **Domain Logic: Acknowledge & Complete Actions (ADR-017)**
  - [x] Tạo action `App\Actions\Process\AcknowledgeStep`.
  - [x] Tạo action `App\Actions\Process\CompleteStep`.
  - [x] Xử lý logic chuyển đổi trạng thái State Machine (ADR-016).
  - [x] Xử lý logic tự động kích hoạt bước tiếp theo (FR12) hoặc kết thúc quy trình.
  - [x] Ghi Activity Log cho từng hành động (ADR-005).
- [x] **Web Layer: Controller & Routes (ADR-031)**
  - [x] Tạo `StepExecutionController` với các method: `acknowledge`, `complete`.
  - [x] Đăng ký routes trong `web.php` (nhóm dưới `/process-instances/{instance}/steps/{step}`).
  - [x] Tạo `StepExecutionPolicy` để kiểm soát quyền (chỉ người được gán mới có quyền thực thi).
- [x] **Frontend: Executor Task UI (Vue/Inertia)**
  - [x] Cập nhật trang chi tiết `resources/js/pages/Instances/Show.vue`.
  - [x] Hiển thị nút "Xác nhận nhận việc" khi trạng thái là `pending`.
  - [x] Hiển thị nút "Hoàn thành" và form nhập ghi chú khi trạng thái là `in_progress`.
  - [x] UX-DR4: Progressive disclosure cho ghi chú hoàn thành.
  - [x] UX-DR12: Visual feedback rõ ràng khi hoàn thành.
- [x] **Testing**
  - [x] Viết Pest Feature test cho luồng Acknowledge.
  - [x] Viết Pest Feature test cho luồng Complete (bao gồm auto-trigger bước sau).
  - [x] Viết Security test đảm bảo đúng người đúng việc.

## Dev Notes

### Architecture Compliance
- **ADR-016 (States):** Sử dụng `transitionTo` của Spatie Model States để thay đổi trạng thái.
- **ADR-017 (Actions):** Logic chuyển bước (trigger next step) phải nằm hoàn toàn trong Action class.
- **ADR-005 (Audit Log):** Log mọi thay đổi trạng thái bước thực thi.

### Project Structure Notes
- Actions: `app/Actions/Process/`
- Controller: `app/Http/Controllers/StepExecutionController.php`
- Policy: `app/Policies/StepExecutionPolicy.php`

### References
- [PRD: FR10, FR11, FR12, FR15](_bmad-output/planning-artifacts/prd.md#vận-hành-quy-trình--instance-execution-fr7fr15)
- [Architecture: ADR-016, ADR-017](_bmad-output/planning-artifacts/architecture.md#architecture-decision-records)

### Review Findings

- [ ] [Review][Decision] StepExecutionPolicy completeness — Policy only checks `assigned_to` for `acknowledge/complete`. Does not account for instance status (Cancelled/Paused) or upcoming Manager overrides.
- [ ] [Review][Decision] Index View Filter — ProcessInstanceController only shows instances launched by the user. Hides instances where the user is an executor.
- [ ] [Review][Decision] Activity Log assertions — Missing explicit assertions in StepExecutionTest for activity logs.
- [ ] [Review][Patch] Snapshot Bypass — CompleteStep uses live template instead of `$instance->template_snapshot_data` to find next step. Violates ADR-006.
- [ ] [Review][Patch] Missing Context Display — AC4 violated: ProcessInstanceResource lacks context_data and Show.vue doesn't display it.
- [ ] [Review][Patch] Missing Notes Display — Show.vue doesn't display `completion_notes` on completed steps.
- [ ] [Review][Patch] Double DB Updates — AcknowledgeStep and CompleteStep call transitionTo() then update(), causing redundant queries.
- [ ] [Review][Patch] N+1 Query in Resource — ProcessInstanceResource methods trigger N+1 queries on step_executions.
- [ ] [Review][Patch] Hardcoded Order Assumption — LaunchProcessInstance assumes first step order is 1.
- [ ] [Review][Patch] SoftDelete vs Cascade — ProcessInstance uses SoftDeletes but step_executions migration uses physical cascade.
- [ ] [Review][Patch] Vue Syntax Error — Duplicate closing tags in Create.vue.
- [x] [Review][Defer] Role-Coupled Authorization Flaw — ProcessInstancePolicy restricts view based on role, not just assignment. Pre-existing. — deferred, pre-existing

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
N/A

### File List
- app/Actions/Process/AcknowledgeStep.php (new)
- app/Actions/Process/CompleteStep.php (new)
- app/Http/Controllers/StepExecutionController.php (new)
- app/Policies/StepExecutionPolicy.php (new)
- resources/js/pages/Instances/Show.vue (update)
- tests/Feature/Process/StepExecutionTest.php (new)
