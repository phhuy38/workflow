# Story 5.2: View Task Detail with Process Context (FR22)

Status: done

## Story

As an Executor,
I want to view the full details of a task including its context within the larger process,
So that I have enough information to complete my work correctly.

## Acceptance Criteria

1. **Given** Executor click vào một task trong Inbox
   **When** trang task detail load
   **Then** hiển thị đầy đủ: tên bước, mô tả, tên quy trình, người launch, deadline, trạng thái hiện tại
2. **Given** Executor xem task detail
   **When** họ nhìn vào phần "Ngữ cảnh quy trình"
   **Then** họ thấy vị trí bước hiện tại trong flow: "Bước 3/7 — HR làm hợp đồng ✅ → IT cấp thiết bị ▶ → Admin cấp thẻ ⏳"
3. **Given** Executor xem task detail
   **When** có tin nhắn từ Manager (ping từ Story 4.5)
   **Then** tin nhắn hiển thị trong ngữ cảnh task, không bị ẩn
4. **Given** Executor xem task detail trên mobile
   **When** trang render
   **Then** layout một cột, thông tin ưu tiên hiển thị đầu, nút hành động cố định ở bottom của màn hình

## Tasks / Subtasks

- [x] **Backend: Controller & Resource**
  - [x] Có thể tái sử dụng `ProcessInstanceController@show` đã viết cho Manager. Tuy nhiên, Policy (được implement ở Epic 1/Epic 3) cần đảm bảo Executor xem được detail của instance nếu họ có tham gia. Hoặc tạo `TaskDetailController` riêng nếu view tách biệt. (Khuyến nghị: Tái sử dụng `ProcessInstanceController@show` để Executor dùng chung giao diện `Instances/Show.vue` của Manager, vì giao diện đó đã khá hoàn thiện).
  - [x] Nếu dùng chung, hãy đảm bảo `step_executions` eager load đầy đủ `assignee`, `finisher`, `messages`. Điều này đã làm ở Story 4.3 và 4.5.
- [x] **Frontend: Process Context Flow UI**
  - [x] Bổ sung phần UI "Ngữ cảnh quy trình" (Process Context Flow) vào `Show.vue` (hiển thị "Bước 1 ✅ → Bước 2 ▶ → Bước 3 ⏳" ở dạng rút gọn). Giao diện này đặc biệt hữu ích trên mobile nơi timeline dài có thể chiếm diện tích.
  - [x] Đảm bảo phần UI này hiển thị ngay phần đầu trang (dưới Sticky Header) cho cả Manager và Executor.
- [x] **Frontend: Mobile Layout Optimization**
  - [x] Ở màn hình `Show.vue`, điều chỉnh cấu trúc responsive (CSS class như `md:w-1/2`) để trên thiết bị mobile mọi nội dung gập về dạng một cột đơn giản.
  - [x] Gắn sticky nút hành động chính ("Xác nhận", "Hoàn thành") xuống fixed bottom trên màn hình nhỏ.
- [x] **Testing**
  - [x] Cập nhật Test để Executor truy cập vào `ProcessInstanceController@show` thành công với Status 200 (nếu chưa test).
  - [x] Kiểm tra giao diện mobile (thường test thông qua Tailwind classes là đủ).

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
- ✅ Eager loaded `finisher` in `ProcessInstanceController@show`.
- ✅ Process Context Flow UI was already present in `Show.vue` via the Ribbon component.
- ✅ Updated `Show.vue` executor interaction buttons to be fixed at the bottom on mobile screens using tailwind classes.
- ✅ Added `TaskDetailTest.php` to verify executor can see the instance with all context steps and messages.

### File List
- app/Http/Controllers/ProcessInstanceController.php (update)
- resources/js/pages/Instances/Show.vue (update)
- tests/Feature/Process/TaskDetailTest.php (new)

### Change Log
- Added `finisher` to eager loading for step executions.
- Made executor action buttons sticky on mobile bottom.
- Added TaskDetailTest for executor view.

### Review Findings

- [x] [Review][Patch] Global Pusher Connection Memory Leak — `window.Echo.connector.pusher.connection.bind('connected')` in `onMounted` is never unbound in `onUnmounted`, causing memory leaks and duplicate reload calls across the SPA.
- [x] [Review][Patch] Loss of Form Reactivity — `useForm` is instantiated locally inside submit functions (`submitComplete`, `submitOverride`, `submitPing`). This swallows validation errors and prevents disabling buttons during `form.processing`, leading to double submissions.
- [x] [Review][Patch] Manager Override Privilege Regression — Manager controls check `authUser?.id === instance.launched_by` instead of using the `can.override` prop, which locks out admins from overriding steps.
- [x] [Review][Patch] UI State Collision for Manager-Executors — Manager and Executor use the exact same state `showPingForm` and `pingInput`. If a user is both, clicking one opens both boxes and shares input.
- [x] [Review][Patch] Missing Event Debouncing — `useDebounceFn` is imported but not used. Simultaneous events will trigger duplicate `router.reload()` calls.
- [x] [Review][Patch] Undefined Model Properties in Messages — The template accesses `msg.sender_name` and `msg.is_manager`, but standard API resources might nest these or not expose them correctly.
- [x] [Review][Patch] Missing step description in task detail view — Violates AC1. The `Step` interface lacks `description` and it is not rendered in the step card.
- [x] [Review][Patch] Missing Icon for 'Skipped' Status — In the Process Context Flow Ribbon, the `skipped` status has no icon handling.

---

## Technical Guardrails

### Architecture Compliance
- **Code Reuse:** Đừng tạo route và page riêng cho "Task Detail" trừ khi logic cực kỳ khác biệt. Ở hệ thống này, một Task chính là một `StepExecution` nằm trong `ProcessInstance`. Hãy tiếp tục tái sử dụng `resources/js/pages/Instances/Show.vue` (đã xây dựng kỹ ở Epic 3 & 4), kết hợp với Policy cho phép Executor truy cập.

### Performance & UI Requirements
- **Mobile First (UX-DR10):** AC4 nhấn mạnh việc nút hành động fixed bottom. Hãy dùng thẻ div có class `fixed bottom-0 left-0 right-0 p-4 bg-background border-t z-50 md:static md:bg-transparent md:border-none md:p-0 md:z-auto` để wrapper các nút bấm.
- **Process Context Ribbon:** Xây dựng một Ribbon/Breadcrumb nhỏ hiển thị chuỗi các step (`step.order` + icon trạng thái) trải dài theo chiều ngang `overflow-x-auto`.

### Reference Implementation
Đã có component Timeline ở `Show.vue` hiển thị chiều dọc. Hãy thêm một thanh ngang (ngữ cảnh) nhỏ gọn hơn ở trên cùng. Nó giúp Executor có bức tranh tổng thể nhanh nhất trên màn hình hẹp.
