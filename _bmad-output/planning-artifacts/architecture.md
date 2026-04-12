---
stepsCompleted: ['step-01-init', 'step-02-context']
inputDocuments:
  - '_bmad-output/planning-artifacts/prd.md'
  - '_bmad-output/planning-artifacts/prd-validation-report.md'
  - '_bmad-output/planning-artifacts/ux-design-specification.md'
workflowType: 'architecture'
project_name: 'workflow'
user_name: 'huyph'
date: '2026-04-12'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

## Project Context Analysis

### Requirements Overview

**Functional Requirements:**
37 FRs phân thành 7 nhóm domain: Process Template Management (FR1-6),
Instance Execution (FR7-15), Manager Dashboard (FR16-19), Executor Task
Management (FR20-23), Beneficiary Interface (FR24-26), Notification &
Communication (FR27-32), System Administration (FR33-37).

Các FRs phản ánh hệ thống có vòng lõi rõ ràng: tạo template → khởi chạy
instance → tracking real-time → can thiệp khi cần. Đặc biệt: immutable
template versioning (FR6) và beneficiary auto-account creation (FR36) là
hai FR đòi hỏi xử lý data flow phi tuyến tính.

**Non-Functional Requirements:**
- Performance: Dashboard load < 3s với 100 concurrent users (NFR1);
  Notification delivery ≤ 60s từ event trigger (NFR2)
- Reliability: Uptime trong giờ làm việc 08:00-18:00, backup/restore
  không mất dữ liệu (NFR3, NFR4)
- Security: Data on-premise, RBAC enforcement với audit log vi phạm (NFR5, NFR6)
- Deployability: Cài đặt < 1 giờ bởi người có kiến thức server cơ bản,
  Docker packaging (NFR8, NFR9)

**Scale & Complexity:**

- Primary domain: Full-stack web (SaaS B2B, on-premise / single-tenant)
- Complexity level: Medium
- Target scale: 100 concurrent users, ~30 active processes, single organization
- Estimated architectural components: ~8-10 (auth, template engine,
  instance runner, dashboard API, executor API, notification service,
  scheduler, admin API, activity log)

### Technical Constraints & Dependencies

- **Single-tenant**: Mỗi cài đặt phục vụ 1 tổ chức — không cần multi-tenant
  data isolation
- **No horizontal scaling for MVP**: Vertical scaling đủ cho 100 users
- **SMTP only**: Kênh notification duy nhất trong MVP — không phụ thuộc
  external messaging service
- **No external API integrations**: MVP hoàn toàn self-contained
- **Docker packaging**: Deployment phải có thể thực hiện bằng docker-compose
  hoặc tương đương
- **Design system locked**: Shadcn/ui + Tailwind CSS (đã quyết định ở UX spec)
- **Web responsive only**: Không cần native app; responsive web đủ cho
  cả desktop lẫn mobile

### Cross-Cutting Concerns Identified

1. **Authentication & RBAC (5 roles)** — Ảnh hưởng mọi API endpoint và
   data query. Access scope khác nhau theo từng vai trò cần được enforce
   nhất quán ở tầng middleware/service, không phải controller.

2. **Audit Logging** — FR15 yêu cầu log đầy đủ mọi hành động trên instance.
   Cần chiến lược append-only event log — ảnh hưởng thiết kế data model.

3. **Real-time Data Delivery** — Dashboard và Executor Inbox cần cập nhật
   khi có thay đổi. Phải chọn mechanism phù hợp (WebSocket / SSE / polling)
   cân bằng giữa complexity và self-hosted deployment.

4. **Background Job Scheduling** — Deadline monitoring (FR28-FR30) và
   notification delivery cần scheduler chạy độc lập với request cycle.

5. **Template Versioning / Immutable Snapshot** — Instance phải giữ nguyên
   cấu trúc template tại thời điểm khởi tạo. Yêu cầu snapshot strategy
   hoặc versioning scheme trong data model.

6. **Optimistic UI Consistency** — UX spec yêu cầu immediate feedback khi
   hoàn thành task. Cần cơ chế rollback và error handling khi server
   trả về lỗi sau optimistic update.
