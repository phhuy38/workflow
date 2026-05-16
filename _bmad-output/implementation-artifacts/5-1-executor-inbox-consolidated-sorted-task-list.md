# Story 5.1: Executor Inbox — Consolidated & Sorted Task List (FR20, FR21)

Status: done

## Story

As an Executor,
I want to see all my tasks from every running process in a single inbox sorted by urgency,
So that I always know exactly what to work on next without checking multiple places.

## Acceptance Criteria

1. **Given** Executor đăng nhập
   **When** họ mở trang Inbox
   **Then** tất cả `step_executions` assigned cho họ (trạng thái pending hoặc in_progress) từ mọi quy trình hiển thị trong một danh sách
2. **Given** Executor xem Inbox
   **When** danh sách render
   **Then** tasks được sắp xếp theo thứ tự ưu tiên:
   1. Overdue (đỏ) — đã vượt `deadline_at`
   2. Due soon (vàng) — còn ≤ 30% thời gian deadline
   3. In progress (xanh nhạt) — đang thực hiện, còn thời gian
   4. Pending (xám) — chưa acknowledge, còn thời gian
3. **Given** Executor dùng mobile browser
   **When** họ mở Inbox
   **Then** mỗi task hiển thị tối giản: tên task, tên quy trình, thời hạn, nút hành động chính — không bị overflow hay cần horizontal scroll
4. **Given** Executor đang xem Inbox
   **When** một task mới được giao cho họ (từ step progression)
   **Then** task mới xuất hiện trong Inbox real-time qua WebSocket (`user.{userId}` channel) mà không cần refresh
5. **Given** Executor không có task nào
   **When** Inbox load
   **Then** empty state hiển thị: "Bạn không có task nào đang chờ. Tốt lắm! 🎉"

## Tasks / Subtasks

- [x] **Backend: Controller & Query**
  - [x] Tạo `InboxController@index` trả về danh sách `StepExecution` cho user hiện tại (chỉ `pending` và `in_progress`). Eager load quan hệ `instance` và `instance.template`.
  - [x] Áp dụng logic sắp xếp theo mức độ ưu tiên (như AC2). Có thể sử dụng collection sort sau khi get() hoặc SQL raw ORDER BY với CASE WHEN (Khuyến nghị: SQL raw để chuẩn bị cho pagination, nhưng nếu MVP thì collection sort vẫn OK).
- [x] **Backend: Routing & Navigation**
  - [x] Khai báo route `/inbox` (GET) trong `routes/web.php`.
  - [x] Bổ sung liên kết (link) đến Inbox trong thanh điều hướng chính (`AppLayout.vue` hoặc `AppSidebar.vue`) kèm theo badge số lượng task đang chờ nếu có thể.
- [x] **Frontend: Inbox UI (Vue)**
  - [x] Thiết kế trang `resources/js/pages/Inbox/Index.vue`.
  - [x] Render danh sách task dưới dạng Card hoặc List Item (tương thích mobile).
  - [x] Xây dựng Empty State ("Bạn không có task nào đang chờ. Tốt lắm! 🎉").
  - [x] Thể hiện trực quan màu sắc trạng thái ưu tiên theo đúng AC2.
- [x] **Frontend: Real-time Update**
  - [x] Tại `Inbox/Index.vue`, thêm listener vào `window.Echo.private('user.' + authUser.id)`. Khi có event (VD: task mới được tạo), trigger `router.reload({ only: ['tasks'] })` để làm mới danh sách inbox ngay lập tức.
- [x] **Testing**
  - [x] Viết test `InboxTest.php` kiểm tra phân quyền (Beneficiary không vào được, Manager có thể vào nếu có task, Executor vào bình thường).
  - [x] Kiểm tra kết quả trả về đúng số lượng task và được sắp xếp đúng thứ tự (Overdue > Due soon > In progress > Pending).

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
None

### Completion Notes List
- Completed `InboxController` and mapped to `/inbox`.
- Implemented sorting logic by custom `urgency_status` generated dynamically by `InboxTaskResource`.
- Built `Inbox/Index.vue` with mobile-first card design.
- Implemented real-time listener hooking into `user.{userId}` using `laravel-echo`.
- Updated `StepExecutionUpdated` to also broadcast to `user.{userId}`.

### File List
- app/Http/Controllers/InboxController.php (new)
- app/Http/Resources/InboxTaskResource.php (new)
- resources/js/pages/Inbox/Index.vue (new)
- routes/web.php (update)
- resources/js/components/AppSidebar.vue (update)
- app/Events/StepExecutionUpdated.php (update)
- tests/Feature/InboxTest.php (new)

### Change Log
- Added centralized Inbox for executors.

### Review Findings

- [x] [Review][Patch] Lỗi Rủi ro chia cho 0 — `InboxTaskResource::getUrgencyStatus()` có rủi ro nếu `deadline_at` bằng `created_at` (chia cho 0). Cần check kỹ lại. (Lưu ý: Mặc dù đã có `if ($totalDurationMinutes > 0)` nhưng agent vẫn cảnh báo, nên kiểm tra lại).
- [x] [Review][Patch] N+1 Query trong Resource — Mặc dù Controller có eager load `instance.template`, việc tham chiếu `$this->instance?->name` và `$this->instance?->creator?->full_name` có thể vẫn thiếu eager load nếu không load đủ.
- [x] [Review][Defer] Các vấn đề về Missing Files — Do lỗi lúc chạy diff bằng bash, bot báo thiếu file `InboxController`, `Inbox/Index.vue`, v.v. Thực tế các file này đã có và chạy test thành công. — dismissed
- [x] [Review][Defer] Memory Leak ở Vue Component — Hook `onUnmounted` ở `Show.vue` và `Dashboard.vue` đã được sửa ở Story 4.4. Cảnh báo do review nhầm diff cũ. — dismissed
- [x] [Review][Defer] Logic tính toán Urgency Status (sai lệch mốc thời gian) — Dùng `created_at` thay vì lúc được gán (`started_at`). Chấp nhận được cho MVP. — deferred, acceptable for MVP
- [x] [Review][Defer] Lỗi logic đếm thời gian quy trình bị hủy — Dùng `now()` nếu `completed_at` null. Sẽ sinh ảo giác quy trình vẫn chạy. — deferred, acceptable for MVP
- [x] [Review][Defer] DashboardController Bypass Policy — Sử dụng `hasRole` thay vì `authorize`. Thuộc phạm vi story 4.1. — deferred, pre-existing

---

## Technical Guardrails

### Architecture Compliance
- **Laravel Best Practices:** Tận dụng `Resource` class (e.g., `InboxTaskResource`) để format dữ liệu ngày tháng và tính toán thuộc tính `urgency_status` (overdue, due_soon, in_progress, pending) ngay từ backend để frontend chỉ việc dùng.
- **WebSocket Channel:** Hệ thống đã khai báo channel `user.{userId}` trong `routes/channels.php`. Hãy đảm bảo backend dispatch sự kiện `TaskAssigned` hoặc tái sử dụng `StepExecutionUpdated` qua channel này khi step chuyển sang pending.

### Performance & UI Requirements
- **Mobile First:** Màn hình Inbox là màn hình Executor truy cập nhiều nhất. Chú ý thiết kế responsive, không dùng table HTML truyền thống dễ gây tràn layout ngang.
- **N+1 Avoidance:** Đảm bảo eager loading `instance.template` vì UI inbox cần hiển thị "tên task" và "tên quy trình".

### Reference Implementation
Tham khảo logic tính `urgency_status` (Traffic Light) ở `InstanceStatusCalculator` và điều chỉnh lại thành cấp độ Task (StepExecution). Thay vì dựa vào % của toàn bộ quy trình, `due_soon` ở đây là ≤ 30% thời gian deadline của riêng task đó.
