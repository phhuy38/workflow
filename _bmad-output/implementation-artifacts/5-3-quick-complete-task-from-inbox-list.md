# Story 5.3: Quick Complete Task from Inbox List (FR23)

Status: done

## Story

As an Executor,
I want to complete a task directly from the inbox list without opening the detail page,
So that routine completions take a single action and my inbox shrinks satisfyingly.

## Acceptance Criteria
1. Given Executor có task ở trạng thái 'in_progress' trong Inbox, When họ click nút "Hoàn thành" ngay trên row trong danh sách, Then task được đánh dấu completed ngay lập tức.
2. Given task được hoàn thành từ Inbox, When completion được lưu, Then task row biến mất khỏi danh sách với animation trượt ra (inbox zero metaphor), And nếu đây là task cuối cùng, empty state "Tốt lắm! 🎉" hiện ra.
3. Given Executor muốn thêm ghi chú khi hoàn thành từ list view, When họ click mũi tên mở rộng trên row (progressive disclosure), Then inline text input cho ghi chú hiện ra ngay trong row, không rời khỏi danh sách.
4. Given Executor có task ở trạng thái 'pending' (chưa acknowledge), When họ click "Hoàn thành" từ list, Then system tự động acknowledge rồi complete trong một hành động (không yêu cầu 2 bước riêng).
5. Given Executor dùng mobile, When họ swipe left trên một task row, Then nút "Hoàn thành" hiện ra (swipe-to-complete gesture).

## Technical Requirements
- [x] **Backend**: Reuse `CompleteStep` action. If a step is 'pending', the controller should either call `AcknowledgeStep` action before `CompleteStep` or `CompleteStep` should handle pending steps implicitly. (Since `CompleteStep` currently checks `status->getValue() === 'in_progress'`, we must call `AcknowledgeStep` first, or update the logic).
- [x] **Frontend**: Update `resources/js/pages/Inbox/Index.vue` (created in 5.1).
- [x] **UX**:
  - [x] Implement Vue transition groups (`<TransitionGroup>`) for list row removal (inbox zero metaphor).
  - [x] Implement progressive disclosure for completion notes inline (expandable row).
  - [x] Implement touch events / swipe left to reveal action buttons on mobile. You can use `@vueuse/core` (`useSwipe` or similar) or simple CSS scroll snap.

## Architecture Compliance
- [x] Use Inertia.js `router.post` with `preserveScroll: true`.
- [x] Forms must use Inertia `useForm` (instantiate them at the component level to preserve reactivity).
- [x] Follow BMad guidelines on using Tailwind variants and semantic colors.

## Previous Story Intelligence
- [x] In Story 5.2, we fixed an issue where `useForm` was instantiated *inside* the submit function, breaking reactivity. For the inbox list, use a component-level `useForm` with dynamic data injection to avoid validation failures.
- [x] Event listeners for `StepExecutionUpdated` must use debounced reloads to prevent race conditions.

## File Structure Requirements
- `resources/js/pages/Inbox/Index.vue`
- `app/Http/Controllers/StepExecutionController.php` (if modifications are needed for complete endpoint to auto-acknowledge)

## Testing Requirements
- [x] Write Pest feature test in `tests/Feature/InboxTest.php` or `tests/Feature/Process/StepExecutionTest.php` to verify quick completion and auto-acknowledge.
- [x] Ensure the endpoint validates permissions properly.

---

## Dev Agent Record

### Agent Model Used
Gemini 2.0 Flash

### Debug Log References
N/A

### Completion Notes List
- ✅ Modified `StepExecutionController@complete` to auto-acknowledge if the status is pending before continuing.
- ✅ Updated `Inbox/Index.vue` to use `<TransitionGroup>` for animated task removal.
- ✅ Implemented `useForm` globally inside setup with dynamic IDs mapped via `completingTaskId` and `notesInput`.
- ✅ Implemented progressive disclosure logic for completion notes inline using `expandedNoteTaskId`.
- ✅ Implemented basic swipe-left detection (`touchmove`) on mobile that translates the card by `-translate-x-24` and reveals an underlying absolute positioned "Hoàn thành" green block.
- ✅ Added Pest test for quick complete auto-acknowledgement in `InboxTest.php`.

### File List
- app/Http/Controllers/StepExecutionController.php (update)
- resources/js/pages/Inbox/Index.vue (update)
- tests/Feature/InboxTest.php (update)

### Change Log
- Refactored Inbox Index.vue to support quick completion with transitions.
- Backend controller auto-acknowledge feature added.

### Review Findings

- [x] [Review][Patch] Stale Model State in Auto-Acknowledge — In `StepExecutionController@complete`, `$stepExecution->refresh()` should be called after `AcknowledgeStep` to ensure the subsequent Policy check receives the updated state.
- [x] [Review][Patch] Permanent Swipe Lockout — `swipedTaskId` is never reset to null in the `onSuccess` callback of `quickComplete()`, preventing swiping on other tasks after one is completed.
- [x] [Review][Patch] Unprotected Swipe Background & Race Condition — The green swipe background lacks `pointer-events-none` during submission, and `quickComplete` lacks a `completeForm.processing` guard, allowing multiple submissions.
- [x] [Review][Patch] Touch Scroll Conflicts — Horizontal swiping on mobile triggers native browser back/forward navigation. Need `touch-action: pan-y` CSS on the task card.
- [x] [Review][Patch] Memory Leak / Dangling State on WebSockets — If a task is completed remotely and disappears, `expandedNoteTaskId.value` is not cleared, leaving orphaned reactive state.
- [x] [Review][Patch] Missing slide-out animation for the last task — The `v-if="tasks.length === 0"` wrapper destroys the `<TransitionGroup>` instantly, preventing the exit animation from playing for the final task.

---
