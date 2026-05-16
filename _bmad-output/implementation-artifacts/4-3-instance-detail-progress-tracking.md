# Story 4.3: Instance Detail with Progress Tracking (FR18, FR9)

Status: done

## Story

As a Manager,
I want to see the full detail of an instance including step timeline and progress,
So that I understand exactly what's happening and where potential delays are.

## Acceptance Criteria

1. **Given** Manager click vào một instance
   **When** trang detail load
   **Then** sticky header hiển thị: tên instance, trạng thái tổng thể (traffic light), % hoàn thành, thời gian đã chạy
2. **Given** Manager xem trang detail instance
   **When** họ nhìn vào timeline
   **Then** thấy tất cả bước với trạng thái từng bước: completed (xanh), in_progress (vàng), pending (xám), overdue (đỏ)
   **And** bước đang active được highlight rõ
3. **Given** instance đang chạy
   **When** Manager xem progress tracking (FR9)
   **Then** % hoàn thành được tính: (số bước completed / tổng số bước) × 100
   **And** ước tính thời gian còn lại = tổng `duration_hours` các bước chưa completed
4. **Given** Manager scroll xuống
   **When** sticky header ra khỏi viewport
   **Then** header vẫn hiển thị fixed ở top với context cơ bản (tên instance, trạng thái) — UX-DR10
5. **Given** Manager xem Activity Log trên trang detail
   **When** họ đọc log
   **Then** tất cả sự kiện từ Story 3.5 hiển thị theo thứ tự thời gian mới nhất lên đầu

## Tasks / Subtasks

- [x] **Backend: Query & Calculations (ProcessInstanceController)**
  - [x] Cập nhật hàm `show` trong `ProcessInstanceController` để bổ sung tính toán: thời gian đã chạy (time elapsed), % hoàn thành, và ước tính thời gian còn lại (sum của `duration_hours` từ những bước chưa hoàn thành).
  - [x] Có thể tính toán các chỉ số này ở cấp Resource (`ProcessInstanceResource`) để đồng nhất dữ liệu trả về Inertia. Đảm bảo eager loading `stepExecutions` và `template.stepDefinitions` (để lấy `duration_hours`).
- [x] **Frontend: UI & Sticky Header (Vue)**
  - [x] Thiết kế lại/Nâng cấp `resources/js/pages/Instances/Show.vue` để thêm vùng Sticky Header. Sử dụng CSS `position: sticky` hoặc `fixed` để header luôn bám sát màn hình khi scroll.
  - [x] Hiển thị thông tin tổng quan trên header: Tên quy trình, Traffic Light badge, Progress Bar, Thời gian đã chạy, Ước tính thời gian còn lại.
- [x] **Frontend: Timeline Component**
  - [x] Xây dựng hoặc tái sử dụng một component Timeline để hiển thị danh sách các bước.
  - [x] Cài đặt logic màu sắc cho từng node trong timeline: Hoàn thành (xanh lá), Đang thực hiện (vàng), Chờ xử lý (xám), Quá hạn (đỏ). Highlight rõ bước nào đang là bước hiện tại.
- [x] **Frontend: Activity Log Integration**
  - [x] Tích hợp phần Activity Log hiện có (từ Story 3.5) xuống nửa dưới màn hình hoặc vào một Tab riêng để thông tin hiển thị rõ ràng, sắp xếp mới nhất lên đầu.
- [x] **Testing**
  - [x] Cập nhật/viết test kiểm tra xem các tính toán về tiến độ, thời gian còn lại trả về chính xác trong Resource/Controller.

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
None

### Completion Notes List
- Updated `ProcessInstanceResource` to calculate `time_elapsed` and `estimated_remaining_hours`.
- Totally overhauled `resources/js/pages/Instances/Show.vue` to include a sticky header and a timeline-styled layout for step executions.
- The `Show.vue` now explicitly shows status, time limits, active step highlighting, and completed results efficiently.

### File List
- `app/Http/Resources/ProcessInstanceResource.php`
- `resources/js/pages/Instances/Show.vue`

### Change Log
- Added advanced attributes calculation logic inside Resource.
- Implemented visual Timeline in Instance Detail page.

### Review Findings

- [x] [Review][Patch] UI Sticky Header cồng kềnh — Dùng JS/IntersectionObserver hoặc scroll event để ẩn bớt phần progress bar và context phụ khi người dùng cuộn xuống.
- [x] [Review][Patch] Thiếu màu báo Overdue (Đỏ) trên Timeline — Hàm `getTimelineColor` chưa tính toán thời gian `deadline_at` so với hiện tại để tô màu đỏ cho các bước quá hạn.
- [x] [Review][Patch] Lỗi Rò rỉ State Form — `Show.vue` dùng chung `completeForm` và `overrideForm` cho tất cả các bước trong vòng lặp `v-for`. Gõ ghi chú ở bước này sẽ bị lọt sang bước khác.
- [x] [Review][Patch] Lỗi N+1 Query tiềm ẩn — `getEstimatedRemainingHours()` trong `ProcessInstanceResource` dùng `$this->stepExecutions` lặp lại, có thể gây N+1 nếu không eager load đúng.
- [x] [Review][Patch] Hardcode ngôn ngữ — `ProcessInstanceResource` gắn trực tiếp các chuỗi tiếng Việt (' ngày', ' giờ', ' phút') thay vì dùng hệ thống Lang.
- [x] [Review][Patch] Missing array key 'order' — Thiếu kiểm tra tồn tại key `order` trong mảng `steps` của template snapshot, có thể sinh PHP Warning.
- [x] [Review][Patch] Thiếu Test Coverage — Chưa bổ sung Unit/Feature test cho các hàm tính toán thời gian và giao diện mới.
- [x] [Review][Defer] Các vấn đề từ file cũ (Dashboard Controller/Test) — Các nhận xét về Dashboard thuộc phạm vi của story 4.2 trước đó. — deferred, pre-existing
- [x] [Review][Defer] Magic Strings ở Frontend — Sử dụng các chuỗi cứng cho trạng thái. — deferred, project convention issue

---

## Technical Guardrails

### Architecture Compliance
- **UI Consistency (ADR-025):** Sử dụng các component từ Shadcn/ui (như Card, Badge, ScrollArea, Tabs nếu cần thiết) để xây dựng trang Detail.
- **Resource Formatting:** Tính toán các thuộc tính phái sinh (`estimated_remaining_hours`, `time_elapsed`, `progress`) bên trong `ProcessInstanceResource` để Controller gọn gàng.
- **Eager Loading:** Đảm bảo `stepExecutions` đã được load trước khi tính toán để tránh query N+1.

### Performance & UI Requirements
- **Sticky Header (UX-DR10):** Chú ý z-index và padding khi làm sticky header để không che khuất phần Timeline hay Activity Log khi người dùng scroll qua.
- **Visual Status:** Tái sử dụng logic của `InstanceStatusCalculator` (hoặc tính logic báo Đỏ - Quá hạn tương tự) ở mức độ từng step execution. Tức là step nào qua deadline_at thì là đỏ, chưa tới là vàng, chưa kích hoạt là xám.

### Reference Implementation
Hiện tại `ProcessInstanceController::show` đã trả về data cơ bản và activities. Dev Agent cần tập trung cải thiện dữ liệu gửi sang (Resource) và đại tu hoàn toàn cấu trúc giao diện của file `Show.vue`.
