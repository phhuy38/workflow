# Story 4.1: Manager Dashboard Traffic Light Status (FR16)

Status: done

## Story

As a Manager,
I want to see all running process instances grouped by urgency at a glance,
so that I immediately know which processes need my attention without reading every row.

## Acceptance Criteria

1. **Given** Manager mở trang Dashboard, **When** trang load, **Then** tất cả instances đang chạy hiển thị, được phân nhóm bằng traffic light visual:
   - 🟢 Xanh (Normal) — đang tiến triển bình thường (chưa quá 70% deadline bước hiện tại).
   - 🟡 Vàng (Warning) — cần chú ý (bước chưa acknowledged sau 1h, hoặc còn ≤ 30% thời gian deadline).
   - 🔴 Đỏ (Critical) — cần can thiệp ngay (bước đã vượt deadline).
2. **Given** Dashboard có nhiều instances, **When** Manager nhìn qua trong vài giây, **Then** các instance đỏ và vàng hiển thị nổi bật ở đầu hoặc được highlight rõ (visual grouping, không phải flat list).
3. **Given** không có instance đang chạy, **When** Dashboard load, **Then** empty state hiển thị với call-to-action "Khởi động quy trình đầu tiên" (dẫn đến form khởi động instance).
4. **Given** một user có quyền truy cập hệ thống nhưng không phải Manager/Admin/Designer, **When** họ truy cập Dashboard, **Then** chỉ thấy dữ liệu phù hợp với role của họ (ví dụ Executor thấy inbox của mình, sẽ làm ở Epic 5), nhưng Dashboard tổng quát của Manager này sẽ báo 403 hoặc redirect.

## Tasks / Subtasks

- [x] **Domain Logic & Data Retrieval**
  - [x] Xây dựng logic tính toán trạng thái Traffic Light cho một `ProcessInstance` dựa trên trạng thái các `StepExecution` bên trong nó.
    - Logic tính deadline: so sánh `deadline_at` của bước đang active (`pending` hoặc `in_progress`). Nếu quá -> Đỏ. Nếu còn <= 30% -> Vàng.
    - Logic tính SLA acknowledge: Nếu bước đang `pending` và quá 1h kể từ lúc tạo (`created_at` hoặc lúc được kích hoạt) -> Vàng.
    - Còn lại -> Xanh.
  - [x] Cập nhật `DashboardController` hoặc `ProcessInstanceController` để trả về dữ liệu Dashboard cho Manager, gộp logic tính trạng thái vào Query hoặc Resource.
  - [x] Sắp xếp dữ liệu trả về theo mức độ nghiêm trọng (Đỏ -> Vàng -> Xanh).
- [x] **Frontend: Dashboard UI (Vue/Inertia)**
  - [x] Cập nhật trang `resources/js/pages/Dashboard.vue` cho Manager.
  - [x] Thiết kế UI cho bảng điều khiển (cards hoặc grouped lists) hiển thị rõ ràng 3 trạng thái Đỏ, Vàng, Xanh.
  - [x] Xây dựng Empty State với nút "Khởi động quy trình".
- [x] **Testing**
  - [x] Viết Unit tests cho logic tính Traffic Light status.
  - [x] Viết Feature tests kiểm tra việc dashboard hiển thị đúng danh sách và sắp xếp đúng mức độ ưu tiên.
  - [x] Viết Security tests đảm bảo Executor/Beneficiary không truy cập được Manager Dashboard.

## Dev Notes

### Architecture Compliance
- Logic tính toán Traffic Light nên được encapsulate trong một Service class (vd: `InstanceStatusCalculator`) hoặc được tính toán trực tiếp trong `ProcessInstanceResource` bằng cách append một thuộc tính `traffic_light_status`. Tránh viết logic này trực tiếp trong controller.
- **Performance:** Khi query danh sách instance cho Dashboard, cần eager load `stepExecutions` và chỉ lấy các bước đang active để tránh N+1.

### Project Structure Notes
- Controller: `app/Http/Controllers/DashboardController.php`
- UI: `resources/js/pages/Dashboard.vue`

### References
- [PRD: FR16](_bmad-output/planning-artifacts/prd.md)
- [Epics: Story 4.1](_bmad-output/planning-artifacts/epics.md)

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
- Fixed absolute value of Carbon diffInMinutes returning negative.

### Completion Notes List
- Implemented `InstanceStatusCalculator` to calculate the traffic light status correctly.
- Added extensive tests in `TrafficLightStatusTest` for the status calculation logic including edge cases like `pending` over 1 hour.
- Modified `DashboardController` to get the list of active instances, append their traffic light status, sort them properly, and abort 403 for non-privileged users.
- Redesigned `Dashboard.vue` to show a grouped card view of instances highlighting their status with appropriate colors and icons.

### File List
- app/Services/InstanceStatusCalculator.php (new)
- app/Http/Controllers/DashboardController.php (update)
- app/Http/Resources/ProcessInstanceResource.php (update)
- resources/js/pages/Dashboard.vue (update)
- tests/Feature/DashboardTest.php (update)
- tests/Feature/TrafficLightStatusTest.php (new)

### Review Findings

- [x] [Review][Patch] Bỏ abort(403) Controller / Mâu thuẫn AC4 — Xóa abort(403) ở Controller để Frontend hiển thị Dashboard riêng cho từng role (UI non-manager).
- [x] [Review][Patch] Hiệu suất truy vấn và sắp xếp ở Application Level — Tải toàn bộ instance rồi sắp xếp bằng `usort` trong DashboardController, cần đưa xuống DB/Query.
- [x] [Review][Patch] Hardcode Role Authorization — Xóa `authorize('dashboard.view')` thay bằng `hasRole()` trong DashboardController.
- [x] [Review][Patch] N+1 Service Instantiation — Gọi `app(InstanceStatusCalculator::class)` lặp vòng trong `ProcessInstanceResource::toArray`.
- [x] [Review][Patch] Crash vì Null Deadline — `Carbon::parse($activeStep->deadline_at)` trong InstanceStatusCalculator không kiểm tra null, dẫn đến lỗi nếu deadline_at bị null.
- [x] [Review][Patch] Xác thực bằng cách đoán mò — Frontend Dashboard.vue đoán quyền bằng cách kiểm tra `page.props.instances !== undefined`.
- [x] [Review][Patch] UI thiếu phân nhóm trực quan (AC2) — Dashboard.vue hiển thị flat list CSS Grid thay vì chia nhóm (Grouped list) rõ ràng.
- [x] [Review][Patch] Rò rỉ thời gian Test — Dùng `Carbon::setTestNow()` trong `DashboardTest.php` thay vì helper an toàn `$this->travelTo()`.
- [x] [Review][Patch] Hardcode ngôn ngữ — Sử dụng chuỗi thông báo lỗi cứng tiếng Việt trong `abort(403)`.
- [x] [Review][Patch] Cảnh báo Undefined Array Key — Sắp xếp `usort` thiếu fallback cho `$order[$a['traffic_light_status']]`.
- [x] [Review][Patch] Tải dư thừa dữ liệu StepExecution — `with(['stepExecutions'])` tải toàn bộ lịch sử step thay vì dùng closure lọc các bước active.
- [x] [Review][Defer] Rủi ro Full Table Scan — `orWhereHas` trên `step_executions` trong `ProcessInstanceController`. — deferred, pre-existing (ngoài phạm vi story)
- [x] [Review][Defer] Magic Strings — Sử dụng chuỗi trạng thái cứng 'running', 'pending' thay vì Enum. — deferred, project convention issue
- [x] [Review][Defer] Lỗi Data Flow Inertia — Lấy dữ liệu qua `usePage().props.template?.name` thay vì `defineProps` trong Show.vue. — deferred, pre-existing
