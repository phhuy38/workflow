# Story 2.4: Publish & Unpublish Template (FR4)

**Status:** review
**Epic:** Epic 2: Process Template Management
**Story Key:** 2-4-publish-unpublish-template
**Created:** 2026-05-14
**Updated:** 2026-05-14
**Author:** huyph (via Gemini CLI)

---

## 1. Goal & Context
As a Process Designer, I want to publish or unpublish a template, so that I can control which templates Managers can use to start new processes.

---

## 2. Requirements (Acceptance Criteria)

- [x] **[AC1] Publish Action:** Designer có thể click "Publish" từ trang chi tiết template.
- [x] **[AC2] Publish Validation:** Khi click Publish, hệ thống phải kiểm tra:
    - Template phải có ít nhất 1 bước.
    - Mọi bước phải có `assignee_type` và `duration_hours`.
- [x] **[AC3] Published State:** Khi publish thành công, `is_published` thành `true`, `published_at` được set thành thời gian hiện tại.
- [x] **[AC4] Unpublish Action:** Designer có thể click "Unpublish" cho template đang ở trạng thái Published.
- [x] **[AC5] Unpublish Effect:** Template chuyển về Draft (`is_published = false`). Managers không còn thấy template này trong danh sách để launch.
- [x] **[AC6] Manager View Filter:** Trang danh sách template dành cho Manager (hoặc khi Manager thực hiện launch) chỉ được hiển thị các template đang ở trạng thái `Published`.

---

## 3. Tasks / Subtasks

- [x] **Task 1: Backend Logic (Actions & Validation)**
    - [x] [RED] Create Pest feature tests for Publish/Unpublish (Validation, Auth, Success).
    - [x] [GREEN] Implement `PublishTemplate` Action with step validation logic.
    - [x] [GREEN] Implement `UnpublishTemplate` Action.
- [x] **Task 2: Controller & Routes**
    - [x] [GREEN] Add `publish` and `unpublish` methods to `ProcessTemplateController`.
    - [x] [GREEN] Define POST routes for publish/unpublish.
    - [x] [GREEN] Update `ProcessTemplatePolicy@publish` logic.
- [x] **Task 3: Frontend Integration**
    - [x] [GREEN] Update `Templates/Show.vue` with Publish/Unpublish buttons and validation error display.
    - [x] [GREEN] Update `Templates/Index.vue` to filter list for Managers (using `scopePublished`).
- [x] **Task 4: Final Verification**
    - [x] Run full test suite.
    - [x] Manual check of the Manager's filtered view.

---

## 4. Dev Notes (Architecture & Patterns)
- **Action Pattern**: Đã sử dụng `PublishTemplate` và `UnpublishTemplate` actions.
- **Validation**: Validation cụ thể được ném qua `ValidationException` và hiển thị ở frontend.
- **Filtering**: `ProcessTemplateController@index` sử dụng `scopePublished` cho users không có quyền `manage_templates`.

---

## 5. Dev Agent Record (Debug Log & Implementation Plan)

### Debug Log
- [2026-05-14] Initializing story implementation.
- [2026-05-14] Implemented backend actions and routes.
- [2026-05-14] Updated controller `index` and added `publish`/`unpublish` methods.
- [2026-05-14] Updated `Show.vue` with action buttons and state-dependent logic.
- [2026-05-14] Fixed legacy tests in `ProcessTemplateTest.php` to account for new Manager access requirements.
- [2026-05-14] Verified all ACs with full Pest suite (33 tests).

---

## 6. File List
- `_bmad-output/implementation-artifacts/2-4-publish-unpublish-template.md` (modified)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (modified)
- `app/Actions/Template/PublishTemplate.php` (created)
- `app/Actions/Template/UnpublishTemplate.php` (created)
- `app/Http/Controllers/ProcessTemplateController.php` (modified)
- `resources/js/pages/Templates/Show.vue` (modified)
- `routes/web.php` (modified)
- `tests/Feature/Template/ProcessTemplatePublishTest.php` (created)
- `tests/Feature/Template/ProcessTemplateTest.php` (modified)

---

## 7. Change Log
- [2026-05-14] Story initialized for development.
- [2026-05-14] Implementation complete, marked for review.

---

### Review Findings

- [x] [Review][Patch] Step loading safety [app/Actions/Template/PublishTemplate.php:12]
- [x] [Review][Patch] Bulk validation errors [app/Actions/Template/PublishTemplate.php:20]
- [x] [Review][Patch] Confirmation for Publish [resources/js/pages/Templates/Show.vue:46]
- [x] [Review][Defer] Activity log for publish [app/Models/ProcessTemplate.php:24] — deferred, pre-existing
- [x] [Review][Defer] Custom exception types [app/Actions/Template/PublishTemplate.php:14] — deferred, pre-existing

---

## 8. Status
- Current Status: done
- Completion: 100%
