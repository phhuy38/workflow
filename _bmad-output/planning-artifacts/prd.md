---
stepsCompleted: ['step-01-init', 'step-02-discovery', 'step-01b-continue', 'step-02b-vision', 'step-02c-executive-summary']
inputDocuments: ['_bmad-output/brainstorming/brainstorming-session-2026-04-08-1500.md']
workflowType: 'prd'
classification:
  projectType: 'saas_b2b'
  domain: 'general'
  complexity: 'medium'
  projectContext: 'greenfield'
---

# Product Requirements Document - workflow

**Author:** huyph
**Date:** 2026-04-10

## Executive Summary

Workflow là hệ thống quản lý quy trình doanh nghiệp (Workflow Management System) dạng SaaS B2B, được xây dựng cho **Manager** — người chịu trách nhiệm về kết quả khi quy trình vận hành, không phải người thiết kế hay vận hành quy trình. Hệ thống giải quyết bài toán cốt lõi: khi tổ chức tăng trưởng, các quy trình liên phòng ban trở nên quá phức tạp để quản lý bằng email hay Excel — Manager mất khả năng quan sát, mất accountability, và không thể can thiệp kịp thời khi có sự cố.

Đối tượng người dùng chính: Manager (người khởi tạo và giám sát quy trình), Process Designer (admin thiết kế template), và Executor (nhân viên thực hiện từng bước). Thị trường mục tiêu: doanh nghiệp vừa và lớn tại Việt Nam đang vận hành nhiều quy trình lặp lại liên phòng ban (onboarding, approval, procurement, v.v.).

### What Makes This Special

**Actionable visibility** — không chỉ hiển thị trạng thái mà hiển thị *đúng thông tin Manager cần, đúng lúc, với khả năng tương tác ngay tại chỗ*. Manager nhìn thấy toàn bộ quy trình đang chạy: ai đang delay, tiến độ bao nhiêu phần trăm, ước tính thời gian hoàn thành — và có thể nhắc việc, override, hoặc leo thang trực tiếp trong hệ thống mà không cần chuyển sang email hay gọi điện.

**Core insight:** Các công cụ workflow hiện tại được thiết kế cho *người vận hành* (HR, Admin) — người tạo và chạy quy trình. Workflow lật ngược triết lý thiết kế: phục vụ *người chịu hậu quả* khi quy trình delay. Đây là điểm mà người dùng nhận ra sự khác biệt ngay lần đầu dùng.

**Thời điểm phù hợp:** Doanh nghiệp đang scale nhanh — số quy trình, số người tham gia, số bước thực hiện tăng theo cấp số nhân. Email và Excel đã vượt điểm tới hạn, nhưng các enterprise tool hiện có quá nặng nề và không phù hợp với cách doanh nghiệp Việt Nam vận hành.

## Project Classification

| Thuộc tính | Giá trị |
|---|---|
| Loại dự án | SaaS B2B |
| Domain | Quản lý quy trình doanh nghiệp |
| Độ phức tạp | Trung bình (Medium) |
| Bối cảnh | Greenfield (xây mới từ đầu) |
