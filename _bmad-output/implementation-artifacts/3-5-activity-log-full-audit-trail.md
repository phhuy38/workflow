# Story 3.5: Activity log full audit trail (FR15)

Status: review

## Story

As a Manager or Admin,
I want to view a complete audit trail of all activities related to a process instance,
so that I can track who did what and when, ensuring transparency and accountability.

## Acceptance Criteria

1. **Given** một instance có nhiều sự kiện (launch, acknowledge, complete, override, cancel), **When** Manager xem chi tiết instance đó, **Then** họ thấy một phần "Lịch sử hoạt động" (Activity Log / Timeline) hiển thị danh sách tất cả các sự kiện này.
2. **Given** phần hiển thị Activity Log, **When** hệ thống render dữ liệu, **Then** mỗi dòng log phải hiển thị: thời gian (format dễ đọc), người thực hiện (causer), hành động (description), và chi tiết bổ sung (ví dụ: lý do override/cancel từ properties).
3. **Given** danh sách log, **When** hiển thị, **Then** danh sách được sắp xếp theo thời gian mới nhất xếp trước (hoặc cũ nhất xếp trước tùy thiết kế timeline, nhưng phải nhất quán).
4. **Given** dữ liệu log của instance và các bước con (step_executions), **When** query dữ liệu, **Then** hệ thống phải tổng hợp cả log của ProcessInstance và log của các StepExecution thuộc về instance đó thành một timeline duy nhất.
5. **Given** một người dùng chỉ là Executor hoặc Beneficiary, **When** họ xem chi tiết task/quy trình, **Then** họ KHÔNG thấy toàn bộ Activity Log chi tiết như Manager (theo UX-DR9: "không hiển thị granular tracking cho Executor").

## Tasks / Subtasks

- [ ] **Domain Logic & Data Retrieval**
  - [ ] Cập nhật `ProcessInstanceController@show` để load thêm dữ liệu Activity Log.
  - [ ] Viết logic query để gộp log của `ProcessInstance` và các `StepExecution` liên quan (sử dụng Polymorphic relationships của spatie/laravel-activitylog), sắp xếp theo `created_at` DESC.
  - [ ] Tạo `ActivityLogResource` để format dữ liệu trả về cho Frontend (xử lý translation các description thành tiếng Việt dễ đọc, xử lý causer name).
- [ ] **Frontend: Timeline UI (Vue/Inertia)**
  - [ ] Cập nhật `resources/js/pages/Instances/Show.vue` để thêm một tab hoặc section mới cho "Lịch sử hoạt động".
  - [ ] Thiết kế một UI Component dạng Timeline (có thể dùng UI lib hoặc custom CSS) để hiển thị danh sách các sự kiện.
  - [ ] Ẩn section này nếu user không có quyền view_full_log (kiểm tra qua biến `can` truyền từ controller).
- [ ] **Testing**
  - [ ] Viết Feature tests kiểm tra endpoint trả về đúng và đủ log từ cả instance và steps.
  - [ ] Viết test xác nhận dữ liệu được sắp xếp đúng thứ tự thời gian.
  - [ ] Viết Security tests đảm bảo Executor/Beneficiary không nhận được dữ liệu full log này trong props.

## Dev Notes

### Architecture Compliance
- **ADR-005 (Audit Log):** Sử dụng model `Spatie\Activitylog\Models\Activity`.
- Lấy log tổng hợp: Có thể query `Activity::where('subject_type', ProcessInstance::class)->where('subject_id', $id)->orWhere(function($q) { $q->where('subject_type', StepExecution::class)->whereIn('subject_id', $stepIds); })->with('causer')->latest()->get()`.
- **UX-DR9:** Đảm bảo ẩn với Executor.

### Project Structure Notes
- Resource: `app/Http/Resources/ActivityResource.php`

### References
- [PRD: FR15, UX-DR9](_bmad-output/planning-artifacts/prd.md)
- [Epics: Story 3.5](_bmad-output/planning-artifacts/epics.md)
Status: done

...
### Review Findings

- [x] [Review][Patch] TS Interface Missing — `resources/js/pages/Instances/Show.vue` uses `activities` and `can.view_full_log` in the template but did not update the `Props` interface in `<script setup>`, breaking TypeScript compilation.
- [x] [Review][Patch] System-wide User Attribute Bug — `User` model uses `full_name`, but `ActivityResource`, `ProcessInstanceResource`, and `StepExecutionResource` attempt to access `$user->name`.
- [x] [Review][Patch] Nested Properties Rendering — Spatie Activitylog often tracks model changes inside nested JSON objects (`attributes`, `old`). Naive `v-for` rendering in `Show.vue` will display `[object Object]`.
- [x] [Review][Patch] Brittle Polymorphic Type Checking — `ActivityResource` uses strict string comparison for FQCN (`$this->subject_type === ProcessInstance::class`). Should use `$this->subject instanceof ProcessInstance`.
- [x] [Review][Patch] Non-deterministic Sorting — `latest()` only sorts by `created_at`. Needs `orderByDesc('id')` for events occurring in the same second.
- [x] [Review][Defer] Hardcoded Translations — `ActivityResource` hardcodes Vietnamese strings instead of using Laravel's localization system.
- [x] [Review][Defer] Fat Controller — The polymorphic ORM query in `ProcessInstanceController` should be extracted to a dedicated Service or Action class.
- [x] [Review][Defer] Scale & Performance Risks — Missing pagination for activity logs, which could degrade performance for long-running processes.

## Dev Agent Record
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
N/A

### File List
- app/Http/Resources/ActivityResource.php (new)
- app/Http/Controllers/ProcessInstanceController.php (update)
- resources/js/pages/Instances/Show.vue (update)
- tests/Feature/Process/ActivityLogTest.php (new)
new)
