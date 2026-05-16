# Story 6.3: Pre-account Beneficiary Email Notification (FR31)

Status: done

## Story

As a system,
I want to send informational emails to beneficiaries who don't yet have an account,
So that they are kept informed about the process being run on their behalf.

## Acceptance Criteria
1. **Given** Manager launch instance và cung cấp email beneficiary (thông qua `context_data['beneficiary_email']`), **When** instance được khởi chạy, **Then** email thông báo được đưa vào queue và gửi đến email beneficiary với: tên quy trình, người khởi chạy, và thông báo trạng thái.
2. **Given** beneficiary chưa có tài khoản, **When** email được gửi, **Then** email không chứa link đăng nhập vào hệ thống, **And** email giải thích rõ rằng họ sẽ nhận được thông tin đăng nhập chính thức khi quy trình tiến đến bước cấp tài khoản.
3. **Given** email beneficiary gửi thất bại (ví dụ: lỗi SMTP hoặc địa chỉ không tồn tại), **When** hệ thống cố gửi email, **Then** lỗi được catch và log cẩn thận (không làm crash request tạo instance của Manager).

## Technical Requirements
- [x] **Backend / Mail**:
  - [x] Create a new Mailable `PreAccountBeneficiaryWelcomeMail`.
  - [x] Create a new Listener `SendBeneficiaryWelcomeEmail` listening to `ProcessLaunched` event.
  - [x] The listener should implement `ShouldQueue` and use the `notifications` queue.
- [x] **Backend / Logic**:
  - [x] Inside the listener, check if the `ProcessInstance` has `context_data['beneficiary_email']`. If it does, send the `PreAccountBeneficiaryWelcomeMail`.
- [x] **Frontend / Emails**:
  - [x] Create Blade markdown template `resources/views/emails/beneficiary-welcome.blade.php`.
  - [x] Ensure the copy is friendly, informative, and explicitly states that an account will be provided later. No login links should be included.

## Architecture Compliance
- [x] Use the `notifications` queue.
- [x] Handle exceptions within the listener so failed emails do not disrupt the system.
- [x] Follow UX-DR9 for supportive and clear language.

## Previous Story Intelligence
- [x] In Story 6.1, we created similar queued notification listeners. Follow the pattern of `try-catch` inside the listener and utilizing `ShouldQueue`.
- [x] The `ProcessLaunched` event was created in Epic 3 (Story 3.1) and is already dispatched when a process starts.

## File Structure Requirements
- `app/Mail/PreAccountBeneficiaryWelcomeMail.php`
- `app/Listeners/SendBeneficiaryWelcomeEmail.php`
- `resources/views/emails/beneficiary-welcome.blade.php`
- `app/Providers/EventServiceProvider.php` (if needed for registration, or let auto-discovery handle it)

## Testing Requirements
- [x] Write a Pest feature test (e.g. `tests/Feature/Notifications/BeneficiaryWelcomeTest.php`) using `Mail::fake()` and `Event::fake()` (or just `Mail::fake()` testing the listener directly).
- [x] Verify that the mail is queued if `context_data['beneficiary_email']` is present.
- [x] Verify no mail is queued if the email is absent.

---

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
- ✅ Implemented `PreAccountBeneficiaryWelcomeMail` mailable with appropriate markdown view without login links.
- ✅ Implemented `SendBeneficiaryWelcomeEmail` listener on the `notifications` queue with backoff logic and a safety try-catch block.
- ✅ Linked listener explicitly to `ProcessLaunched` event to check for `context_data['beneficiary_email']`.
- ✅ Written `BeneficiaryWelcomeTest.php` passing all assertions for both email presence and absence.

### File List
- app/Mail/PreAccountBeneficiaryWelcomeMail.php (new)
- app/Listeners/SendBeneficiaryWelcomeEmail.php (new)
- resources/views/emails/beneficiary-welcome.blade.php (new)
- tests/Feature/Notifications/BeneficiaryWelcomeTest.php (new)

### Change Log
- Pre-account welcome email added for Beneficiaries when processes are launched.

### Review Findings

- [x] [Review][Patch] Defeated Retry Logic & Double Queueing — The listener is `ShouldQueue` but calls `Mail::queue()` instead of `Mail::send()`. Also, the `try-catch` block catches the exception without rethrowing, which defeats the `tries = 3` backoff configuration since the job completes "successfully".
- [x] [Review][Patch] Potential Array-to-String Conversion — In `SendBeneficiaryWelcomeEmail`, if `$beneficiaryEmail` is passed as an array, the `Log::error` string interpolation will crash ungracefully.
- [x] [Review][Patch] Null `context_data` Warning — Attempting to access `$event->instance->context_data['beneficiary_email']` when `context_data` is null throws a PHP warning.
- [x] [Review][Patch] Email Subject Formatting — Instance names with newlines or excessive length might break email headers. Clean/truncate the instance name for the subject.

---
