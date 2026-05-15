# Story 4.1: Manager Dashboard Traffic Light Status (FR16)

Status: ready-for-dev

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

- [ ] **Domain Logic & Data Retrieval**
  - [ ] Xây dựng logic tính toán trạng thái Traffic Light cho một `ProcessInstance` dựa trên trạng thái các `StepExecution` bên trong nó.
    - Logic tính deadline: so sánh `deadline_at` của bước đang active (`pending` hoặc `in_progress`). Nếu quá -> Đỏ. Nếu còn <= 30% -> Vàng.
    - Logic tính SLA acknowledge: Nếu bước đang `pending` và quá 1h kể từ lúc tạo (`created_at` hoặc lúc được kích hoạt) -> Vàng.
    - Còn lại -> Xanh.
  - [ ] Cập nhật `DashboardController` hoặc `ProcessInstanceController` để trả về dữ liệu Dashboard cho Manager, gộp logic tính trạng thái vào Query hoặc Resource.
  - [ ] Sắp xếp dữ liệu trả về theo mức độ nghiêm trọng (Đỏ -> Vàng -> Xanh).
- [ ] **Frontend: Dashboard UI (Vue/Inertia)**
  - [ ] Cập nhật trang `resources/js/pages/Dashboard.vue` cho Manager.
  - [ ] Thiết kế UI cho bảng điều khiển (cards hoặc grouped lists) hiển thị rõ ràng 3 trạng thái Đỏ, Vàng, Xanh.
  - [ ] Xây dựng Empty State với nút "Khởi động quy trình".
- [ ] **Testing**
  - [ ] Viết Unit tests cho logic tính Traffic Light status.
  - [ ] Viết Feature tests kiểm tra việc dashboard hiển thị đúng danh sách và sắp xếp đúng mức độ ưu tiên.
  - [ ] Viết Security tests đảm bảo Executor/Beneficiary không truy cập được Manager Dashboard.

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
N/A

### Completion Notes List
N/A

### File List
- app/Services/InstanceStatusCalculator.php (new)
- app/Http/Controllers/DashboardController.php (update)
- app/Http/Resources/ProcessInstanceResource.php (update)
- resources/js/pages/Dashboard.vue (update)
- tests/Feature/DashboardTest.php (update)
