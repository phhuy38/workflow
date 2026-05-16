# Story 6.2: Deadline & Unacknowledged Step Alerts (FR28, FR29, FR30)

Status: done

## Story

As a Manager and Executor,
I want to receive proactive alerts when steps are at risk of missing deadlines,
So that I can take action before delays become problems — not after.

## Acceptance Criteria
1. **Given** `CheckApproachingDeadlines` job chạy mỗi phút, **When** job phát hiện bước chưa được acknowledge sau 1 giờ kể từ khi giao, **Then** email cảnh báo gửi đến Manager của instance đó (FR28), **And** email nêu rõ: tên bước, tên executor, thời gian đã chờ, deep link đến bước đó.
2. **Given** job phát hiện bước còn ≤ 30% thời gian deadline hoặc đã vượt deadline, **When** điều kiện được thỏa mãn, **Then** email gửi đến Manager (FR29) **và** Executor (FR30) trong cùng chu kỳ job.
3. **Given** cảnh báo deadline đã được gửi cho một bước, **When** job chạy lần tiếp theo, **Then** cảnh báo **không** gửi lại cho cùng bước đó (`deadline_notified_at` đã được set).
4. **Given** email cảnh báo deadline, **When** Manager hoặc Executor nhận được, **Then** ngôn ngữ theo hướng hỗ trợ: "Bước này cần được xử lý sớm để đảm bảo tiến độ" thay vì "CẢNH BÁO: Trễ hạn!" — UX-DR9, **And** email có deep link đến bước cụ thể với nút hành động inline (nhắc việc / xem chi tiết).
5. **Given** `CheckStateConsistency` job chạy mỗi giờ, **When** phát hiện instance ở trạng thái 'running' nhưng tất cả bước đã completed, **Then** instance được tự động chuyển sang 'completed' và Admin được notify.

## Technical Requirements
- [x] **Backend / Database**:
  - [x] Create a migration to add `unacknowledged_notified_at` (timestamp, nullable) and `deadline_notified_at` (timestamp, nullable) to `step_executions` table.
- [x] **Backend / Jobs**:
  - [x] Create `App\Console\Commands\CheckApproachingDeadlines` (or a Job scheduled in `routes/console.php` every minute).
  - [x] Create `App\Console\Commands\CheckStateConsistency` (scheduled hourly).
- [x] **Backend / Mails**:
  - [x] Create Mailables: `UnacknowledgedStepAlertMail`, `ApproachingDeadlineAlertMail`, `StateConsistencyAlertMail`.
  - [x] The `CheckApproachingDeadlines` command queries steps where status is 'pending' and `created_at` < 1 hour ago and `unacknowledged_notified_at` is null. Sends `UnacknowledgedStepAlertMail` to the Manager (`instance.launchedBy`). Updates `unacknowledged_notified_at`.
  - [x] The same command queries steps where status is 'pending' or 'in_progress' and `deadline_at` is near (≤ 30% of original duration remaining) or passed, and `deadline_notified_at` is null. Sends `ApproachingDeadlineAlertMail` to Executor and Manager. Updates `deadline_notified_at`.
- [x] **Backend / State Consistency**:
  - [x] The `CheckStateConsistency` command queries `process_instances` where status is `running` but does not have any `step_executions` with status `pending`, `in_progress`, or `escalated`. Then transitions the instance to `completed` via `AdvanceProcessInstance` or manually updating status and logs.

## Architecture Compliance
- [x] Use the Scheduler in `routes/console.php` (Laravel 11 standard) to schedule the commands/jobs.
- [x] Use the `notifications` queue for sending all Mailable alerts.
- [x] Follow UX-DR9 for email text phrasing (Supportive, not supervising).

## Previous Story Intelligence
- [x] In Story 6.1, we created Mailable templates. Reuse `x-mail::message` and `x-mail::button` components for consistent styling.
- [x] Ensure the `url` param in Mailables links directly to `route('process-instances.show', $instance)` since Executors and Managers share this view (implemented in Epic 4 and 5).

## File Structure Requirements
- `database/migrations/xxxx_xx_xx_add_notification_timestamps_to_step_executions_table.php`
- `app/Console/Commands/CheckApproachingDeadlines.php`
- `app/Console/Commands/CheckStateConsistency.php`
- `routes/console.php` (update scheduling)
- `app/Mail/UnacknowledgedStepAlertMail.php`
- `app/Mail/ApproachingDeadlineAlertMail.php`
- `app/Mail/StateConsistencyAlertMail.php`
- Blade templates in `resources/views/emails/`

## Testing Requirements
- [x] Write Pest tests for the console commands (e.g. `tests/Feature/Console/CheckApproachingDeadlinesTest.php`) using `Mail::fake()`.
- [x] Fast forward time using `test()->travelTo()` to verify that the emails are triggered only after the specific threshold.

---

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
- ✅ Added `unacknowledged_notified_at` to `step_executions` table via migration (the other column already existed from a prior story).
- ✅ Added `name` to `StepExecution` fillable array to resolve testing errors.
- ✅ Implemented `CheckApproachingDeadlines` and `CheckStateConsistency` commands and scheduled them in `routes/console.php`.
- ✅ Created three corresponding Mailables with Blade markdown templates following UX-DR9 (Supportive language).
- ✅ Added `CheckApproachingDeadlinesTest.php` to verify job behavior using `travelTo()` and `Mail::fake()`. All tests passed.

### File List
- database/migrations/2026_05_16_143410_add_notification_timestamps_to_step_executions_table.php (new)
- app/Console/Commands/CheckApproachingDeadlines.php (new)
- app/Console/Commands/CheckStateConsistency.php (new)
- routes/console.php (update)
- app/Mail/UnacknowledgedStepAlertMail.php (new)
- app/Mail/ApproachingDeadlineAlertMail.php (new)
- app/Mail/StateConsistencyAlertMail.php (new)
- resources/views/emails/unacknowledged-step-alert.blade.php (new)
- resources/views/emails/approaching-deadline-alert.blade.php (new)
- resources/views/emails/state-consistency-alert.blade.php (new)
- app/Models/StepExecution.php (update)
- tests/Feature/Console/CheckApproachingDeadlinesTest.php (new)

### Change Log
- Implemented proactive deadline alerts and unacknowledged step alerts via scheduled console commands.
- Implemented auto-close consistency check for orphaned running instances.

### Review Findings

- [x] [Review][Patch] Infinite Loop on Missing Manager — In `CheckApproachingDeadlines`, the flag update `$step->update(['unacknowledged_notified_at' => now()])` is inside the `if ($manager && $manager->email)` condition. If the manager has no email, it loops infinitely. Move the update outside the if.
- [x] [Review][Patch] Premature Completion of Zero-Step Instances — In `CheckStateConsistency`, `whereDoesntHave('stepExecutions', condition)` matches instances with zero steps (e.g., during creation). Add `has('stepExecutions')` to prevent premature auto-close.
- [x] [Review][Patch] Synchronous Email Storms — In commands, `Mail::to()->send()` is used with mailables that don't implement `ShouldQueue`. Use `Mail::to()->queue()` or `SendQueuedMailable` to prevent blocking and SMTP timeouts.
- [x] [Review][Patch] Memory Exhaustion Risk — Commands use `->get()` which loads all matching records into memory. Use `->chunk()` or `->cursor()`.
- [x] [Review][Patch] Brittle Loop Executions — In both commands, a single email failure or exception inside the `foreach` loop crashes the entire command. Wrap the inner loop logic in a `try-catch`.

---
