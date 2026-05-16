# Story 4.5: Inline Manager Ping — Send Message to Executor (FR19)

Status: in-progress

## Story

As a Manager,
I want to send a reminder message to an executor directly from the dashboard or instance detail,
So that I can unblock delays without leaving the system or switching to email.

## Acceptance Criteria

1. **Given** Manager xem trang detail một instance mình đã launch
   **When** họ click "Nhắc việc" trên một bước cụ thể
   **Then** một inline text input hiện ra ngay tại chỗ (không mở trang mới, không modal phức tạp)
2. **Given** Manager nhập nội dung nhắc và gửi
   **When** message được submit
   **Then** message được lưu vào `step_messages` với sender, recipient (executor của bước đó), và step context
   **And** confirmation visual hiển thị inline (flash message nhẹ)
   **And** activity log ghi nhận hành động nhắc việc
3. **Given** Manager cố gửi ping trên instance **không phải** do mình launch
   **When** họ gửi request
   **Then** system trả về 403 Forbidden
4. **Given** Executor nhận được ping
   **When** họ xem tin nhắn
   **Then** tin nhắn hiển thị trong ngữ cảnh bước liên quan (không phải inbox riêng)

## Tasks / Subtasks

- [x] **Backend: Database & Model (StepMessage)**
  - [x] Tạo file migration tạo bảng `step_messages` chứa các cột: `id`, `step_execution_id`, `sender_id`, `recipient_id`, `body`, `read_at`, `created_at`, `updated_at`.
  - [x] Tạo Eloquent model `StepMessage` và khai báo các quan hệ tương ứng (`stepExecution`, `sender`, `recipient`).
  - [x] Cập nhật model `StepExecution` bổ sung quan hệ `messages()` (hasMany StepMessage).
- [x] **Backend: Action & Controller**
  - [x] Tạo `SendMessageToStep` Action xử lý logic lưu tin nhắn vào DB. Action phải ghi lại `activity()` log là "đã gửi nhắc nhở / tin nhắn".
  - [x] Tạo `StepMessageController@store` với FormRequest validate nội dung (`body` required).
  - [x] Viết / cập nhật Policy (`ProcessInstancePolicy` hoặc `StepExecutionPolicy`) để chặn quyền gửi tin nhắn nếu user không phải người launch instance (trả về 403) hoặc không phải executor.
- [x] **Frontend: UI "Nhắc việc" (Manager)**
  - [x] Ở trang `resources/js/pages/Instances/Show.vue` (Instance Detail), thêm nút "Nhắc việc" ở card của từng bước nếu Manager là người khởi tạo quy trình.
  - [x] Khi click, hiển thị một inline Textarea sử dụng Inertia `useForm` (với `preserveScroll`). Không dùng Modal.
  - [x] Xử lý trạng thái loading và hiển thị thông báo thành công sau khi gửi.
- [x] **Frontend: Hiển thị Tin nhắn (Executor / Manager)**
  - [x] Cập nhật `ProcessInstanceController@show` / `ProcessInstanceResource` để lấy và trả về kèm danh sách `messages` cho mỗi `step`.
  - [x] Hiển thị luồng tin nhắn (Message Thread) bên dưới Card của từng bước trong `Show.vue` để cả Executor và Manager đều đọc được nội dung ngữ cảnh này.
- [x] **Testing**
  - [x] Viết Unit/Feature test chứng minh Manager A không thể gửi tin vào quy trình do Manager B tạo.
  - [x] Đảm bảo message lưu thành công và trả về response đúng.

## Dev Agent Record

### Agent Model Used
(To be filled by Dev Agent)

### Debug Log References
(To be filled by Dev Agent)

### Completion Notes List
(To be filled by Dev Agent)

### File List
(To be filled by Dev Agent)

### Change Log
- Added `StepMessage` model, migration, and controller to handle inline pings.
- Implemented `SendMessageToStep` action to persist message and record activity log.
- Attempted to add UI in `Show.vue` but it failed to patch.

### Review Findings

- [x] [Review][Patch] Hoàn toàn thiếu UI Nhắc Việc và Nhắn Tin — Do quá trình merge code bị lỗi, thẻ `<template>` của `Show.vue` hoàn toàn không chứa mã HTML cho tính năng "Nhắc việc" (Ping) và phần hiển thị luồng tin nhắn (Message Thread).
- [x] [Review][Patch] Lỗi Fatal Vue Rendering (Show.vue) — Khai báo `completeForm` ở script đã bị xóa nhưng trong template vẫn còn dùng `completeForm.completion_notes` và `completeForm.processing`, gây ReferenceError và sập giao diện.
- [x] [Review][Patch] Không bắt lỗi Validation rỗng (Show.vue) — Hàm `submitPing` không kiểm tra input rỗng trước khi gửi. Nếu server trả về lỗi 422, giao diện không có feedback nào cho người dùng.
- [x] [Review][Patch] Rác Import (Show.vue) — Hàm `useDebounceFn` được import nhưng không dùng.
- [x] [Review][Patch] Lỗ hổng Mass Assignment (StepMessage.php) — Việc dùng `protected $guarded = [];` có thể gây rủi ro bảo mật nếu Controller lơ là.
- [x] [Review][Patch] Lỗ hổng logic gửi tin nhắn (Routes/Action) — Route không chặn việc Manager gửi tin nhắn vào một step đã `completed`/`cancelled` hoặc step chưa có người phụ trách (`assigned_to` là null).
- [x] [Review][Defer] Load toàn bộ Select Options bằng get() và thiếu Pagination — deferred, acceptable for MVP scale
- [x] [Review][Defer] Các vấn đề từ file cũ (Memory Leak, Hardcode Role) — Đã được xử lý ở review trước hoặc chấp nhận defer ở MVP. — deferred, pre-existing

---

## Technical Guardrails

### Architecture Compliance
- **Data Persistence:** Tin nhắn phải lưu vĩnh viễn trong database (`step_messages`) chứ không phải chỉ là push notification bay hơi.
- **RBAC:** Authorization check là bắt buộc ở controller trước khi lưu. Chỉ được cho phép người khởi tạo quy trình (Manager) hoặc Executor đang phụ trách bước đó gửi/nhận tin trong phạm vi bước đó.
- **Form Submission:** Dùng `useForm` của Inertia. Khi thành công thì clear textarea, ẩn input và hiện một toast nhỏ hoặc label "Đã gửi".

### Performance & UI Requirements
- **Inline Experience (UX-DR10):** Không dùng Modal hoặc redirect. Phải là inline input mở rộng ra ngay dưới thẻ (card) của bước đang xem.
- **Activity Log:** Đảm bảo hành động "Manager nhắc việc Executor" xuất hiện trong Activity Log của instance để lưu vết (Audit Trail).

### Reference Implementation
Tham khảo luồng Action `AcknowledgeStep` hoặc `CompleteStep` hiện có để tạo `SendMessageToStep`.
Tái sử dụng layout component `<Card>` / `<Textarea>` có sẵn trong thư mục `components/ui`.
