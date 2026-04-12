---
stepsCompleted: [1, 2, 3, 4, 5, 6]
inputDocuments:
  - '_bmad-output/planning-artifacts/prd.md'
  - '_bmad-output/brainstorming/brainstorming-session-2026-04-08-1500.md'
---

# UX Design Specification - workflow

**Author:** huyph
**Date:** 2026-04-12

---

## Executive Summary

### Project Vision

Workflow lật ngược triết lý thiết kế của các WMS hiện tại: thay vì phục vụ người *vận hành* quy trình, Workflow phục vụ người *chịu hậu quả* khi quy trình delay. Manager có được khả năng quan sát toàn cảnh và quyền can thiệp ngay tại chỗ — không cần chuyển sang email hay gọi điện.

### Target Users

**Manager** — người dùng trung tâm của sản phẩm
- Hành động đầu tiên khi mở app: xem tổng quan toàn bộ quy trình đang chạy
- Cần thấy ngay: ai đang delay, bước nào quá hạn, tiến độ tổng thể
- Muốn can thiệp trực tiếp trong hệ thống (nhắc việc, override, leo thang)

**Executor** — người thực thi
- Dùng cả desktop (văn phòng) lẫn mobile (di chuyển)
- Xử lý nhiều task song song từ nhiều quy trình khác nhau
- Cần inbox rõ ràng, ưu tiên theo urgency/deadline

**Process Designer** — role riêng biệt
- Thiết kế và duy trì template quy trình
- Tần suất thấp hơn, nhưng cần công cụ mạnh và linh hoạt

### Key Design Challenges

1. **Dashboard Manager không được là "data dump"** — Khi có hàng chục quy trình chạy song song, Manager cần thấy ngay *điều gì cần chú ý*, không phải đọc từng dòng. Thách thức: thiết kế visual hierarchy phân biệt rõ "bình thường", "cần theo dõi", "cần can thiệp ngay".

2. **Executor Inbox đa thiết bị** — Executor dùng cả desktop lẫn mobile, nhiều task song song. Thách thức: thiết kế inbox hoạt động tốt trên cả hai form factor mà không tạo ra hai trải nghiệm riêng biệt.

3. **Giảm ma sát can thiệp cho Manager** — Khi phát hiện quy trình delay, Manager phải hành động ngay (nhắc việc, leo thang) mà không bị gián đoạn bởi nhiều bước xác nhận hay chuyển màn hình.

### Design Opportunities

1. **Dashboard-first onboarding** — Vì Manager luôn bắt đầu từ tổng quan, đây là màn hình tạo ấn tượng đầu tiên và lâu dài nhất. Đầu tư vào dashboard tốt = giá trị cảm nhận ngay lập tức.

2. **Progressive disclosure cho Executor** — Hiển thị task theo mức độ ưu tiên động (deadline gần, bị block, bình thường). Trên mobile: tối giản tối đa — chỉ cần biết "làm gì tiếp theo". Trên desktop: đủ context để xử lý phức tạp.

3. **Inline action pattern** — Can thiệp của Manager (nhắc việc, override, leo thang) nên xảy ra ngay trên dashboard mà không cần vào màn hình riêng — giảm friction, tăng tốc độ phản ứng.

## Core User Experience

### Defining Experience

Trải nghiệm cốt lõi của Workflow xoay quanh hai hành động trung tâm:

- **Executor đánh dấu task hoàn thành** — hành động xảy ra thường xuyên nhất, cần được tối giản tối đa. Flow lý tưởng: một tap/click để hoàn thành; ghi chú và đính kèm file là tùy chọn, không được cản trở hành động chính.

- **Thiết kế template quy trình** — hành động phải làm đúng. Một template sai sẽ tạo ra hàng chục instance sai. Process Designer cần công cụ rõ ràng, ít lỗi, và có thể preview trước khi publish.

### Platform Strategy

- **Web responsive** — một codebase hoạt động tốt trên cả desktop và mobile browser. Không cần native app.
- **Không cần offline mode** — sản phẩm vận hành trong môi trường có kết nối ổn định.
- **Tích hợp thông báo:** Email, Telegram — để Manager và Executor nhận alert mà không cần mở app.
- **Tích hợp dữ liệu:** Google Sheet — để xuất/nhập dữ liệu quy trình phục vụ báo cáo hoặc khởi tạo hàng loạt.

### Effortless Interactions

- **Task completion:** Một tap/click là đủ để đánh dấu hoàn thành. Ghi chú và file đính kèm luôn có sẵn nhưng không bắt buộc — không được xuất hiện như bước bắt buộc trong flow.
- **Dashboard scan:** Manager nhìn qua dashboard trong vài giây và biết ngay đâu cần chú ý — không cần đọc từng dòng hay click vào từng quy trình.
- **Thông báo có thể hành động:** Notification từ Email/Telegram dẫn thẳng đến đúng bước cần xử lý, không phải trang chủ.

### Critical Success Moments

Ba khoảnh khắc người dùng nhận ra sản phẩm vượt trội hơn email/Excel:

1. **Lần đầu thấy dashboard** — Manager mở app lần đầu, thấy toàn bộ quy trình đang chạy với trạng thái rõ ràng. Không cần hỏi ai.
2. **Lần đầu nhận thông báo proactive** — Hệ thống chủ động báo khi có delay trước khi Manager cần đi hỏi. "Nó biết trước cả mình."
3. **Lần đầu can thiệp trong app** — Manager nhắc việc hoặc leo thang trực tiếp trong hệ thống, không cần mở email hay gọi điện.

### Experience Principles

1. **Completion first** — Mọi hành động thường xuyên phải hoàn thành trong ít bước nhất có thể. Optional info không được block primary action.
2. **Visibility without effort** — Thông tin quan trọng phải hiện ra, không cần người dùng tìm kiếm.
3. **Responsive first, not mobile afterthought** — Thiết kế cho màn hình nhỏ trước, mở rộng ra desktop — không phải ngược lại.
4. **Act where you see** — Manager thấy vấn đề ở đâu, can thiệp ngay ở đó. Không có "đi đến trang khác để xử lý".
5. **Integrations as extensions** — Email/Telegram/Google Sheet là cầu nối ra thế giới bên ngoài, không phải tính năng phụ. Notification dẫn thẳng đến action.

## Desired Emotional Response

### Primary Emotional Goals

**Manager:**
Cảm giác **kiểm soát chủ động** — không phải lo lắng hay đi săn thông tin. Mọi thứ kịp thời, rõ ràng, đầy đủ. Manager biết mình có thể tin tưởng vào những gì hệ thống hiển thị và hành động dựa trên đó mà không cần xác minh lại.

**Executor:**
Cảm giác **nhẹ nhõm và thoải mái** — hoàn thành được task là xong, không còn lo bị quên hay bỏ sót. Inbox rõ ràng, ưu tiên đúng, không bị áp lực bởi danh sách vô tận.

**Cả hai vai trò:**
Cảm giác **làm ít hơn nhưng đạt được nhiều hơn** — so với email và Excel, mọi thứ nhanh hơn, ít bước hơn, ít nhầm lẫn hơn. Đây là cảm xúc thúc đẩy người dùng giới thiệu sản phẩm cho đồng nghiệp.

### Emotional Journey Mapping

| Giai đoạn | Manager | Executor |
|---|---|---|
| Mở app lần đầu | Ngạc nhiên — thấy rõ toàn cảnh ngay lập tức | Nhẹ nhõm — biết mình cần làm gì |
| Trong lúc dùng | Tự tin — thông tin đủ để quyết định | Thoải mái — hoàn thành từng task một |
| Sau khi hoàn thành | Hài lòng — can thiệp được, không cần gọi điện | Thỏa mãn — danh sách rút ngắn |
| Khi có sự cố | Bình tĩnh — biết đúng vấn đề ở đâu | Rõ ràng — biết bước tiếp theo là gì |
| Quay lại dùng | Thói quen tự nhiên — không cần học lại | Quen thuộc — mọi thứ đúng chỗ |

### Micro-Emotions

| Cảm xúc muốn tạo ra | Cảm xúc cần tránh |
|---|---|
| Tự tin (Confidence) | Bối rối (Confusion) |
| Tin tưởng (Trust) | Lo ngại (Anxiety) |
| Hoàn thành (Accomplishment) | Thất vọng (Frustration) |
| Nhẹ nhõm (Relief) | **Bị giám sát (Surveillance)** ⚠️ |
| Hiệu quả (Efficiency) | Căng thẳng (Stress) |

### Design Implications

- **Kiểm soát → Transparency by design:** Dashboard hiển thị thông tin đủ để ra quyết định, không quá tải. Mọi con số đều có thể truy vết được.

- **Nhẹ nhõm → Inbox rõ ràng:** Executor thấy đúng task cần làm, đúng thứ tự ưu tiên. Không có danh sách mơ hồ hay notification rác.

- **Làm ít hơn → Tối giản flow:** Mỗi action phổ biến hoàn thành trong ít bước nhất. Không có bước xác nhận thừa.

- **Tránh cảm giác bị giám sát → Framing là hỗ trợ, không phải kiểm tra:** Ngôn ngữ trong app dùng "nhắc việc" thay vì "cảnh báo vi phạm". Thông báo delay nên frame là "cần hỗ trợ không?" chứ không phải "bạn đang trễ". Executor không thấy Manager đang "xem" mình — họ thấy mình đang được hỗ trợ hoàn thành công việc.

### Emotional Design Principles

1. **Inform, don't alarm** — Thông tin quan trọng được trình bày bình tĩnh, rõ ràng. Chỉ dùng màu đỏ/cảnh báo khi thực sự cần hành động ngay.

2. **Support, don't surveil** — Mọi tính năng tracking phải cảm thấy như công cụ hỗ trợ, không phải công cụ kiểm soát. Executor là người được giúp đỡ, không phải đối tượng bị theo dõi.

3. **Completion feels good** — Mỗi lần đánh dấu hoàn thành nên có phản hồi rõ ràng (visual, animation nhẹ) để tạo cảm giác accomplishment thực sự.

4. **Trust through consistency** — Thông tin hiển thị luôn nhất quán và chính xác. Người dùng không bao giờ phải tự hỏi "liệu cái này có đúng không?"

## UX Pattern Analysis & Inspiration

### Inspiring Products Analysis

**Trello — Visual board**
Kanban columns cho phép nhìn trạng thái tổng thể bằng mắt, không cần đọc. Bài học: Visual grouping theo trạng thái thay vì danh sách phẳng.

**Jira — Filter mạnh**
Filter đa chiều (người, deadline, trạng thái) giúp tìm đúng item cần xử lý trong hàng trăm item. Bài học: Manager cần lọc nhanh theo tình huống cụ thể.

**Notion — Flexible views**
Cùng dữ liệu xem được theo nhiều dạng (table, board, list). Bài học: Không ép người dùng vào một cách xem duy nhất.

**N8N — Node-based workflow design**
Visual editor kéo thả, nhìn thấy toàn bộ flow trước khi chạy. Bài học: Template Builder nên có visual representation, không chỉ là form điền tuần tự.

**Grab — Actionable real-time notification**
Thông báo chủ động, đúng thời điểm, có thể hành động ngay trong notification. Bài học: Alert delay phải đi kèm nút hành động — không chỉ thông báo suông.

### Transferable UX Patterns

**Navigation & Layout:**
- **Status traffic light** (xanh/vàng/đỏ) — phân biệt ngay "bình thường / cần chú ý / cần can thiệp" trên Manager Dashboard
- **Sticky context** — khi xem chi tiết quy trình, luôn thấy context tổng thể ở header

**Interaction Patterns:**
- **Optimistic UI** — đánh dấu task hoàn thành cập nhật ngay, không chờ server confirm
- **Progressive disclosure** — form hoàn thành task hiện ghi chú/file đính kèm chỉ khi người dùng muốn, không phải mặc định
- **Actionable notification** — mọi alert đều có ít nhất một nút hành động inline (nhắc việc, xem chi tiết, leo thang)

**Visual Patterns:**
- **Inbox zero metaphor** — Executor inbox rút ngắn khi hoàn thành, tạo cảm giác accomplishment
- **Visual flow representation** — Template Builder dùng visual nodes thay vì form text thuần túy

### Anti-Patterns to Avoid

| Anti-pattern | Lý do tránh |
|---|---|
| Modal confirmation thừa | Friction không cần thiết cho action thường xuyên |
| Notification chỉ hiện số | "99+" không nói lên *loại* vấn đề gì |
| Flat list không hierarchy | Buộc Manager phải đọc từng dòng thay vì scan |
| Jira-style complexity upfront | Quá nhiều option ngay từ đầu gây cognitive overload |
| Activity log granular cho Executor | Hiển thị từng hành động nhỏ tạo cảm giác bị giám sát |

### Design Inspiration Strategy

**Áp dụng trực tiếp:**
- Traffic light status system từ dashboard patterns → Manager Dashboard
- Optimistic UI từ mobile app patterns → Task completion flow
- Actionable notification từ Grab → Alert system

**Điều chỉnh cho phù hợp:**
- Kanban visual từ Trello → Đơn giản hóa, chỉ 3 trạng thái thay vì customizable columns (phù hợp user skill level: intermediate)
- Node-based design từ N8N → Giữ visual metaphor nhưng đơn giản hơn, không cần drag-and-drop phức tạp ở MVP

**Tránh hoàn toàn:**
- Complexity model của Jira (quá nhiều config, quá nhiều view)
- Granular activity tracking kiểu surveillance

## Design System Foundation

### Design System Choice

**Shadcn/ui + Tailwind CSS**

Kết hợp giữa component library copy-paste (Shadcn/ui) và utility-first CSS framework (Tailwind CSS). Không phải dependency đen hộp — developer sở hữu hoàn toàn từng component.

### Rationale for Selection

- **Developer kiêm UI:** Shadcn/ui cung cấp component đọc được, chỉnh được trực tiếp trong codebase. Không cần hiểu internals của một library phức tạp.
- **Brand xây dựng cùng lúc:** Tailwind design tokens (màu, spacing, typography, border-radius) là nơi duy nhất định nghĩa brand — nhất quán tự động trên toàn app.
- **Visual độc đáo:** Shadcn/ui bắt đầu từ neutral, không mang "look" của Material hay Ant Design. Visual của workflow do brand tokens quyết định, không phải library.
- **Đủ component cho B2B SaaS:** Table, form, dialog, dropdown, tooltip, badge, tabs — đủ để xây Manager Dashboard và Executor Inbox phức tạp.

### Implementation Approach

1. **Khởi tạo:** Cài Tailwind CSS + Shadcn/ui CLI, định nghĩa design tokens ngay từ đầu (màu primary, neutral, danger, warning, success).
2. **Component strategy:** Dùng Shadcn/ui làm base, override style qua Tailwind classes. Không fork component trừ khi thực sự cần.
3. **Brand-first tokens:** Định nghĩa bảng màu và typography trước khi viết UI đầu tiên — đảm bảo nhất quán từ ngày 1.

### Customization Strategy

- **Màu sắc:** Định nghĩa semantic color system (primary, muted, destructive, warning) thay vì hard-code hex. Dễ thay đổi toàn bộ brand sau này.
- **Typography:** Chọn 1 font chính (gợi ý: Inter hoặc Be Vietnam Pro cho thị trường Việt Nam) — readable, professional, phù hợp B2B.
- **Component overrides:** Chỉ tùy chỉnh khi cần thiết cho UX pattern đặc thù (ví dụ: task completion flow, status badge traffic light).
- **Dark mode:** Shadcn/ui hỗ trợ sẵn — không cần implement từ đầu, có thể bật sau nếu cần.
