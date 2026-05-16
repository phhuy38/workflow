# Story 7.1: Beneficiary Auto-Account Creation (FR36)

Status: done

## Story

As a system,
I want to automatically create a Beneficiary account and send login credentials when the designated step is completed,
So that beneficiaries can access the system at exactly the right moment in the process.

## Acceptance Criteria
1. **Given** một step_execution được cấu hình với `config_data['is_account_creation_step'] = true` được hoàn thành, **When** `CompleteStep` action xử lý, **Then** tài khoản Beneficiary được tạo tự động với: email từ instance `context_data['beneficiary_email']`, role 'beneficiary', password ngẫu nhiên an toàn, **And** email chào mừng được gửi với thông tin đăng nhập và deep link vào hệ thống.
2. **Given** tài khoản Beneficiary vừa được tạo, **When** Beneficiary dùng link trong email để đăng nhập lần đầu, **Then** họ được yêu cầu đổi password ngay lập tức.
3. **Given** email beneficiary đã tồn tại trong hệ thống (tài khoản cũ), **When** bước 'tạo tài khoản' hoàn thành với email đó, **Then** system không tạo tài khoản mới mà liên kết instance với tài khoản đã tồn tại (nếu có trường `created_for`), **And** Beneficiary nhận email thông báo về quy trình mới liên quan đến họ.
4. **Given** tạo tài khoản thất bại (ví dụ: thiếu email, email không hợp lệ), **When** action gặp lỗi, **Then** lỗi được log và Manager được notify ngay, **And** bước vẫn được đánh dấu completed để không block quy trình.

## Technical Requirements
- [x] **Backend / Database**:
  - [x] Add `requires_password_reset` (boolean, default false) to `users` table via migration.
  - [x] Add `created_for` (unsignedBigInteger, nullable) foreign key to `users` on `process_instances` via migration.
- [x] **Backend / Action**:
  - [x] In `App\Actions\Process\CompleteStep`, dispatch a new event `StepCompleted` (or use existing Eloquent event) and create a Listener `HandleBeneficiaryAccountCreation`.
  - [x] The listener checks `$step->step_snapshot_data['config_data']['is_account_creation_step'] ?? false`.
  - [x] If true, check `$instance->context_data['beneficiary_email']`. Create the user if it doesn't exist, assign role 'beneficiary', set `requires_password_reset = true`.
  - [x] Update `$instance->update(['created_for' => $user->id])`.
  - [x] Send `BeneficiaryAccountCreatedMail` (or `BeneficiaryExistingAccountMail` if user exists).
- [x] **Backend / Middleware**:
  - [x] Create `ForcePasswordReset` middleware. Apply it to web routes for authenticated users. If `requires_password_reset` is true, redirect to a specific password reset view.
- [x] **Frontend**:
  - [x] Create a simple Vue view for password reset: `resources/js/pages/Auth/ForceResetPassword.vue`.
  - [x] Route to handle the force reset submission.

## Architecture Compliance
- [x] Use the `notifications` queue for the emails.
- [x] Handle exceptions within the listener so `CompleteStep` is not aborted.
- [x] Follow BMad Fortify / Security guidelines for password hashing.

## File Structure Requirements
- `database/migrations/xxxx_add_beneficiary_columns.php`
- `app/Listeners/HandleBeneficiaryAccountCreation.php`
- `app/Http/Middleware/ForcePasswordReset.php`
- `app/Mail/BeneficiaryAccountCreatedMail.php`
- `app/Mail/BeneficiaryExistingAccountMail.php`
- `resources/js/pages/Auth/ForceResetPassword.vue`

## Testing Requirements
- [x] Write a Pest feature test `tests/Feature/Process/BeneficiaryAccountCreationTest.php`.
- [x] Test new user creation vs existing user handling.
- [x] Test the `ForcePasswordReset` middleware protection.

---

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
- ✅ Added `requires_password_reset` to `users` table. `created_for` already existed in `process_instances`.
- ✅ Dispatched `StepCompleted` in `CompleteStep` action.
- ✅ Implemented `HandleBeneficiaryAccountCreation` listener with complete auto-account generation logic and queue safety.
- ✅ Implemented `ForcePasswordReset` middleware to intercept authenticated users who need a password change and appended it to the `web` group.
- ✅ Implemented `Auth/ForceResetPassword.vue` with form handling.
- ✅ Created `BeneficiaryAccountCreatedMail` and `BeneficiaryExistingAccountMail` mailables with Blade markdown templates.
- ✅ Tests in `BeneficiaryAccountCreationTest.php` cover new user creation, existing user linking, and middleware redirection successfully.

### File List
- database/migrations/2026_05_16_154021_add_beneficiary_columns.php (new)
- app/Models/User.php (update)
- app/Actions/Process/CompleteStep.php (update)
- app/Events/StepCompleted.php (new)
- app/Listeners/HandleBeneficiaryAccountCreation.php (new)
- app/Mail/BeneficiaryAccountCreatedMail.php (new)
- app/Mail/BeneficiaryExistingAccountMail.php (new)
- resources/views/emails/beneficiary-account-created.blade.php (new)
- resources/views/emails/beneficiary-existing-account.blade.php (new)
- app/Http/Middleware/ForcePasswordReset.php (new)
- bootstrap/app.php (update)
- routes/web.php (update)
- resources/js/pages/Auth/ForceResetPassword.vue (new)
- tests/Feature/Process/BeneficiaryAccountCreationTest.php (new)

### Change Log
- Automated account creation for beneficiaries triggered by completing specific steps.
- Introduced forced password reset workflow for auto-generated accounts.

### Review Findings

- [x] [Review][Patch] Session Invalidation on Password Reset — When the password is updated via `$request->user()->update(...)`, the underlying database hash changes. Laravel's session might invalidate the user. Add `\Illuminate\Support\Facades\Auth::login($request->user());` after update to ensure session persists.
- [x] [Review][Patch] Flawed Queue Retry (Password Loss) — If the queued listener fails *after* `User::create()` but *during* email sending, the retry will see the user exists and send the "existing account" email, permanently losing the generated password. Fix by using `firstOrCreate` and relying on `wasRecentlyCreated`, though retry still needs careful handling (e.g. check if `last_login_at` is null and `requires_password_reset` is true to regenerate and resend password).
- [x] [Review][Patch] Missing State Validation in CompleteStep — `CompleteStep` does not verify if `$step` is *already* completed before executing. Add an idempotency check `if ($step->status->getValue() === 'completed') return;`.
- [x] [Review][Patch] API/JSON Request Handling in Middleware — `ForcePasswordReset` middleware intercepts all requests, returning a 302 redirect for API requests. Add `$request->expectsJson()` check to return a 403 JSON response instead.
- [x] [Review][Patch] Missing Manager Notification on Failure (AC4) — If account creation fails, the Manager is not notified. Send an email alert to the Manager (`$step->instance->creator`) in the `catch` block.
- [x] [Review][Dismiss] Plaintext Passwords in Queue Payloads — Generated passwords are serialized in the queue payload. This is an accepted risk for the MVP's auto-generated account flow.

---
