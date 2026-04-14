---
stepsCompleted: ['step-01-validate-prerequisites', 'step-02-design-epics', 'step-03-create-stories', 'step-04-final-validation']
status: 'complete'
completedAt: '2026-04-12'
inputDocuments:
  - '_bmad-output/planning-artifacts/prd.md'
  - '_bmad-output/planning-artifacts/architecture.md'
  - '_bmad-output/planning-artifacts/ux-design-specification.md'
---

# workflow - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for workflow, decomposing the requirements from the PRD, UX Design, and Architecture into implementable stories.

## Requirements Inventory

### Functional Requirements

**Quản lý Template Quy trình (FR1–FR6)**

FR1: Process Designer có thể tạo template quy trình mới với các bước tuần tự (sequential)
FR2: Process Designer có thể cấu hình từng bước: tên, mô tả, người/vai trò phụ trách, deadline mặc định
FR3: Process Designer có thể chỉnh sửa template đang tồn tại
FR4: Process Designer có thể publish hoặc unpublish template để cho phép hoặc ngăn khởi động instance mới
FR5: Process Designer có thể xem danh sách tất cả template trong hệ thống
FR6: Hệ thống lưu phiên bản template tại thời điểm instance được khởi tạo — thay đổi template không ảnh hưởng đến instance đang chạy

**Vận hành Quy trình — Instance Execution (FR7–FR15)**

FR7: Manager có thể khởi tạo instance từ template đã publish, cung cấp thông tin context và danh sách beneficiary
FR8: Hệ thống tự động giao task bước đầu tiên cho người/vai trò phụ trách ngay khi instance được khởi động
FR9: Hệ thống cung cấp tracking real-time cho mỗi instance: bước hiện tại, % hoàn thành, thời gian đã chạy, ước tính còn lại
FR10: Executor có thể xác nhận đã nhận task (acknowledge) để cập nhật trạng thái từ "đã giao" sang "đang thực hiện"
FR11: Executor có thể đánh dấu hoàn thành một bước kèm ghi chú tùy chọn
FR12: Hệ thống tự động kích hoạt bước tiếp theo và giao task cho người phụ trách khi bước hiện tại được hoàn thành
FR13: Manager có thể override (đánh dấu hoàn thành) một bước trong instance mình khởi động, bắt buộc nhập lý do
FR14: Manager có thể hủy instance đang chạy mà mình đã khởi động, bắt buộc nhập lý do
FR15: Hệ thống ghi log đầy đủ mọi hành động trên instance: thời điểm giao việc, xác nhận, hoàn thành, override, hủy, tin nhắn

**Bảng điều khiển Manager (FR16–FR19)**

FR16: Manager có thể xem toàn bộ instance đang chạy trong tổ chức
FR17: Manager có thể lọc và tìm kiếm instance theo template, trạng thái, executor, và deadline
FR18: Manager có thể xem chi tiết một instance: timeline, trạng thái từng bước, và activity log đầy đủ
FR19: Manager có thể gửi tin nhắn nhắc việc trực tiếp đến executor phụ trách một bước trong instance mình khởi động

**Quản lý Task của Executor (FR20–FR23)**

FR20: Executor có thể xem danh sách tổng hợp tất cả task được giao từ mọi quy trình đang chạy
FR21: Hệ thống sắp xếp task theo mức độ khẩn cấp dựa trên deadline còn lại
FR22: Executor có thể xem thông tin chi tiết của task bao gồm context quy trình liên quan
FR23: Executor có thể hoàn thành task trực tiếp từ màn hình danh sách mà không cần mở chi tiết

**Giao diện Beneficiary (FR24–FR26)**

FR24: Beneficiary có thể xem danh sách các quy trình đang chạy liên quan đến mình
FR25: Beneficiary có thể xem trạng thái chi tiết của quy trình: bước hiện tại, người phụ trách, tiến độ tổng thể
FR26: Beneficiary có thể gửi tin nhắn đến người phụ trách bước hiện tại trong quy trình của mình

**Thông báo & Giao tiếp (FR27–FR32)**

FR27: Hệ thống gửi thông báo cho executor khi được giao task mới
FR28: Hệ thống gửi thông báo cho Manager khi bước trong instance mình khởi động chưa được xác nhận sau 1 giờ kể từ khi giao (mặc định, có thể cấu hình bởi Admin)
FR29: Hệ thống gửi thông báo cho Manager khi bước trong instance mình khởi động còn lại ≤ 30% số giờ deadline của bước đó hoặc đã vượt deadline
FR30: Hệ thống gửi thông báo cho executor khi task còn lại ≤ 30% số giờ deadline của bước đó
FR31: Hệ thống gửi thông báo ra bên ngoài (email) đến beneficiary trước khi họ có tài khoản hệ thống
FR32: Người nhận tin nhắn/ping có thể phản hồi trong ngữ cảnh của bước liên quan

**Quản trị Hệ thống (FR33–FR37)**

FR33: Admin có thể tạo, chỉnh sửa và vô hiệu hóa tài khoản người dùng
FR34: Admin có thể gán và thu hồi quyền Process Designer cho người dùng
FR35: Admin có thể cấu hình thông số hệ thống bao gồm cài đặt email (SMTP)
FR36: Hệ thống tự động tạo tài khoản Beneficiary và gửi thông báo đăng nhập khi một bước kiểu "cấp tài khoản" trong quy trình được hoàn thành
FR37: Hệ thống kiểm soát truy cập theo 5 vai trò với phạm vi quyền hạn riêng biệt: Admin, Process Designer, Manager, Executor, Beneficiary

### NonFunctional Requirements

NFR1: Các trang dashboard và chi tiết instance tải trong vòng 3 giây với 100 người dùng hoạt động đồng thời trong điều kiện bình thường
NFR2: Thông báo được gửi đến người nhận trong vòng 60 giây sau khi sự kiện trigger (bước hoàn thành, deadline, chưa xác nhận)
NFR3: Hệ thống duy trì uptime không gián đoạn trong giờ làm việc (08:00–18:00 các ngày làm việc)
NFR4: Không mất dữ liệu khi xảy ra sự cố — hệ thống hỗ trợ khôi phục từ backup
NFR5: Toàn bộ dữ liệu người dùng và quy trình lưu trên server của tổ chức — không truyền ra ngoài ngoại trừ email thông báo qua SMTP
NFR6: Người dùng chỉ có thể truy cập tính năng và dữ liệu phù hợp với vai trò được gán — mọi vi phạm quyền truy cập đều bị từ chối và ghi log
NFR7: Session người dùng hết hạn tự động sau khoảng thời gian không hoạt động có thể cấu hình bởi Admin
NFR8: Người có kiến thức server cơ bản có thể cài đặt và khởi động hệ thống trong vòng dưới 1 giờ với tài liệu hướng dẫn đi kèm
NFR9: Hệ thống được đóng gói để triển khai tự động (Docker hoặc tương đương) với cấu hình tối thiểu

### Additional Requirements

_Từ Architecture Decision Records — ảnh hưởng trực tiếp đến implementation:_

- **Khởi tạo dự án:** Dùng Laravel Official Vue Starter Kit: `laravel new workflow --using=vue` (TypeScript: Yes, Inertia SSR: No, Testing: Pest). Đây là Story đầu tiên của Epic 1.
- **Docker infrastructure:** 3 compose files (base + override + prod) + multi-stage Dockerfile + named volumes (`postgres_data`, `redis_data`, `storage_data`) + `restart: unless-stopped` cho queue workers và scheduler (ADR-011)
- **Database:** PostgreSQL 16 với 7 composite indexes trên `step_executions` và `process_instances` (ADR-001, ADR-042)
- **Queue:** Redis + 3 named queues (`broadcasts`, `notifications`, `default`) + 2 worker containers (ADR-003)
- **Real-time:** Laravel Reverb WebSocket — defer đến Story 2+ sau khi core CRUD hoàn thành (ADR-002)
- **RBAC:** spatie/laravel-permission (5 roles, 9 permissions) + Policy layer bắt buộc trên mọi controller action (ADR-004)
- **Audit log:** spatie/laravel-activitylog với `queue: false` bắt buộc (ADR-005)
- **State machine:** spatie/laravel-model-states cho ProcessInstance và StepExecution (ADR-016)
- **Template versioning:** JSON snapshot trong `template_snapshot_data` column khi launch instance (ADR-006)
- **Beneficiary auto-account:** FR36 phải implement trong `LaunchProcessInstance` Action — khi bước "cấp tài khoản" hoàn thành, `CompleteStepExecution` Action tự động tạo tài khoản Beneficiary (ADR-017, ADR-038)
- **User deactivation handler:** `UserDeactivating` event → `ReassignOpenStepsOnUserDeactivation` listener — reassign các step về `assigned_to = null` và notify Manager (ADR-038)
- **Scheduler:** `CheckApproachingDeadlines` job chạy mỗi phút + `CheckStateConsistency` job mỗi giờ (ADR-029)
- **Dry-run instances:** `status='test'` — không trigger notifications, không hiện trên dashboard, Prunable sau 24h (ADR-037)
- **Data retention:** `step_messages` prunable sau 365 ngày; `notifications` prunable 90 ngày sau khi đã đọc (ADR-045)
- **CI gates:** Pint + Larastan level 5 + tsc + ESLint + Pest phải pass trước khi merge (ADR-035)
- **Security headers:** X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy bắt buộc; CSP defer (ADR-027)
- **Rate limiting:** 3 tiers — auth (5/min per IP), notifications (10/min per user), uploads (20/min per user) (ADR-028)
- **Deployment script:** `deploy.sh` với health checks (app `/up`, DB connection, storage volume) (ADR-015)
- **Required seeders:** `PermissionsSeeder → RolesSeeder → AdminUserSeeder` phải chạy ở mọi environment (ADR-044)

### UX Design Requirements

_Từ UX Design Specification — actionable requirements với implementation scope rõ ràng:_

UX-DR1: Thiết lập Tailwind design tokens ngay từ đầu — semantic color system (primary, muted, destructive, warning, success) + typography (Inter hoặc Be Vietnam Pro) định nghĩa trong `tailwind.config.js` trước khi viết UI đầu tiên
UX-DR2: Implement traffic light status system (xanh/vàng/đỏ) cho Manager Dashboard — phân biệt "bình thường / cần chú ý / cần can thiệp ngay" bằng visual hierarchy, không phải chỉ text
UX-DR3: Inline action pattern cho Manager — override, ping, và leo thang phải thực hiện được ngay trên dashboard/instance detail mà không cần navigate sang trang khác (Act where you see)
UX-DR4: Progressive disclosure cho task completion flow — form hoàn thành chỉ hiện ghi chú và file đính kèm khi người dùng muốn; một click/tap là đủ để hoàn thành task bình thường
UX-DR5: Executor Inbox responsive-first — thiết kế cho mobile trước (tối giản, chỉ "làm gì tiếp theo"), mở rộng ra desktop (đủ context). Không phải hai flow riêng biệt.
UX-DR6: Actionable notifications — mọi alert email đều có deep link dẫn thẳng đến bước cần xử lý, không phải trang chủ
UX-DR7: Visual flow representation cho Template Builder — dùng visual nodes thay vì form text thuần túy (lấy cảm hứng từ N8N nhưng đơn giản hơn, không cần drag-and-drop phức tạp ở MVP)
UX-DR8: Inbox zero metaphor cho Executor — danh sách task rút ngắn dần khi hoàn thành kèm micro-animation nhẹ để tạo cảm giác accomplishment
UX-DR9: Framing ngôn ngữ "hỗ trợ, không giám sát" — notification delay dùng "cần hỗ trợ không?" thay vì "bạn đang trễ"; log hoạt động không hiển thị granular tracking cho Executor
UX-DR10: Sticky context header — khi xem chi tiết quy trình, luôn thấy context tổng thể (tên instance, trạng thái, % hoàn thành) ở header, không bị mất khi scroll
UX-DR11: Dashboard scan pattern — Manager nhìn qua dashboard trong vài giây và biết ngay đâu cần chú ý; cần visual grouping theo trạng thái, không phải flat list
UX-DR12: Completion feels good — đánh dấu hoàn thành task phải có phản hồi rõ ràng (visual feedback / animation nhẹ) ngay lập tức

### FR Coverage Map

| FR | Epic | Mô tả ngắn |
|---|---|---|
| FR1 | Epic 2 | Tạo template mới |
| FR2 | Epic 2 | Cấu hình từng bước |
| FR3 | Epic 2 | Chỉnh sửa template |
| FR4 | Epic 2 | Publish/unpublish template |
| FR5 | Epic 2 | Xem danh sách template |
| FR6 | Epic 3 | Template snapshot khi launch |
| FR7 | Epic 3 | Manager launch instance |
| FR8 | Epic 3 | Auto-assign bước đầu tiên |
| FR9 | Epic 4 | Real-time tracking instance |
| FR10 | Epic 3 | Executor acknowledge task |
| FR11 | Epic 3 | Executor complete step + notes |
| FR12 | Epic 3 | Auto-trigger bước tiếp theo |
| FR13 | Epic 3 | Manager override bước |
| FR14 | Epic 3 | Manager hủy instance |
| FR15 | Epic 3 | Full activity log |
| FR16 | Epic 4 | Manager xem tất cả instances |
| FR17 | Epic 4 | Filter/search instances |
| FR18 | Epic 4 | Chi tiết instance + timeline |
| FR19 | Epic 4 | Manager ping executor |
| FR20 | Epic 5 | Executor xem inbox tổng hợp |
| FR21 | Epic 5 | Sắp xếp theo urgency/deadline |
| FR22 | Epic 5 | Xem chi tiết task + context |
| FR23 | Epic 5 | Hoàn thành task từ danh sách |
| FR24 | Epic 7 | Beneficiary xem quy trình của mình |
| FR25 | Epic 7 | Beneficiary xem chi tiết tiến độ |
| FR26 | Epic 7 | Beneficiary ping step owner |
| FR27 | Epic 6 | Notify executor khi được giao task |
| FR28 | Epic 6 | Notify Manager khi bước chưa acknowledged |
| FR29 | Epic 6 | Notify Manager khi gần/vượt deadline |
| FR30 | Epic 6 | Notify executor khi gần deadline |
| FR31 | Epic 6 | Email outbound cho beneficiary chưa có tài khoản |
| FR32 | Epic 7 | Reply trong ngữ cảnh bước |
| FR33 | Epic 1 | Admin CRUD users |
| FR34 | Epic 1 | Admin gán Designer role |
| FR35 | Epic 1 | Admin cấu hình system/SMTP |
| FR36 | Epic 7 | Auto-create Beneficiary account |
| FR37 | Epic 1 | 5-role access control |

## Epic List

### Epic 1: Foundation, Infrastructure & System Administration
Hệ thống có thể triển khai trên Docker, tất cả 5 vai trò có thể đăng nhập và truy cập đúng phạm vi tính năng, Admin quản lý được người dùng và cấu hình hệ thống. Đây là nền tảng cho mọi epic tiếp theo.
**FRs covered:** FR33, FR34, FR35, FR37
**UX covered:** UX-DR1

### Epic 2: Process Template Management
Process Designer tạo, cấu hình chi tiết từng bước, và publish/unpublish template workflow. Template Builder có visual node representation. Các Manager có thể duyệt danh sách template sẵn sàng để khởi chạy.
**FRs covered:** FR1, FR2, FR3, FR4, FR5
**UX covered:** UX-DR7

### Epic 3: Process Instance Execution
Manager khởi chạy quy trình từ template (với snapshot versioning), Executor acknowledge và hoàn thành bước kèm ghi chú, hệ thống tự động chuyển tiếp. Manager có thể override bước hoặc hủy instance. Mọi hành động được ghi log đầy đủ.
**FRs covered:** FR6, FR7, FR8, FR10, FR11, FR12, FR13, FR14, FR15
**UX covered:** UX-DR4, UX-DR12

### Epic 4: Manager Dashboard & Real-time Visibility
Manager có toàn cảnh tất cả quy trình đang chạy với traffic light status (xanh/vàng/đỏ), filter/tìm kiếm, xem chi tiết instance với real-time tracking, và can thiệp (ping Executor) trực tiếp ngay tại chỗ không cần chuyển màn hình.
**FRs covered:** FR9, FR16, FR17, FR18, FR19
**UX covered:** UX-DR2, UX-DR3, UX-DR10, UX-DR11
**Technical note:** Reverb WebSocket được kích hoạt tại epic này

### Epic 5: Executor Inbox & Task Management
Executor có inbox tổng hợp từ mọi quy trình đang tham gia, sắp xếp theo urgency/deadline, hoàn thành task ngay từ danh sách. Trải nghiệm hoạt động mượt mà trên cả desktop lẫn mobile.
**FRs covered:** FR20, FR21, FR22, FR23
**UX covered:** UX-DR5, UX-DR8

### Epic 6: Notifications & Proactive Alerts
Hệ thống tự động gửi email thông báo cho tất cả stakeholders: giao task mới, sắp deadline, chưa acknowledged, vượt deadline. Email có deep link dẫn thẳng đến bước cần xử lý. Beneficiary nhận email trước khi có tài khoản. Ngôn ngữ thông báo định hướng hỗ trợ, không giám sát.
**FRs covered:** FR27, FR28, FR29, FR30, FR31
**UX covered:** UX-DR6, UX-DR9

### Epic 7: Beneficiary Experience & In-System Messaging
Beneficiary được tự động tạo tài khoản khi bước "cấp tài khoản" hoàn thành, xem được quy trình của mình, ping step owner. Vòng giao tiếp trong hệ thống (reply trong ngữ cảnh bước) hoàn chỉnh cho tất cả vai trò.
**FRs covered:** FR24, FR25, FR26, FR32, FR36

---

## Epic 1: Foundation, Infrastructure & System Administration

Hệ thống có thể triển khai trên Docker, tất cả 5 vai trò có thể đăng nhập và truy cập đúng phạm vi tính năng, Admin quản lý được người dùng và cấu hình hệ thống. Đây là nền tảng cho mọi epic tiếp theo.

### Story 1.1: Initialize Project & Configure Development Infrastructure

As a developer,
I want the project initialized with Docker infrastructure, CI pipeline, and design tokens,
So that the team has a consistent, reproducible development and deployment environment from day one.

**Acceptance Criteria:**

**Given** a clean server with Docker and Docker Compose installed
**When** the developer runs `docker compose up -d` (dev mode with override)
**Then** tất cả containers khởi động thành công: app, PostgreSQL, Redis, queue workers, scheduler, Mailpit
**And** app truy cập được tại `http://localhost` hiển thị login page

**Given** developer push code
**When** CI workflow chạy
**Then** Pint, Larastan level 5, tsc (noEmit), ESLint, và Pest đều pass không có lỗi

**Given** project được khởi tạo
**When** developer mở `tailwind.config.js`
**Then** semantic color tokens được định nghĩa: primary, muted, destructive, warning, success
**And** typography font (Inter hoặc Be Vietnam Pro) được cấu hình

**Given** production Docker Compose được dùng
**When** containers restart sau sự cố
**Then** named volumes (`postgres_data`, `redis_data`, `storage_data`) giữ nguyên dữ liệu
**And** queue workers và scheduler tự khởi động lại nhờ `restart: unless-stopped`

### Story 1.2: Authentication — Login, Logout & Session Security

As a user,
I want to log in with my email and password and have my session managed securely,
So that I can access the system and my session expires automatically when I'm inactive.

**Acceptance Criteria:**

**Given** user với credentials hợp lệ
**When** họ submit login form
**Then** họ được xác thực và redirect đến trang phù hợp với vai trò

**Given** authenticated user
**When** họ click logout
**Then** session bị hủy và redirect về login page

**Given** admin đã cấu hình session timeout N phút
**When** user không hoạt động sau N phút
**Then** session hết hạn, request tiếp theo redirect về login

**Given** bất kỳ HTTP response nào từ server
**When** browser nhận được
**Then** security headers có mặt: X-Frame-Options (SAMEORIGIN), X-Content-Type-Options (nosniff), X-XSS-Protection, Referrer-Policy

**Given** unauthenticated user
**When** họ truy cập bất kỳ protected route nào
**Then** redirect về login page

### Story 1.3: Role-Based Access Control — 5 Roles & Policy Layer (FR37)

As a system,
I want users assigned to exactly one of 5 roles with scoped permissions enforced by Policy layer,
So that each user only sees features and data appropriate to their role.

**Acceptance Criteria:**

**Given** `php artisan db:seed --class=RequiredDataSeeder` được chạy
**When** seeding hoàn thành
**Then** 5 roles tồn tại: admin, manager, process_designer, executor, beneficiary
**And** 9 permissions được tạo và gán đúng theo permission matrix
**And** admin account tồn tại với credentials từ `.env`

**Given** user với role "executor"
**When** họ truy cập manager dashboard URL
**Then** system trả về 403 Forbidden

**Given** user với role "beneficiary"
**When** họ truy cập bất kỳ URL ngoài phạm vi được phép
**Then** system trả về 403 Forbidden

**Given** bất kỳ controller action nào yêu cầu authorization
**When** action được gọi
**Then** `$this->authorize()` là lệnh đầu tiên, delegate sang Policy tương ứng
**And** unauthorized access được ghi vào activity log

**Given** permission check cho user đã đăng nhập
**When** cache còn warm
**Then** check hoàn thành không cần database query

### Story 1.4: Admin — User Account Management (FR33, FR34)

As an Admin,
I want to create, edit, and deactivate user accounts and assign the Process Designer role,
So that I can control who has access to the system and what capabilities they have.

**Acceptance Criteria:**

**Given** Admin trên trang user management
**When** họ tạo user mới với name, email, password, và role
**Then** tài khoản được tạo và user có thể đăng nhập

**Given** Admin deactivate một user account
**When** deactivation được xác nhận
**Then** user đó không thể đăng nhập
**And** tất cả step_executions đang assigned cho user đó có `assigned_to = null` và status về 'pending'
**And** Manager sở hữu các instance liên quan nhận thông báo liệt kê các bước cần reassign

**Given** Admin gán role Process Designer cho user
**When** assignment được lưu
**Then** user đó có thể truy cập Template management features
**And** activity log ghi lại ai gán, cho ai, lúc nào

**Given** Admin thu hồi role Process Designer
**When** revocation được lưu
**Then** user mất quyền truy cập Template features ngay lập tức

**Given** non-admin user
**When** họ truy cập user management pages
**Then** system trả về 403 Forbidden

### Story 1.5: System Configuration — SMTP & Session Settings (FR35)

As an Admin,
I want to configure SMTP settings and session timeout from the admin panel,
So that the organization can send email notifications using their own email server.

**Acceptance Criteria:**

**Given** Admin trên trang system settings
**When** họ nhập SMTP host, port, username, password, from-address, encryption và save
**Then** settings được lưu vào database
**And** email tiếp theo sử dụng SMTP configuration mới

**Given** Admin nhấn "Test Email"
**When** request được gửi
**Then** test email gửi đến admin address
**And** kết quả (thành công / thất bại + error message) hiển thị inline không reload page

**Given** Admin cập nhật session timeout (đơn vị: phút)
**When** settings được lưu
**Then** user sessions mới hết hạn sau thời gian không hoạt động đã cấu hình

**Given** non-admin user
**When** họ truy cập system settings page
**Then** system trả về 403 Forbidden

---

## Epic 2: Process Template Management

Process Designer tạo, cấu hình chi tiết từng bước, và publish/unpublish template workflow. Template Builder có visual node representation. Các Manager có thể duyệt danh sách template sẵn sàng để khởi chạy.

### Story 2.1: Template List & Create New Template (FR1, FR5)

As a Process Designer,
I want to see all process templates and create new ones,
So that I can start building workflows for the organization.

**Acceptance Criteria:**

**Given** Process Designer đăng nhập
**When** họ vào trang Templates
**Then** danh sách tất cả templates hiển thị với: tên, trạng thái (Draft/Published), số bước, ngày tạo

**Given** Process Designer trên trang Template List
**When** họ click "Tạo template mới" và nhập tên + mô tả
**Then** template mới được tạo với trạng thái Draft
**And** họ được redirect đến trang chỉnh sửa template vừa tạo

**Given** user với role không phải Process Designer hoặc Admin
**When** họ truy cập trang Templates
**Then** system trả về 403 Forbidden

**Given** Process Designer nhập tên template trùng với template đã tồn tại
**When** họ submit form
**Then** form hiển thị lỗi validation "Tên template đã tồn tại"

### Story 2.2: Configure Template Steps with Visual Builder (FR2, UX-DR7)

As a Process Designer,
I want to add and configure steps in a template using a visual node builder,
So that I can clearly see the workflow structure while designing it.

**Acceptance Criteria:**

**Given** Process Designer trên trang chỉnh sửa template
**When** họ xem phần Steps
**Then** các bước hiển thị dạng visual cards/nodes theo thứ tự tuần tự (không phải plain text list)
**And** mỗi node hiển thị: số thứ tự, tên bước, người phụ trách, deadline mặc định

**Given** Process Designer click "Thêm bước"
**When** họ điền tên, mô tả, assignee type (user/role/department), assignee, và deadline (giờ)
**Then** bước mới xuất hiện như node cuối cùng trong chuỗi
**And** `duration_hours` mặc định là 24 nếu không điền

**Given** Process Designer muốn sắp xếp lại bước
**When** họ dùng nút lên/xuống trên node
**Then** thứ tự các bước thay đổi và được lưu lại

**Given** Process Designer xóa một bước
**When** xóa được xác nhận
**Then** bước bị xóa, các bước còn lại được đánh số lại

**Given** template có ít nhất 1 bước được cấu hình đầy đủ
**When** Process Designer lưu template
**Then** tất cả thay đổi được persist vào database

### Story 2.3: Edit Existing Template (FR3)

As a Process Designer,
I want to edit the name, description, and steps of an existing template,
So that I can refine workflows as the organization's processes evolve.

**Acceptance Criteria:**

**Given** Process Designer trên trang Template List
**When** họ mở một template (bất kể Draft hay Published)
**Then** họ có thể chỉnh sửa tên, mô tả, và cấu hình từng bước

**Given** Process Designer chỉnh sửa thông tin bước
**When** họ lưu
**Then** thay đổi được persist vào database
**And** các instance đang chạy dựa trên template này **không bị ảnh hưởng** (snapshot đã được chốt lúc launch)

**Given** Process Designer thử xóa một template đã có instance đang chạy
**When** họ thực hiện xóa
**Then** system từ chối với message: "Template đang có quy trình đang chạy, không thể xóa"

**Given** Process Designer lưu thay đổi cho template Published
**When** lưu thành công
**Then** flash message hiển thị: "Template đã được cập nhật. Các quy trình mới khởi động sẽ dùng phiên bản mới."

### Story 2.4: Publish & Unpublish Template (FR4)

As a Process Designer,
I want to publish or unpublish a template,
So that I can control which templates Managers can use to start new processes.

**Acceptance Criteria:**

**Given** Process Designer muốn publish một Draft template
**When** họ click "Publish"
**Then** system validate: template có ít nhất 1 bước, mọi bước có `assignee_type` và `duration_hours`
**And** nếu validation pass → template chuyển sang Published, Managers có thể dùng để launch instance

**Given** template không qua validation khi publish (ví dụ: bước thiếu assignee)
**When** Process Designer click "Publish"
**Then** system hiển thị danh sách lỗi cụ thể: "Bước 2: chưa có người phụ trách"
**And** template vẫn ở trạng thái Draft

**Given** Process Designer muốn unpublish một template Published
**When** họ click "Unpublish"
**Then** template chuyển về Draft
**And** Managers không thể dùng template này để launch instance mới
**And** các instance đang chạy từ template này **không bị ảnh hưởng**

**Given** Manager xem danh sách template để launch
**When** họ mở trang launch instance
**Then** chỉ hiển thị các template đang ở trạng thái Published

---

## Epic 3: Process Instance Execution

Manager khởi chạy quy trình từ template (với snapshot versioning), Executor acknowledge và hoàn thành bước kèm ghi chú, hệ thống tự động chuyển tiếp. Manager có thể override bước hoặc hủy instance. Mọi hành động được ghi log đầy đủ.

### Story 3.1: Launch Process Instance (FR7, FR6, FR8)

As a Manager,
I want to launch a process instance from a published template,
So that the workflow starts automatically with all steps assigned to the right people.

**Acceptance Criteria:**

**Given** Manager chọn một template Published
**When** họ điền tên instance, thông tin context, và nhấn "Khởi động"
**Then** instance được tạo với trạng thái Running
**And** toàn bộ cấu hình template được snapshot vào `template_snapshot_data` (thay đổi template sau này không ảnh hưởng instance)
**And** bước đầu tiên tự động được tạo và gán cho người/vai trò phụ trách tương ứng

**Given** instance vừa được launch
**When** bước đầu tiên được giao
**Then** step_execution đầu tiên có trạng thái 'pending' và `deadline_at` được tính từ `duration_hours` của bước

**Given** Manager truy cập trang launch instance
**When** họ xem danh sách template
**Then** chỉ thấy template Published (không thấy Draft)

**Given** user với role không phải Manager
**When** họ cố launch instance
**Then** system trả về 403 Forbidden

### Story 3.2: Executor — Acknowledge & Complete Step (FR10, FR11)

As an Executor,
I want to acknowledge that I've received a task and mark it complete when done,
So that the process can progress and everyone knows the current status.

**Acceptance Criteria:**

**Given** Executor có task ở trạng thái 'pending'
**When** họ click "Nhận việc" (acknowledge)
**Then** step_execution chuyển sang 'in_progress'
**And** timestamp `started_at` được ghi nhận

**Given** Executor có task ở trạng thái 'in_progress'
**When** họ click "Hoàn thành"
**Then** step_execution chuyển sang 'completed' ngay lập tức (không có modal confirmation thừa)
**And** timestamp `completed_at` và `completed_by` được ghi nhận

**Given** Executor muốn thêm ghi chú khi hoàn thành
**When** họ click "Thêm ghi chú" (progressive disclosure)
**Then** text area cho ghi chú và tùy chọn đính kèm file hiện ra
**And** nội dung này là tùy chọn — không điền vẫn có thể hoàn thành

**Given** Executor hoàn thành một bước thành công
**When** completion được lưu
**Then** visual feedback rõ ràng (animation nhẹ, màu xanh) xác nhận hành động thành công
**And** task biến mất khỏi danh sách pending tasks của Executor

**Given** Executor cố hoàn thành bước không được giao cho mình
**When** họ gửi request
**Then** system trả về 403 Forbidden

### Story 3.3: Automatic Step Progression & Process Completion (FR12)

As a system,
I want to automatically activate the next step when the current step is completed,
So that the process flows without manual intervention.

**Acceptance Criteria:**

**Given** một bước vừa được đánh dấu hoàn thành
**When** `CompleteStepExecution` action được gọi
**Then** bước tiếp theo trong sequence tự động chuyển sang trạng thái 'pending'
**And** `deadline_at` cho bước tiếp theo được tính từ thời điểm hiện tại + `duration_hours`
**And** người phụ trách bước tiếp theo được gán (resolved từ snapshot)

**Given** bước cuối cùng trong instance vừa hoàn thành
**When** không còn bước nào pending
**Then** instance chuyển sang trạng thái 'completed'
**And** `completed_at` được ghi nhận trên instance

**Given** Manager override một bước (force-complete)
**When** override được xác nhận
**Then** logic progression tương tự như complete bình thường — bước tiếp theo được kích hoạt

**Given** một bước được hoàn thành
**When** người phụ trách bước tiếp theo được xác định từ snapshot
**Then** nếu assignee_type là 'role', system chọn user có role đó (nếu nhiều user, chọn theo round-robin hoặc policy được định nghĩa)

### Story 3.4: Manager — Override Step & Cancel Instance (FR13, FR14)

As a Manager,
I want to override a stuck step or cancel an instance I launched,
So that I can unblock processes that need manual intervention.

**Acceptance Criteria:**

**Given** Manager xem chi tiết một instance mình đã launch
**When** họ click "Override" trên một bước cụ thể
**Then** một form yêu cầu nhập lý do bắt buộc hiện ra

**Given** Manager nhập lý do override
**When** họ xác nhận
**Then** bước được đánh dấu 'skipped' với lý do được lưu vào activity log
**And** bước tiếp theo tự động được kích hoạt

**Given** Manager muốn hủy một instance đang chạy
**When** họ click "Hủy quy trình" và nhập lý do bắt buộc
**Then** instance chuyển sang trạng thái 'cancelled'
**And** tất cả step_executions đang pending/in_progress bị đóng lại
**And** lý do hủy được ghi vào activity log

**Given** Manager cố override bước trong instance **không phải** do mình launch
**When** họ gửi request
**Then** system trả về 403 Forbidden

**Given** Manager cố hủy instance đã ở trạng thái completed hoặc cancelled
**When** họ gửi request
**Then** system từ chối với message rõ ràng về trạng thái hiện tại

### Story 3.5: Activity Log — Full Audit Trail (FR15)

As a Manager,
I want to see a complete, immutable audit trail of everything that happened on an instance,
So that I have full accountability and can reconstruct any event sequence.

**Acceptance Criteria:**

**Given** bất kỳ hành động nào xảy ra trên instance (giao việc, acknowledge, complete, override, cancel, message)
**When** hành động được thực hiện
**Then** một entry được ghi **đồng bộ** (không queue) vào activity log với: actor, action type, timestamp, entity bị ảnh hưởng, và metadata liên quan

**Given** Manager xem trang chi tiết instance
**When** họ scroll xuống phần Activity Log
**Then** tất cả sự kiện hiển thị theo thứ tự thời gian, không thể bị xóa hoặc chỉnh sửa

**Given** activity log của một instance
**When** Manager đọc
**Then** họ có thể reconstruct toàn bộ timeline: ai làm gì, lúc nào, với lý do gì (với override/cancel)

**Given** một hành động unauthorized bị từ chối
**When** system log vi phạm
**Then** entry được ghi vào activity log với actor, action attempted, và "DENIED" status

---

## Epic 4: Manager Dashboard & Real-time Visibility

Manager có toàn cảnh tất cả quy trình đang chạy với traffic light status (xanh/vàng/đỏ), filter/tìm kiếm, xem chi tiết instance với real-time tracking, và can thiệp (ping Executor) trực tiếp ngay tại chỗ không cần chuyển màn hình.

### Story 4.1: Manager Dashboard with Traffic Light Status (FR16)

As a Manager,
I want to see all running process instances grouped by urgency at a glance,
So that I immediately know which processes need my attention without reading every row.

**Acceptance Criteria:**

**Given** Manager mở trang Dashboard
**When** trang load
**Then** tất cả instances đang chạy hiển thị, được phân nhóm bằng traffic light visual:
- 🟢 Xanh — đang tiến triển bình thường (chưa quá 70% deadline bước hiện tại)
- 🟡 Vàng — cần chú ý (bước chưa acknowledged sau 1h, hoặc còn ≤ 30% thời gian deadline)
- 🔴 Đỏ — cần can thiệp ngay (bước đã vượt deadline)

**Given** Dashboard có nhiều instances
**When** Manager nhìn qua trong vài giây
**Then** các instance đỏ và vàng hiển thị nổi bật ở đầu hoặc được highlight rõ (visual grouping, không phải flat list)

**Given** không có instance đang chạy
**When** Dashboard load
**Then** empty state hiển thị với call-to-action "Khởi động quy trình đầu tiên"

**Given** Manager có role nhưng không phải Manager
**When** họ truy cập Dashboard
**Then** chỉ thấy dữ liệu phù hợp với role của họ (Executor thấy inbox của mình, không thấy full dashboard)

### Story 4.2: Filter & Search Instances (FR17)

As a Manager,
I want to filter and search instances by template, status, executor, and deadline,
So that I can quickly find the specific processes I need to review or act on.

**Acceptance Criteria:**

**Given** Manager trên Dashboard
**When** họ chọn filter theo template
**Then** chỉ hiển thị instances thuộc template đó

**Given** Manager filter theo executor (người đang thực hiện bước hiện tại)
**When** filter được áp dụng
**Then** chỉ hiển thị instances có bước đang active được giao cho executor đó

**Given** Manager filter theo status (Running / Completed / Cancelled)
**When** filter được áp dụng
**Then** danh sách cập nhật ngay không reload trang

**Given** Manager nhập từ khóa vào ô tìm kiếm
**When** họ gõ
**Then** instances được lọc theo tên instance (debounced, không cần nhấn Enter)

**Given** Manager đã set filters
**When** họ navigate đi nơi khác rồi quay lại Dashboard
**Then** filters được giữ nguyên (lưu vào Pinia store với localStorage persistence)

### Story 4.3: Instance Detail with Progress Tracking (FR18, FR9)

As a Manager,
I want to see the full detail of an instance including step timeline and progress,
So that I understand exactly what's happening and where potential delays are.

**Acceptance Criteria:**

**Given** Manager click vào một instance
**When** trang detail load
**Then** sticky header hiển thị: tên instance, trạng thái tổng thể (traffic light), % hoàn thành, thời gian đã chạy

**Given** Manager xem trang detail instance
**When** họ nhìn vào timeline
**Then** thấy tất cả bước với trạng thái từng bước: completed (xanh), in_progress (vàng), pending (xám), overdue (đỏ)
**And** bước đang active được highlight rõ

**Given** instance đang chạy
**When** Manager xem progress tracking (FR9)
**Then** % hoàn thành được tính: (số bước completed / tổng số bước) × 100
**And** ước tính thời gian còn lại = tổng `duration_hours` các bước chưa completed

**Given** Manager scroll xuống
**When** sticky header ra khỏi viewport
**Then** header vẫn hiển thị fixed ở top với context cơ bản (tên instance, trạng thái) — UX-DR10

**Given** Manager xem Activity Log trên trang detail
**When** họ đọc log
**Then** tất cả sự kiện từ Story 3.5 hiển thị theo thứ tự thời gian mới nhất lên đầu

### Story 4.4: Real-time Dashboard & Instance Updates via WebSocket

As a Manager,
I want the dashboard and instance detail to update automatically when process events occur,
So that I always see current status without having to manually refresh the page.

**Acceptance Criteria:**

**Given** Manager đang xem Dashboard
**When** một Executor hoàn thành bước trong bất kỳ instance nào
**Then** traffic light và progress của instance đó cập nhật trong vòng 2 giây mà không reload trang

**Given** Manager đang xem trang detail của một instance
**When** trạng thái bất kỳ bước nào thay đổi
**Then** timeline và progress bar cập nhật real-time

**Given** Reverb WebSocket connection bị mất
**When** connection được restore
**Then** dashboard tự động fetch lại trạng thái hiện tại (missed events recovery)
**And** user không cần refresh tay

**Given** Manager có role `manager` hoặc `process_designer` hoặc `admin`
**When** họ kết nối WebSocket
**Then** họ được subscribe vào channel `organization.{orgId}` (private, RBAC-aware)

### Story 4.5: Inline Manager Ping — Send Message to Executor (FR19)

As a Manager,
I want to send a reminder message to an executor directly from the dashboard or instance detail,
So that I can unblock delays without leaving the system or switching to email.

**Acceptance Criteria:**

**Given** Manager xem trang detail một instance mình đã launch
**When** họ click "Nhắc việc" trên một bước cụ thể
**Then** một inline text input hiện ra ngay tại chỗ (không mở trang mới, không modal phức tạp)

**Given** Manager nhập nội dung nhắc và gửi
**When** message được submit
**Then** message được lưu vào `step_messages` với sender, recipient (executor của bước đó), và step context
**And** confirmation visual hiển thị inline (flash message nhẹ)
**And** activity log ghi nhận hành động nhắc việc

**Given** Manager cố gửi ping trên instance **không phải** do mình launch
**When** họ gửi request
**Then** system trả về 403 Forbidden

**Given** Executor nhận được ping
**When** họ xem tin nhắn
**Then** tin nhắn hiển thị trong ngữ cảnh bước liên quan (không phải inbox riêng)

---

## Epic 5: Executor Inbox & Task Management

Executor có inbox tổng hợp từ mọi quy trình đang tham gia, sắp xếp theo urgency/deadline, hoàn thành task ngay từ danh sách. Trải nghiệm hoạt động mượt mà trên cả desktop lẫn mobile.

### Story 5.1: Executor Inbox — Consolidated & Sorted Task List (FR20, FR21)

As an Executor,
I want to see all my tasks from every running process in a single inbox sorted by urgency,
So that I always know exactly what to work on next without checking multiple places.

**Acceptance Criteria:**

**Given** Executor đăng nhập
**When** họ mở trang Inbox
**Then** tất cả step_executions assigned cho họ (trạng thái pending hoặc in_progress) từ mọi quy trình hiển thị trong một danh sách

**Given** Executor xem Inbox
**When** danh sách render
**Then** tasks được sắp xếp theo thứ tự ưu tiên:
1. Overdue (đỏ) — đã vượt `deadline_at`
2. Due soon (vàng) — còn ≤ 30% thời gian deadline
3. In progress (xanh nhạt) — đang thực hiện, còn thời gian
4. Pending (xám) — chưa acknowledge, còn thời gian

**Given** Executor dùng mobile browser
**When** họ mở Inbox
**Then** mỗi task hiển thị tối giản: tên task, tên quy trình, thời hạn, nút hành động chính — không bị overflow hay cần horizontal scroll

**Given** Executor đang xem Inbox
**When** một task mới được giao cho họ (từ step progression)
**Then** task mới xuất hiện trong Inbox real-time qua WebSocket (`user.{userId}` channel) mà không cần refresh

**Given** Executor không có task nào
**When** Inbox load
**Then** empty state hiển thị: "Bạn không có task nào đang chờ. Tốt lắm! 🎉"

### Story 5.2: View Task Detail with Process Context (FR22)

As an Executor,
I want to view the full details of a task including its context within the larger process,
So that I have enough information to complete my work correctly.

**Acceptance Criteria:**

**Given** Executor click vào một task trong Inbox
**When** trang task detail load
**Then** hiển thị đầy đủ: tên bước, mô tả, tên quy trình, người launch, deadline, trạng thái hiện tại

**Given** Executor xem task detail
**When** họ nhìn vào phần "Ngữ cảnh quy trình"
**Then** họ thấy vị trí bước hiện tại trong flow: "Bước 3/7 — HR làm hợp đồng ✅ → IT cấp thiết bị ▶ → Admin cấp thẻ ⏳"

**Given** Executor xem task detail
**When** có tin nhắn từ Manager (ping từ Story 4.5)
**Then** tin nhắn hiển thị trong ngữ cảnh task, không bị ẩn

**Given** Executor xem task detail trên mobile
**When** trang render
**Then** layout một cột, thông tin ưu tiên hiển thị đầu, nút hành động cố định ở bottom của màn hình

### Story 5.3: Quick Complete Task from Inbox List (FR23)

As an Executor,
I want to complete a task directly from the inbox list without opening the detail page,
So that routine completions take a single action and my inbox shrinks satisfyingly.

**Acceptance Criteria:**

**Given** Executor có task ở trạng thái 'in_progress' trong Inbox
**When** họ click nút "Hoàn thành" ngay trên row trong danh sách
**Then** task được đánh dấu completed ngay lập tức

**Given** task được hoàn thành từ Inbox
**When** completion được lưu
**Then** task row biến mất khỏi danh sách với animation trượt ra (inbox zero metaphor)
**And** nếu đây là task cuối cùng, empty state "Tốt lắm! 🎉" hiện ra

**Given** Executor muốn thêm ghi chú khi hoàn thành từ list view
**When** họ click mũi tên mở rộng trên row (progressive disclosure)
**Then** inline text input cho ghi chú hiện ra ngay trong row, không rời khỏi danh sách

**Given** Executor có task ở trạng thái 'pending' (chưa acknowledge)
**When** họ click "Hoàn thành" từ list
**Then** system tự động acknowledge rồi complete trong một hành động (không yêu cầu 2 bước riêng)

**Given** Executor dùng mobile
**When** họ swipe left trên một task row
**Then** nút "Hoàn thành" hiện ra (swipe-to-complete gesture)

---

## Epic 6: Notifications & Proactive Alerts

Hệ thống tự động gửi email thông báo cho tất cả stakeholders: giao task mới, sắp deadline, chưa acknowledged, vượt deadline. Email có deep link dẫn thẳng đến bước cần xử lý. Beneficiary nhận email trước khi có tài khoản. Ngôn ngữ thông báo định hướng hỗ trợ, không giám sát.

### Story 6.1: Task Assignment Email Notification (FR27)

As an Executor,
I want to receive an email when a new task is assigned to me,
So that I'm immediately aware of new responsibilities even when I'm not actively using the system.

**Acceptance Criteria:**

**Given** một step_execution vừa được giao cho Executor (từ Story 3.1 hoặc 3.3)
**When** `StepCompleted` event fire và `NotifyAssignees` listener xử lý
**Then** email được gửi đến Executor trong vòng 60 giây (NFR2)

**Given** email notification được gửi
**When** Executor nhận được email
**Then** email chứa: tên task, tên quy trình, deadline, và **deep link trực tiếp đến trang task detail** (không phải trang chủ) — UX-DR6

**Given** email notification
**When** Executor đọc nội dung
**Then** ngôn ngữ mang tính thông tin và hỗ trợ: "Bạn có một task mới cần xử lý" (không phải "Bạn được yêu cầu..." kiểu mệnh lệnh) — UX-DR9

**Given** SMTP chưa được cấu hình
**When** system cố gửi email
**Then** lỗi được log ở level ERROR và không crash request flow
**And** Admin nhận cảnh báo qua app notification về cấu hình SMTP chưa hoàn chỉnh

**Given** email gửi thất bại (SMTP error)
**When** queue retry
**Then** system thử lại tối đa 3 lần với exponential backoff trước khi đưa vào failed_jobs

### Story 6.2: Deadline & Unacknowledged Step Alerts (FR28, FR29, FR30)

As a Manager and Executor,
I want to receive proactive alerts when steps are at risk of missing deadlines,
So that I can take action before delays become problems — not after.

**Acceptance Criteria:**

**Given** `CheckApproachingDeadlines` job chạy mỗi phút
**When** job phát hiện bước chưa được acknowledge sau 1 giờ kể từ khi giao
**Then** email cảnh báo gửi đến Manager của instance đó (FR28)
**And** email nêu rõ: tên bước, tên executor, thời gian đã chờ, deep link đến bước đó

**Given** job phát hiện bước còn ≤ 30% thời gian deadline hoặc đã vượt deadline
**When** điều kiện được thỏa mãn
**Then** email gửi đến Manager (FR29) **và** Executor (FR30) trong cùng chu kỳ job

**Given** cảnh báo deadline đã được gửi cho một bước
**When** job chạy lần tiếp theo
**Then** cảnh báo **không** gửi lại cho cùng bước đó (`deadline_notified_at` đã được set)

**Given** email cảnh báo deadline
**When** Manager hoặc Executor nhận được
**Then** ngôn ngữ theo hướng hỗ trợ: "Bước này cần được xử lý sớm để đảm bảo tiến độ" thay vì "CẢNH BÁO: Trễ hạn!" — UX-DR9
**And** email có deep link đến bước cụ thể với nút hành động inline (nhắc việc / xem chi tiết)

**Given** `CheckStateConsistency` job chạy mỗi giờ
**When** phát hiện instance ở trạng thái 'running' nhưng tất cả bước đã completed
**Then** instance được tự động chuyển sang 'completed' và Admin được notify

### Story 6.3: Pre-account Beneficiary Email Notification (FR31)

As a system,
I want to send informational emails to beneficiaries who don't yet have an account,
So that they are kept informed about the process being run on their behalf.

**Acceptance Criteria:**

**Given** Manager launch instance và điền danh sách email beneficiary
**When** instance được khởi chạy
**Then** email thông báo gửi đến từng email beneficiary với: tên quy trình, người khởi chạy, trạng thái sẽ được cập nhật khi có tài khoản

**Given** bước trong quy trình có sự kiện quan trọng liên quan đến beneficiary (ví dụ: bước giao trực tiếp cho beneficiary hoàn thành)
**When** sự kiện xảy ra
**Then** email thông báo gửi đến email beneficiary với thông tin phù hợp

**Given** beneficiary chưa có tài khoản
**When** email được gửi
**Then** email không chứa link đăng nhập (vì chưa có tài khoản)
**And** email giải thích sẽ nhận được thông tin đăng nhập khi quy trình đến bước cấp tài khoản

**Given** email beneficiary không hợp lệ (bounce)
**When** SMTP server trả về bounce
**Then** lỗi được log và Manager của instance được notify để kiểm tra lại email

---

## Epic 7: Beneficiary Experience & In-System Messaging

Beneficiary được tự động tạo tài khoản khi bước "cấp tài khoản" hoàn thành, xem được quy trình của mình, ping step owner. Vòng giao tiếp trong hệ thống (reply trong ngữ cảnh bước) hoàn chỉnh cho tất cả vai trò.

### Story 7.1: Beneficiary Auto-Account Creation (FR36)

As a system,
I want to automatically create a Beneficiary account and send login credentials when the designated step is completed,
So that beneficiaries can access the system at exactly the right moment in the process.

**Acceptance Criteria:**

**Given** một step_execution được cấu hình với `step_type = 'create_account'` được hoàn thành
**When** `CompleteStepExecution` action xử lý
**Then** tài khoản Beneficiary được tạo tự động với: email từ instance context, role 'beneficiary', password ngẫu nhiên an toàn
**And** email chào mừng được gửi với thông tin đăng nhập và deep link vào hệ thống

**Given** tài khoản Beneficiary vừa được tạo
**When** Beneficiary dùng link trong email để đăng nhập lần đầu
**Then** họ được yêu cầu đổi password ngay lập tức

**Given** email beneficiary đã tồn tại trong hệ thống (tài khoản cũ)
**When** step 'create_account' hoàn thành với email đó
**Then** system không tạo tài khoản mới mà liên kết instance với tài khoản đã tồn tại
**And** Beneficiary nhận email thông báo về quy trình mới liên quan đến họ

**Given** tạo tài khoản thất bại (ví dụ: email không hợp lệ)
**When** action gặp lỗi
**Then** lỗi được log và Manager được notify ngay
**And** bước vẫn được đánh dấu completed để không block quy trình

### Story 7.2: Beneficiary View — My Process Status (FR24, FR25)

As a Beneficiary,
I want to see the status of the process being run on my behalf,
So that I know what's happening, who's responsible for the next step, and approximately when it will be done.

**Acceptance Criteria:**

**Given** Beneficiary đăng nhập
**When** họ mở trang chủ
**Then** chỉ thấy các quy trình liên quan đến mình (không thấy quy trình của người khác hoặc tổ chức)

**Given** Beneficiary xem danh sách quy trình của mình (FR24)
**When** trang load
**Then** mỗi quy trình hiển thị: tên, trạng thái tổng thể, bước hiện tại, % hoàn thành

**Given** Beneficiary click vào một quy trình (FR25)
**When** trang detail load
**Then** họ thấy: timeline các bước đã hoàn thành và đang chờ, tên người phụ trách bước hiện tại, deadline ước tính
**And** **không thấy**: thông tin của các quy trình khác, tên các người dùng không liên quan, hay nút can thiệp

**Given** Beneficiary cố truy cập URL của instance không liên quan đến mình
**When** request được gửi
**Then** system trả về 403 Forbidden (Policy kiểm tra `instance->created_for === user->id`)

**Given** quy trình của Beneficiary đã hoàn thành
**When** họ xem
**Then** trạng thái "Đã hoàn thành" hiển thị rõ cùng timeline đầy đủ

### Story 7.3: Beneficiary Ping & In-System Messaging Reply (FR26, FR32)

As a Beneficiary,
I want to send a message to the person responsible for my current step,
So that I can ask questions or provide information without going outside the system.

**Acceptance Criteria:**

**Given** Beneficiary xem trang detail quy trình của mình
**When** họ click "Liên hệ người phụ trách"
**Then** một inline text input hiện ra cho phép nhập tin nhắn

**Given** Beneficiary gửi tin nhắn
**When** message được submit
**Then** tin nhắn được lưu vào `step_messages` với sender (beneficiary), recipient (executor của bước hiện tại), và step context (FR26)
**And** Executor nhận notification (in-app và/hoặc email) về tin nhắn mới

**Given** Executor nhận tin nhắn từ Beneficiary
**When** họ muốn trả lời (FR32)
**Then** họ có thể reply trực tiếp trong ngữ cảnh bước đó
**And** Beneficiary nhận notification về phản hồi

**Given** Manager gửi ping đến Executor (từ Story 4.5)
**When** Executor nhận được và muốn reply
**Then** cùng reply mechanism hoạt động (FR32 áp dụng cho cả Manager→Executor và Beneficiary→Executor)

**Given** Beneficiary cố gửi tin nhắn đến bước không thuộc quy trình của mình
**When** request được gửi
**Then** system trả về 403 Forbidden
