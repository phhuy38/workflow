---
stepsCompleted: ['step-01-init', 'step-02-discovery', 'step-01b-continue', 'step-02b-vision', 'step-02c-executive-summary', 'step-03-success', 'step-04-journeys', 'step-05-domain']
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

## Success Criteria

### User Success

- Manager giảm thời gian hỏi tiến độ về gần 0 — thông tin luôn sẵn có trên dashboard mà không cần chủ động hỏi
- Tỷ lệ quy trình bị delay ẩn (không biết cho đến khi quá trễ) giảm từ ~50% xuống dưới 30%
- Manager nhận thông báo chủ động khi: (1) người trong quy trình báo cáo vấn đề, hoặc (2) bước sắp tới deadline
- Manager không cần can thiệp liên tục vào các quy trình đang chạy bình thường

### Business Success

- **3 tháng:** 100% quy trình nội bộ (~30 quy trình) được số hóa và vận hành hàng ngày — không còn quy trình nào chạy qua email hay Excel
- **12 tháng:** 100 người dùng đang sử dụng thường xuyên; thời gian xử lý quy trình giảm đo được; tỷ lệ sai sót/bỏ sót trong quy trình giảm rõ rệt
- **Packagability:** Hệ thống có thể đóng gói và triển khai tại doanh nghiệp khác không cần tùy chỉnh lớn

### Technical Success

- **Uptime:** Không downtime trong giờ làm việc (08:00–18:00 các ngày làm việc)
- **Hiệu năng:** Dashboard và chi tiết quy trình load ở tốc độ chấp nhận được với 100 người dùng đồng thời
- **Self-service deployment:** Cài đặt và vận hành không cần IT chuyên biệt — người có kiến thức server cơ bản thực hiện được
- **Data ownership:** Toàn bộ dữ liệu lưu trên server doanh nghiệp, không phụ thuộc bên thứ ba

### Measurable Outcomes

| Metric | Hiện tại | Mục tiêu |
|---|---|---|
| Thời gian Manager hỏi tiến độ | Thủ công / không đo được | ≈ 0 |
| Tỷ lệ delay ẩn | ~50% | < 30% |
| Số quy trình được số hóa | 0 | 30 (100%) trong 3 tháng |
| Tỷ lệ adoption người dùng | 0% | 100% trong 12 tháng |

## Product Scope

### MVP — Minimum Viable Product

Core loop: **tạo → chạy → theo dõi → can thiệp**

- **Process Template Builder:** Tạo/chỉnh sửa template với các bước tuần tự, giao việc cho vai trò/người cụ thể, deadline theo bước
- **Process Instance Runner:** Khởi tạo instance từ template, tracking real-time (% hoàn thành, bước hiện tại, thời gian còn lại)
- **Manager Dashboard:** Toàn cảnh tất cả instance đang chạy — trạng thái, người delay, bước quá hạn
- **Executor Inbox:** Danh sách task cá nhân từ tất cả quy trình, sắp xếp theo urgency/deadline
- **Notification System:** Thông báo proactive khi bước có vấn đề hoặc sắp deadline
- **Activity Log:** Lịch sử hành động và trạng thái theo từng instance

### Growth Features (Post-MVP)

- **Parallel Execution Lanes:** Các bước chạy song song, tự động merge khi tất cả hoàn thành
- **Conditional Branching:** Rẽ nhánh dựa trên điều kiện/thuộc tính của instance
- **Step Output Forms:** Form output cấu hình field bắt buộc; output bước N thành input bước N+1
- **Analytics nâng cao:** Bottleneck analysis, thời gian trung bình theo bước, SLA compliance report
- **Manager Override & Auto-escalation:** Quy tắc leo thang tự động; override có log

### Vision (Future)

- **External Integrations:** Kết nối hệ thống bên ngoài (HR, ERP, CRM, Slack, email) để trigger hoặc nhận dữ liệu
- **Customer-Facing Portal:** Khách hàng doanh nghiệp xem trạng thái quy trình liên quan đến họ (đơn hàng, hợp đồng, yêu cầu dịch vụ)
- **Multi-tenant Packaging:** Đóng gói hoàn chỉnh với tách biệt dữ liệu cho nhiều doanh nghiệp

## User Journeys

### Journey 1 — Minh, Manager: Khởi động quy trình mượt mà

Minh là trưởng phòng kinh doanh. Thứ Hai, anh nhận được email HR thông báo có nhân viên mới bắt đầu tuần sau. Trước đây, Minh sẽ phải tự nhắn từng phòng ban — IT chuẩn bị máy, HR làm hợp đồng, Admin cấp thẻ. Tuần nào cũng vài lần quên, vài lần nhắc lại.

Hôm nay anh mở hệ thống, chọn template "Onboarding nhân viên mới", điền tên và ngày bắt đầu của nhân viên, nhấn Khởi động. Hệ thống tự động giao việc cho từng bước đầu tiên và gửi thông báo. Minh đóng tab lại.

Trong 5 ngày tiếp theo, anh mở dashboard mỗi sáng — tất cả đều xanh. Ngày thứ 6, nhân viên mới đến có đủ máy tính, thẻ, hợp đồng. Minh chưa nhắn một tin nào.

*Yêu cầu phát sinh: Template builder, Instance runner, Manager dashboard, Notification system.*

---

### Journey 2 — Minh, Manager: Phát hiện và xử lý bottleneck

Giữa tuần, Minh nhận thông báo: bước "Cấp tài khoản hệ thống" trong quy trình onboarding của Hải đã quá 24 giờ chưa được xác nhận. Anh mở chi tiết — thấy Tuấn (IT) được giao nhưng chưa phản hồi.

Minh gửi nhắc việc ngay trong hệ thống kèm ghi chú: *"Hải vào làm thứ Sáu, cần xong trước thứ Năm."* 20 phút sau Tuấn phản hồi: đang chờ cấp quyền từ cấp trên. Minh thấy vấn đề không thuộc Tuấn — anh liên hệ thẳng người có quyền, giải quyết trong ngày.

Mọi hành động đều có log. Nếu sau này có câu hỏi về việc ai delay ở bước nào, timeline minh bạch hoàn toàn.

*Yêu cầu phát sinh: Proactive notifications, in-system messaging, activity log, visibility vào từng bước.*

---

### Journey 3 — Lan, Process Designer: Số hóa một quy trình

Lan là HR manager, được giao nhiệm vụ số hóa toàn bộ quy trình nội bộ. Cô bắt đầu với quy trình onboarding — thứ cô đã chạy bằng email 3 năm nay.

Cô mở Template Builder, tạo các bước theo đúng thứ tự thực tế: HR làm hợp đồng → IT cấp thiết bị → Admin cấp thẻ → Manager giới thiệu team. Mỗi bước cô gán cho phòng ban phụ trách và đặt deadline mặc định. Bước IT cô đánh dấu: nếu nhân viên là developer thì thêm bước "Cài đặt môi trường dev".

Cô publish template. Từ hôm nay, bất kỳ manager nào cũng có thể khởi động quy trình này trong 30 giây.

*Yêu cầu phát sinh: Template builder với step sequencing, role assignment, deadline config, conditional logic (Growth).*

---

### Journey 4 — Tuấn, Executor: Một ngày làm việc có cấu trúc

Tuấn là nhân viên IT. Anh tham gia nhiều quy trình cùng lúc — onboarding, procurement, offboarding. Trước đây anh nhận việc qua email, Slack, đôi khi bị bỏ sót.

Sáng thứ Ba, anh mở Inbox cá nhân: 4 task từ 3 quy trình khác nhau, sắp xếp theo deadline gần nhất lên đầu. Task đầu tiên: cấp laptop cho Hải — deadline hôm nay 17:00. Anh xử lý xong, ghi chú "Đã bàn giao, serial: XYZ123", nhấn Hoàn thành. Hệ thống tự chuyển sang bước tiếp theo và thông báo người phụ trách.

Tuấn không cần biết toàn bộ quy trình — chỉ cần biết việc của mình, khi nào cần xong.

*Yêu cầu phát sinh: Executor inbox, urgency sorting, task completion flow, completion notes.*

---

### Journey 5 — Nam, Nhân viên mới (Người thụ hưởng): Từ thụ động đến có tiếng nói

Nam nhận email mời đăng nhập hệ thống — tài khoản vừa được IT tạo xong (bước "Cấp tài khoản" hoàn thành). Anh đăng nhập lần đầu.

Anh thấy một view đơn giản: quy trình onboarding của chính mình, đang ở bước nào, còn bao nhiêu bước nữa, ai phụ trách bước tiếp theo. Anh không thấy các quy trình khác của công ty — chỉ thấy những gì liên quan đến mình.

Ngày thứ 3, anh thấy bước "Cấp chỗ ngồi và thiết bị văn phòng" chưa có tiến triển. Anh gửi ping đến Admin phụ trách bước đó: *"Cho mình hỏi về chỗ ngồi với ạ?"* Admin nhận thông báo, phản hồi ngay trong hệ thống.

Nam không có quyền can thiệp vào quy trình — nhưng anh có tiếng nói. Anh biết chuyện gì đang xảy ra và có thể hỏi trực tiếp người liên quan mà không cần qua Manager.

*Yêu cầu phát sinh: Beneficiary view (chỉ thấy quy trình của mình), ping/message đến step owner, tài khoản với quyền hạn giới hạn, outbound notification trước khi có tài khoản.*

---

### Journey Requirements Summary

| Capability | Journeys liên quan |
|---|---|
| Process Template Builder | J3 |
| Instance Runner + Real-time tracking | J1, J2 |
| Manager Dashboard (toàn cảnh) | J1, J2 |
| Proactive Notifications (delay, deadline) | J2 |
| In-system Messaging / Ping | J2, J5 |
| Activity Log | J2 |
| Executor Inbox (urgency sort) | J4 |
| Task Completion với Notes | J4 |
| Outbound Notification (trước khi có tài khoản) | J5 |
| Beneficiary View (quy trình của mình, giới hạn) | J5 |
| Ping step owner (từ phía người thụ hưởng) | J5 |
| Conditional Logic trong template | J3 (Growth) |
