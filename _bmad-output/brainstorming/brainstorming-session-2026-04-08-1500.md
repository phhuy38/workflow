---
stepsCompleted: [1, 2, 3, 4, 5, 6]
inputDocuments: []
session_topic: 'Website quản lý quy trình công việc (Workflow Management System) trong doanh nghiệp'
session_goals: 'Phát triển ý tưởng và tìm giải pháp xây dựng hệ thống'
selected_approach: 'ai-recommended'
techniques_used: ['First Principles Thinking', 'Role Playing', 'SCAMPER Method', 'What If Scenarios', 'Reverse Brainstorming', 'Constraint Mapping']
ideas_generated: 62
context_file: ''
session_status: 'in_progress'
session_continued: true
continuation_date: '2026-04-09'
---

# Brainstorming Session Results

**Người dùng:** huyph
**Ngày:** 2026-04-08

---

## Session Overview

**Chủ đề:** Website quản lý quy trình công việc (Workflow Management System) trong doanh nghiệp
**Mục tiêu:** Phát triển ý tưởng và tìm giải pháp xây dựng hệ thống

### Session Setup

Phiên brainstorming tập trung vào việc xây dựng website quản lý workflow doanh nghiệp. Đi từ First Principles để xác định bản chất bài toán, sau đó khám phá qua 6 vai trò stakeholder, tinh chỉnh bằng SCAMPER, và bổ sung thêm yêu cầu mới.

---

## Technique Selection

**Hướng tiếp cận:** AI-Recommended Techniques
**Ngữ cảnh phân tích:** Website quản lý quy trình công việc với trọng tâm phát triển ý tưởng và giải pháp

**Kỹ thuật được đề xuất:**
- **First Principles Thinking:** Phá bỏ giả định, xây dựng từ sự thật cơ bản về quản lý workflow
- **Role Playing:** Khám phá từ góc nhìn đa stakeholder (Manager, IT, Designer, CEO, HR, Nhân viên mới)
- **SCAMPER Method:** Tinh chỉnh ý tưởng thành tính năng cụ thể, khả thi

---

## Phase 1: First Principles Thinking

**Sự thật cốt lõi được xác định:**
- Không biết quy trình có bao nhiêu bước
- Không biết đang thực hiện ở bước nào
- Không biết mỗi bước cần ai thực hiện
- Không biết bao giờ hoàn thành cả quy trình
- Không biết ai chịu trách nhiệm cho toàn bộ quy trình

**Core Insight:** Vấn đề = Thiếu visibility + Thiếu accountability + Thiếu predictability

**Quyết định kiến trúc quan trọng:**
- Hệ thống xử lý **quy trình lặp lại** (như onboarding), không phải dự án một lần
- Người đau nhất = Manager của người thụ hưởng quy trình
- Người thiết kế quy trình = Admin/Quản trị viên có quyền tạo, chỉnh sửa
- Flow hỗ trợ **song song** và **điều kiện rẽ nhánh**
- Khi sửa template, instance đang chạy giữ nguyên phiên bản cũ hoặc hủy và tạo lại

---

**[Idea #1]: The Pain Owner is the Manager**
_Concept:_ Manager của người thụ hưởng là người chịu thiệt hại trực tiếp khi quy trình trễ — nhân viên không productive, team bị ảnh hưởng. Đây là người cần được phục vụ đầu tiên.
_Novelty:_ Hầu hết workflow tools thiết kế cho người vận hành quy trình (HR), không phải người chịu hậu quả (Manager).

**[Idea #2]: Hai vai trò cốt lõi — Designer vs Runner**
_Concept:_ Hệ thống tách biệt rõ: người thiết kế quy trình (admin) và người chạy quy trình (manager, HR). Hai nhóm có nhu cầu giao diện hoàn toàn khác nhau.
_Novelty:_ Designer cần canvas linh hoạt; Runner chỉ cần dashboard đơn giản.

**[Idea #3]: Immutable Process Instances**
_Concept:_ Mỗi lần chạy quy trình là một "instance" độc lập, đóng băng theo phiên bản tại thời điểm khởi tạo. Thay đổi template chỉ áp dụng cho instance mới.
_Novelty:_ Giống Git — commit cũ không bị ảnh hưởng khi code thay đổi.

**[Idea #4]: Cancel & Restart as First-Class Feature**
_Concept:_ Hủy một instance đang chạy và tạo lại theo template mới là một hành động chính thức trong hệ thống — có log, có lý do, có người xác nhận.
_Novelty:_ Hệ thống chấp nhận sự gián đoạn và quản lý nó một cách minh bạch.

**[Idea #5]: Parallel Execution Lanes**
_Concept:_ Quy trình có thể chia thành các "lanes" chạy đồng thời — mỗi lane tiến độc lập, hệ thống tự động merge khi tất cả hoàn thành trước khi sang bước tiếp theo.
_Novelty:_ Phản ánh đúng thực tế công ty — nhiều phòng ban làm việc song song.

**[Idea #6]: Conditional Branch Engine**
_Concept:_ Mỗi bước có thể gắn điều kiện dựa trên thuộc tính của instance. Hệ thống tự động bỏ qua hoặc thêm bước phù hợp khi chạy.
_Novelty:_ Một template duy nhất xử lý nhiều trường hợp khác nhau.

**[Idea #7]: Visual Flow Builder**
_Concept:_ Designer định nghĩa parallel lanes và conditional branches qua giao diện kéo-thả trực quan — người dùng nhìn thấy flow như sơ đồ.
_Novelty:_ Giảm rào cản cho người quản trị không có kỹ thuật.

---

## Phase 2: Role Playing

### Vai trò #1: Manager

**[Idea #8]: Acknowledgment Gate**
_Concept:_ Mỗi bước không chỉ được "giao" — người nhận phải xác nhận đã nhận việc trong vòng X giờ. Nếu không, hệ thống tự động leo thang lên manager hoặc người backup.
_Novelty:_ Phân biệt rõ "đã giao" vs "đã nhận" vs "đang làm" vs "hoàn thành" — 4 trạng thái khác nhau.

**[Idea #9]: Proactive Kickoff Notification**
_Concept:_ Khi manager tạo instance, hệ thống tự động thông báo cho người phụ trách bước đầu tiên ngay lập tức.
_Novelty:_ Manager chỉ cần "bắn súng khởi đầu", hệ thống tự lo phần còn lại.

**[Idea #10]: Step Activity Log**
_Concept:_ Mỗi bước có nhật ký hoạt động: thời điểm giao việc, thời điểm nhận, ghi chú của người thực hiện, lý do chậm trễ.
_Novelty:_ Biến "không biết chuyện gì đang xảy ra" thành "timeline minh bạch có thể audit".

**[Idea #11]: Deadline Countdown với SLA Indicator**
_Concept:_ Mỗi bước hiển thị: đã chạy bao lâu / tổng thời gian cho phép / còn lại bao nhiêu — dưới dạng visual (xanh/vàng/đỏ).
_Novelty:_ SLA không chỉ là con số — nó là tín hiệu hành động trực quan.

**[Idea #12]: Manager Override — Manual Step Completion**
_Concept:_ Manager có quyền tự đánh dấu hoàn thành một bước (bắt buộc ghi lý do), bypass người thực hiện trong tình huống khẩn cấp. Hành động được log là "completed by override".
_Novelty:_ Trao quyền thực sự cho người chịu trách nhiệm.

**[Idea #13]: In-System Nudge Notification**
_Concept:_ Manager có thể gửi "nhắc việc chính thức" trực tiếp từ hệ thống. Người nhận có thể reply ngay tại đó.
_Novelty:_ Giữ toàn bộ communication liên quan đến bước trong một nơi.

### Vai trò #2: IT (Người thực hiện)

**[Idea #14]: My Work Inbox — Cross-Process Task View**
_Concept:_ Mỗi người thực hiện có "inbox" cá nhân tổng hợp TẤT CẢ bước được giao từ mọi quy trình đang chạy.
_Novelty:_ Người thực hiện chỉ quan tâm "tôi cần làm gì hôm nay" — hệ thống tư duy theo góc nhìn của họ.

**[Idea #15]: Urgency-First Sorting với Deadline Visibility**
_Concept:_ Inbox tự động sắp xếp theo thời gian còn lại tăng dần — task sắp hết hạn nhất luôn ở trên cùng.
_Novelty:_ Loại bỏ câu hỏi "tôi nên làm cái gì trước?"

**[Idea #16]: One-Click Complete on List View**
_Concept:_ Nút "Hoàn thành" ngay trên danh sách — không cần mở chi tiết task. Phân biệt "quick complete" cho task đơn giản vs "complete with notes" cho task phức tạp.
_Novelty:_ UX phù hợp với từng loại công việc.

### Vai trò #3: Process Designer

**[Idea #17]: Structured Step Output Form**
_Concept:_ Mỗi bước có form output được Designer cấu hình sẵn — gồm field bắt buộc và tùy chọn. Không thể hoàn thành nếu chưa điền đủ field bắt buộc.
_Novelty:_ Biến "hoàn thành bước" thành cổng kiểm soát chất lượng.

**[Idea #18]: Step Data Pipeline**
_Concept:_ Output của bước N tự động trở thành input hiển thị trong bước N+1.
_Novelty:_ Loại bỏ hoàn toàn vòng lặp hỏi-đáp giữa các phòng ban.

**[Idea #19]: Completion Gate — Required Fields as Quality Checkpoint**
_Concept:_ Designer đánh dấu field nào là bắt buộc cho từng bước. Mỗi bước khi hoàn thành là một "cam kết dữ liệu" chính thức.
_Novelty:_ Chất lượng dữ liệu được build vào quy trình, không phụ thuộc ý thức cá nhân.

**[Idea #20]: Process Template Cloning**
_Concept:_ Designer có thể clone bất kỳ template nào làm điểm xuất phát — toàn bộ bước, cấu hình, điều kiện được sao chép. Chỉ sửa những gì khác biệt.
_Novelty:_ Công ty không bao giờ xây quy trình từ số 0.

**[Idea #21]: Partial Step Reuse — Shared Step Library**
_Concept:_ Các bước phổ biến được lưu thành thư viện tái sử dụng. Thay đổi bước trong thư viện có thể sync sang tất cả template đang dùng bước đó.
_Novelty:_ Thay đổi một lần, cập nhật nhiều nơi.

**[Idea #22]: Designer Dry Run Mode**
_Concept:_ Khi chạy thử, hệ thống tạo instance với `is_dry_run: true` — toàn bộ bước được gán cho chính Designer. Designer tự đi qua mọi bước để test.
_Novelty:_ Designer trải nghiệm đúng góc nhìn của từng vai trò thực thi.

**[Idea #23]: Dry Run Exclusion từ Analytics**
_Concept:_ Instance có flag `is_dry_run` bị lọc hoàn toàn khỏi mọi báo cáo thống kê.
_Novelty:_ Designer test thoải mái bất kỳ lúc nào mà không lo ảnh hưởng dashboard.

**[Idea #24]: Process Performance Analytics**
_Concept:_ Mỗi template có dashboard riêng: bước nào có average completion time cao nhất, bị override nhiều nhất, hay có ghi chú chậm trễ, field nào thường bỏ trống.
_Novelty:_ Dữ liệu vận hành thành feedback loop cải thiện quy trình.

**[Idea #25]: Bottleneck Detection Alert**
_Concept:_ Nếu bước X bị trễ trong >30% instance, Designer nhận alert gợi ý xem xét lại.
_Novelty:_ Chuyển từ "Designer chủ động phân tích" sang "hệ thống chủ động gợi ý".

**[Idea #26]: Process Version Comparison**
_Concept:_ Designer có thể so sánh KPI giữa phiên bản cũ và mới — thời gian hoàn thành, tỷ lệ trễ hạn, tỷ lệ override.
_Novelty:_ Quản lý quy trình trở thành thực hành có dữ liệu, không phải cảm tính.

### Vai trò #4: CEO

**[Idea #27]: Executive Dashboard — Filterable KPI View**
_Concept:_ Trang riêng cho CEO với thông số tổng hợp: số instance đang chạy, tỷ lệ hoàn thành đúng hạn, thời gian trung bình, phòng ban nào là bottleneck. Bộ lọc theo thời gian, phòng ban, loại quy trình.
_Novelty:_ CEO thấy xu hướng và điểm nghẽn ở cấp độ tổ chức.

### Vai trò #5: HR

**[Idea #28]: Configurable Instance Intake Form**
_Concept:_ Designer cấu hình sẵn form khởi tạo instance. HR chỉ điền form đã được thiết kế sẵn.
_Novelty:_ Dữ liệu khởi đầu được chuẩn hóa ngay từ đầu.

**[Idea #29]: Manual Instance Creation với Role Assignment**
_Concept:_ Bất kỳ ai có quyền (Manager, CEO, không chỉ HR) đều có thể tạo instance và chỉ định process owner.
_Novelty:_ Tránh single point of failure — quy trình không bị tắc vì HR quên hoặc vắng mặt.

**[Idea #30]: HR Overview Dashboard — All Instances View**
_Concept:_ HR có dashboard hiển thị tất cả instance đang chạy — mỗi dòng là một người, hiển thị bước hiện tại, % hoàn thành, ngày bắt đầu làm việc, trạng thái tổng thể.
_Novelty:_ HR nhìn một màn hình thấy ngay toàn bộ pipeline nhân sự.

**[Idea #31]: Days Until Start Date — Countdown Priority**
_Concept:_ Dashboard HR sắp xếp theo countdown đến ngày nhân viên bắt đầu làm việc — người nào bắt đầu sớm nhất hiển thị trên cùng.
_Novelty:_ Priority dựa trên business event, không phải SLA kỹ thuật.

### Vai trò #6: Nhân viên mới

**[Idea #32]: Employee Self-Service Onboarding Portal**
_Concept:_ Nhân viên mới có tài khoản riêng để xem toàn bộ quy trình onboarding của mình — các bước, ai phụ trách, bước nào xong, bước nào đang chờ.
_Novelty:_ Biến nhân viên mới từ "người thụ động chờ đợi" thành "người có thể chủ động theo dõi".

**[Idea #33]: Expected Ready Date**
_Concept:_ Dựa trên tiến độ, hệ thống tự tính và hiển thị: "Laptop của bạn dự kiến sẵn sàng vào thứ Ba. Email công ty dự kiến có vào thứ Hai."
_Novelty:_ Thông tin thiết thực nhất với người mới: "Tôi cần chuẩn bị gì và khi nào?"

**[Idea #34]: Stakeholder Nudge — Anyone Can Remind**
_Concept:_ Bất kỳ người nào liên quan đến instance đều có thể gửi thông báo nhắc nhở đến người đang phụ trách bước bị trễ.
_Novelty:_ Nhân viên mới không phụ thuộc hoàn toàn vào Manager để thúc đẩy quy trình của mình.

**[Idea #35]: Nudge Rate Limiting**
_Concept:_ Mỗi người chỉ được gửi nudge tối đa 1 lần/ngày cho cùng một bước. Hệ thống log ai đã nhắc, lúc mấy giờ.
_Novelty:_ Cân bằng giữa trao quyền stakeholder và bảo vệ người thực hiện khỏi spam.

---

## Phase 3: SCAMPER

**[Idea #36]: Flexible Assignment at Instance Launch**
_Concept:_ Template định nghĩa người thực hiện mặc định. Khi tạo instance, Manager có cửa sổ review và override assignment trước khi quy trình bắt đầu chạy.
_Novelty:_ Cân bằng tính nhất quán của template và tính linh hoạt của thực tế.

**[Idea #37]: Domain-Agnostic Workflow Engine**
_Concept:_ Hệ thống không hard-code cho HR — engine xử lý bất kỳ quy trình lặp lại nào trong công ty. HR dùng để onboarding, Finance dùng để phê duyệt ngân sách, Product dùng để release tính năng.
_Novelty:_ Một hệ thống workflow cho toàn công ty — dữ liệu và báo cáo có thể so sánh cross-department.

**[Idea #38]: Template Categories & Department Organization**
_Concept:_ Templates được tổ chức theo phòng ban và danh mục. Phân quyền theo department — mỗi admin chỉ quản lý template của department mình.
_Novelty:_ CEO thấy tất cả; Department Admin chỉ thấy phạm vi của mình.

**[Idea #39]: Dual Approval Mode — Any vs All**
_Concept:_ Designer cấu hình từng bước theo chế độ Any (bất kỳ một người approve là đủ) hoặc All (tất cả phải approve mới hoàn thành).
_Novelty:_ Cùng một engine xử lý được cả quy trình nhanh-linh hoạt lẫn nghiêm ngặt-tuân thủ.

**[Idea #40]: Approval Progress Indicator**
_Concept:_ Với chế độ All, hiển thị: "2/3 người đã approve — còn chờ Nguyễn Văn C". Với chế độ Any, tự động chuyển bước khi có người đầu tiên approve.
_Novelty:_ Thấy được quy trình approve đang kẹt ở đâu cụ thể.

**[Idea #41]: Process Documentation Export**
_Concept:_ Bất kỳ nhân viên nào cũng có thể tìm kiếm template và xuất ra file Word/PDF — bao gồm sơ đồ visual, mô tả từng bước, người phụ trách, thời gian dự kiến, điều kiện rẽ nhánh.
_Novelty:_ Template vừa là công cụ vận hành vừa là tài liệu quy trình luôn đồng bộ với thực tế.

**[Idea #42]: Process Search & Discovery**
_Concept:_ Nhân viên có thanh tìm kiếm để tìm quy trình theo tên, phòng ban, từ khóa. Kết quả hiển thị tên, mô tả ngắn, số bước, thời gian trung bình.
_Novelty:_ Hệ thống workflow trở thành nơi nhân viên tìm hiểu "công ty mình làm việc như thế nào".

**[Idea #43]: Deadline Aggregation — Bottom-Up Calculation**
_Concept:_ Deadline toàn bộ quy trình được tự động tính bằng tổng thời gian tối đa của các bước tuần tự (bước song song tính theo bước dài nhất).
_Novelty:_ Loại bỏ việc Manager phải ước tính thủ công — con số luôn chính xác.

---

## Yêu cầu bổ sung

**[Idea #44]: Step Rejection with Mandatory Justification**
_Concept:_ Người thực hiện có thể từ chối bước — bắt buộc nhập lý do bằng văn bản và tùy chọn đính kèm ảnh/file minh chứng.
_Novelty:_ Từ chối là hành động chính thức có truy vết, bảo vệ cả người từ chối lẫn người quản lý.

**[Idea #45]: Rejection Evidence Attachment**
_Concept:_ Người thực hiện có thể đính kèm ảnh chụp thực tế cùng với lý do từ chối. File được lưu vào activity log vĩnh viễn.
_Novelty:_ Tạo bằng chứng khách quan — giảm tranh chấp giữa các bên sau này.

**[Idea #46]: Rejection Type 1 — Roll Back to Previous Step**
_Concept:_ Người thực hiện chọn "Chuyển về bước trước" khi dữ liệu đầu vào từ bước trước không đủ hoặc sai. Bước trước được mở lại, người phụ trách nhận thông báo hoàn thành lại.
_Novelty:_ Cơ chế sửa lỗi chính thức trong quy trình — không cần Manager can thiệp thủ công.

**[Idea #47]: Rejection Type 2 — Suspend Step with Reopen Option**
_Concept:_ Người thực hiện chọn "Không muốn thực hiện" — bước bị treo, quy trình tạm dừng. Người đó vẫn là assignee và có thể quay lại hoàn thành bất kỳ lúc nào, nhưng bắt buộc ghi lý do.
_Novelty:_ Phân biệt "từ chối vì thiếu thông tin" (rollback) vs "từ chối vì lý do nội bộ" (suspend).

**[Idea #48]: Rejection Notification Chain**
_Concept:_ Khi có từ chối, hệ thống tự động gửi thông báo đến Manager và người phụ trách bước trước — kèm lý do và loại từ chối.
_Novelty:_ Manager không bị bất ngờ khi quy trình dừng — lý do có sẵn trong thông báo.

---

## Tóm tắt kiến trúc hệ thống (Draft)

### Các module chính đã xác định:

| Module | Ý tưởng liên quan |
|---|---|
| Template Builder (Visual Flow) | #2, #5, #6, #7, #17, #18, #19, #20, #21 |
| Instance Management | #3, #4, #28, #29, #36 |
| My Work Inbox (Người thực hiện) | #14, #15, #16 |
| Manager Dashboard | #1, #9, #11, #12, #13 |
| HR Dashboard | #30, #31 |
| Employee Self-Service | #32, #33 |
| CEO/Executive Dashboard | #27 |
| Notification & Escalation | #8, #9, #13, #34, #35, #48 |
| Rejection Flow | #44, #45, #46, #47, #48 |
| Analytics & Reporting | #24, #25, #26, #43 |
| Approval Engine | #39, #40 |
| Process Library | #38, #41, #42 |
| Dry Run / Testing | #22, #23 |

---

## Ghi chú để tiếp tục

- **Còn lại:** Thử kỹ thuật What If Scenarios, Reverse Brainstorming, hoặc Constraint Mapping
- **Chưa giải quyết:** Auto-escalation khi quy trình bị treo quá lâu (để lại roadmap sau)
- **Chưa giải quyết:** Tích hợp hệ thống bên ngoài (HRIS, email, chat) — để lại roadmap sau
- **Tiếp theo:** Tổ chức 48 ý tưởng thành epic và user stories

---

## Phase 4: What If Scenarios _(2026-04-09)_

**Kỹ thuật:** Phá vỡ mọi ràng buộc và giả định để khám phá khả năng đột phá.

### Thread 1: Peer Suggestion Reassignment

**[Idea #49]: Peer Suggestion Reassignment**
_Concept:_ Khi từ chối bước, người thực hiện có thể đề xuất một người cụ thể trong công ty kèm lý do. Manager xem xét và quyết định chấp thuận hay giữ nguyên.
_Novelty:_ Tận dụng trí tuệ phân tán — người trong cuộc biết ai phù hợp hơn bất kỳ template nào.

**[Idea #50]: Hard Stop on Re-Delegation**
_Concept:_ Khi Manager chấp thuận reassign, người nhận bị lock — không thể từ chối hay đề xuất người khác. Giao diện ẩn hoàn toàn nút từ chối cho assignment được gắn flag `is_final_assignee: true`.
_Novelty:_ Tránh vòng lặp delegation vô tận. Trách nhiệm dừng lại tại đây.

**[Idea #51]: Suggestion Fit Reason Log**
_Concept:_ Lý do đề xuất ("Anh Nam chuyên xử lý việc này", "Chị Hoa có access hệ thống phù hợp") được lưu vào activity log của bước — có thể xem lại sau.
_Novelty:_ Biến local knowledge thành dữ liệu tổ chức có thể tham chiếu.

**[Idea #52]: Template Suggestion Signal**
_Concept:_ Nếu cùng một người được đề xuất ≥3 lần cho cùng loại bước trên nhiều instance, hệ thống tạo alert cho Designer: "Cân nhắc cập nhật người mặc định cho bước này."
_Novelty:_ Pattern thực tế tự động feed ngược về cải thiện template.

### Thread 2: Gamification — Điểm số cá nhân

**[Idea #53]: Personal Performance Score (Private)**
_Concept:_ Mỗi người có dashboard cá nhân hiển thị điểm tổng hợp: tỷ lệ hoàn thành đúng hạn, số bước bị override, số lần từ chối, thời gian xử lý trung bình so với SLA. Chỉ bản thân thấy.
_Novelty:_ Người dùng có gương phản chiếu hành vi mà không bị phán xét công khai.

**[Idea #54]: Manager Performance View**
_Concept:_ Manager thấy được điểm số của từng thành viên trong team — không phải bảng xếp hạng, mà là profile riêng từng người: điểm mạnh/yếu theo loại bước, xu hướng theo thời gian.
_Novelty:_ Dữ liệu workflow trở thành input cho 1-1 và review — không cần Manager tự nhớ hay ghi chép.

**[Idea #55]: Score Breakdown by Behavior**
_Concept:_ Điểm không phải một con số duy nhất mà chia thành 4 chiều: Timeliness (đúng hạn), Reliability (ít bị override), Responsiveness (tốc độ acknowledge), Quality (ít bị rollback từ bước sau). Mỗi chiều có màu riêng.
_Novelty:_ Người dùng hiểu rõ tại sao điểm thay đổi, không chỉ biết điểm tăng hay giảm.

**[Idea #56]: Score Trend Over Time**
_Concept:_ Dashboard cá nhân hiển thị đồ thị xu hướng 30/60/90 ngày — không chỉ điểm hiện tại mà thấy mình đang cải thiện hay đi xuống.
_Novelty:_ Khuyến khích nhìn dài hạn, không bị ám ảnh bởi một bước trễ.

**[Idea #57]: Contextual Score Normalization**
_Concept:_ Điểm được tính tương đối theo độ khó của bước — hoàn thành đúng hạn một bước có SLA 2 giờ tính điểm cao hơn bước có SLA 3 ngày. Bước nhiều dependency được giảm trừ ít hơn nếu trễ.
_Novelty:_ Chống bất công — người nhận việc khó không bị thiệt thòi so với người nhận việc dễ.

---

## Phase 5: Reverse Brainstorming _(2026-04-09)_

**Kỹ thuật:** Hỏi ngược "Làm thế nào để hệ thống thất bại?" để tìm insight ẩn.

**[Idea #58]: Mobile App — Người thực hiện on-the-go**
_Concept:_ Phiên bản mobile tập trung vào "My Work Inbox" — xem task, acknowledge, hoàn thành bước, gửi nudge. Tối ưu cho người thực hiện cần xử lý công việc khi không ở máy tính.
_Novelty:_ Loại bỏ rào cản "phải ngồi vào máy mới xử lý được" — đặc biệt hữu ích cho các bước cần xác nhận nhanh.

**[Idea #59]: Mobile Push Notification thay Email**
_Concept:_ Thay vì gửi email thông báo, hệ thống push thẳng lên điện thoại — tap vào mở ngay bước cần xử lý, không cần login từ đầu.
_Novelty:_ Giảm friction từ "nhận thông báo" đến "hoàn thành hành động" xuống còn vài giây.

---

## Phase 6: Constraint Mapping _(2026-04-09)_

**Kỹ thuật:** Liệt kê và phân loại ràng buộc để tìm con đường vượt qua.

**Ràng buộc lớn nhất được xác định:** Độ phức tạp của quy trình thực tế trong công ty — có thể có những yếu tố không thể phản ánh lên phần mềm ngay từ đầu.
**Chiến lược:** Build MVP, chạy thực tế, thu thập gap data, iterate.

**[Idea #60]: Feedback Loop sau mỗi Instance hoàn thành**
_Concept:_ Khi một instance kết thúc, Manager nhận prompt ngắn: "Quy trình này có phản ánh đúng thực tế không? Bước nào bị thiếu hoặc thừa?" — câu trả lời được gửi thẳng đến Designer.
_Novelty:_ Thu thập gap data từ người vận hành, không phải từ Designer ngồi đoán.

**[Idea #61]: Freeform Note per Step — "Không fit vào form"**
_Concept:_ Bên cạnh structured output form, mỗi bước luôn có ô ghi chú tự do "Điều gì xảy ra ngoài quy trình?" — không bắt buộc, nhưng luôn có sẵn.
_Novelty:_ Capture được những yếu tố thực tế chưa được template hóa — dữ liệu thô để cải thiện sau.

**[Idea #62]: Template Suggestion from Instance History**
_Concept:_ Sau N instance, hệ thống phân tích pattern trong freeform notes và gợi ý Designer: "80% instance có ghi chú về bước X — cân nhắc thêm sub-step hoặc field mới."
_Novelty:_ Hệ thống tự học từ khoảng cách giữa template và thực tế.

---

## Cập nhật bảng kiến trúc module

| Module | Ý tưởng liên quan |
|---|---|
| Template Builder (Visual Flow) | #2, #5, #6, #7, #17, #18, #19, #20, #21 |
| Instance Management | #3, #4, #28, #29, #36 |
| My Work Inbox (Người thực hiện) | #14, #15, #16, #58, #59 |
| Manager Dashboard | #1, #9, #11, #12, #13 |
| HR Dashboard | #30, #31 |
| Employee Self-Service | #32, #33 |
| CEO/Executive Dashboard | #27 |
| Notification & Escalation | #8, #9, #13, #34, #35, #48, #59 |
| Rejection & Reassignment Flow | #44, #45, #46, #47, #48, #49, #50, #51 |
| Analytics & Reporting | #24, #25, #26, #43, #52 |
| Performance Score | #53, #54, #55, #56, #57 |
| Approval Engine | #39, #40 |
| Process Library | #38, #41, #42 |
| Dry Run / Testing | #22, #23 |
| Feedback & Continuous Improvement | #60, #61, #62 |
| Mobile App | #58, #59 |

---

## Ghi chú để tiếp tục (cập nhật 2026-04-09)

- **Hoàn thành:** What If Scenarios, Reverse Brainstorming, Constraint Mapping
- **Tiếp theo:** Tổ chức 62 ý tưởng thành epics & user stories
- **Roadmap sau:** Auto-escalation, tích hợp hệ thống bên ngoài (HRIS, email, chat), Mobile App
