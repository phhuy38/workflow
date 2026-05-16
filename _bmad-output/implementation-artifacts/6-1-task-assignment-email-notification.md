# Story 6.1: Task Assignment Email Notification (FR27)

Status: done

## Story

As an Executor,
I want to receive an email when a new task is assigned to me,
So that I'm immediately aware of new responsibilities even when I'm not actively using the system.

## Acceptance Criteria
1. **Given** một `step_execution` vừa được giao cho Executor (khi tạo mới với status 'pending' từ Story 3.1 hoặc 3.3), **When** Event được fire và Listener xử lý, **Then** email được đưa vào queue và gửi đến Executor trong vòng 60 giây (NFR2).
2. **Given** email notification được gửi, **When** Executor nhận được email, **Then** email chứa: tên task, tên quy trình, deadline, và **deep link trực tiếp đến trang task detail** (không phải trang chủ) — UX-DR6.
3. **Given** email notification, **When** Executor đọc nội dung, **Then** ngôn ngữ mang tính thông tin và hỗ trợ: "Bạn có một task mới cần xử lý" (không phải "Bạn được yêu cầu..." kiểu mệnh lệnh) — UX-DR9.
4. **Given** SMTP chưa được cấu hình hoặc lỗi kết nối, **When** system cố gửi email qua queue, **Then** lỗi được catch không làm crash request chính của user.
5. **Given** email gửi thất bại (SMTP error) trong queue, **When** queue retry, **Then** system thử lại tối đa 3 lần với exponential backoff trước khi đưa vào failed_jobs.

## Technical Requirements
- [x] **Backend**: 
  - [x] Create a new Mailable `TaskAssignedMail`.
  - [x] Create a new Listener `SendTaskAssignedNotification` listening to `StepExecutionUpdated` or a dedicated `TaskAssigned` event. (Since `StepExecutionUpdated` fires frequently, check if `$event->step->wasChanged('assigned_to')` or just listen to Eloquent `created` event on `StepExecution` where `assigned_to` is not null).
  - [x] Use Laravel Queues (`ShouldQueue`) for the listener. Configure `tries = 3` and `backoff = [10, 30, 60]`.
  - [x] Handle exceptions gracefully inside the listener so the queue worker logs it but uses the default failed job logic.
- [x] **Frontend / Emails**: 
  - [x] Use Laravel Blade markdown mail templates.
  - [x] Follow the semantic colors and styling (incorporate UX-DR9 supportive framing).
  - [x] Generate the deep link using `route('process-instances.show', $instance)`.

## Architecture Compliance
- [x] Use the `notifications` queue as defined in ADR-003.
- [x] Always use Queues for emails; never send synchronously.
- [x] Use `php artisan make:mail` and `php artisan make:listener`.

## Previous Story Intelligence
- [x] In Epic 5, we relied heavily on `StepExecutionUpdated` for real-time frontend updates. Be careful when adding email listeners to the same event to avoid spamming emails on status changes. It is highly recommended to use Eloquent's `created` hook or dispatch a specific `TaskAssigned` event only when the assignee is actually resolved and assigned.

## File Structure Requirements
- `app/Mail/TaskAssignedMail.php`
- `resources/views/emails/task-assigned.blade.php`
- `app/Listeners/SendTaskAssignedNotification.php`
- `app/Providers/EventServiceProvider.php` (if registering events manually)

## Testing Requirements
- [x] Write a Pest feature test (e.g. `tests/Feature/Notifications/TaskAssignedTest.php`) using `Mail::fake()` and `Queue::fake()` to assert the mail is queued with the correct recipient and content when a step is assigned.

---

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
- ✅ Created `TaskAssigned` event to be explicitly fired when a step gets assigned.
- ✅ Created `TaskAssignedMail` Mailable.
- ✅ Created `SendTaskAssignedNotification` Listener with `ShouldQueue`, `tries = 3`, and exponential backoff configuration.
- ✅ Created Blade markdown template for `emails.task-assigned` with friendly UX-DR9 language.
- ✅ Dispatched `TaskAssigned` event from `LaunchProcessInstance` and `AdvanceProcessInstance` upon successful assignment.
- ✅ Written tests using `Mail::fake` and `Event::fake` in `TaskAssignedTest.php`.

### File List
- app/Events/TaskAssigned.php (new)
- app/Mail/TaskAssignedMail.php (new)
- app/Listeners/SendTaskAssignedNotification.php (new)
- resources/views/emails/task-assigned.blade.php (new)
- app/Actions/Process/LaunchProcessInstance.php (update)
- app/Actions/Process/AdvanceProcessInstance.php (update)
- tests/Feature/Notifications/TaskAssignedTest.php (new)

### Change Log
- Task Assignment notification implementation added via queue system.

### Review Findings

- [x] [Review][Patch] State Machine Violation (Queue Delay) — Since the notification is queued, the user might complete the task before the email is sent. Add a check `if ($event->step->status->getValue() !== 'pending') return;` in the listener.
- [x] [Review][Patch] Lazy Loading Crash Risk — In `SendTaskAssignedNotification`, `$event->step->assignee` and `$event->step->instance` trigger lazy loading. If `preventLazyLoading()` is enabled, the queue worker crashes. Add `$event->step->loadMissing(['assignee', 'instance']);`.
- [x] [Review][Patch] ModelNotFoundException on Deletion — Add `public bool $deleteWhenMissingModels = true;` to the Listener to safely discard the job if the step is hard-deleted before the queue runs.
- [x] [Review][Patch] Missing Null Checks — In `TaskAssignedMail`, use null-safe operator `$this->step->instance?->name` to prevent fatal errors if the instance relationship fails to load or is deleted.
- [x] [Review][Patch] Loss of Exception Stack Trace — In `SendTaskAssignedNotification`, the exception is logged using string concatenation. Use context array `['exception' => $e]` instead to preserve the stack trace.

---
