# Story 2.2: Configure Template Steps with Visual Builder (FR2, UX-DR7)

Status: review
Date Created: 2026-04-17

## Story

As a Process Designer,
I want to add and configure steps in a template using a visual node builder,
So that I can clearly see the workflow structure while designing it.

## Acceptance Criteria

**AC1 — Visual step cards/nodes display:**
- Given: Process Designer trên trang chỉnh sửa template (Templates/Show.vue)
- When: Họ xem phần Steps
- Then: Các bước hiển thị dạng visual cards/nodes theo thứ tự tuần tự (không phải plain text list)
- And: Mỗi node hiển thị: số thứ tự (order), tên bước, người phụ trách (assignee description), deadline mặc định (duration_hours)
- And: Node layout dạng card với visual hierarchy (tên lớn, meta info nhỏ hơn)

**AC2 — Add new step:**
- Given: Process Designer click "Thêm bước" button trong template
- When: Họ điền: tên bước, mô tả, assignee type (user/role/department), assignee ID, duration_hours (giờ), is_required checkbox
- Then: Bước mới xuất hiện như node cuối cùng trong chuỗi (append to end)
- And: `duration_hours` mặc định là 24 nếu không điền
- And: Node mới có visual indicator "NEW" hoặc highlight khác biệt (được reset sau refresh)

**AC3 — Reorder steps (move up/down):**
- Given: Process Designer có template với 2+ bước
- When: Họ click nút "Lên" hoặc "Xuống" trên node
- Then: Thứ tự `order` của bước thay đổi
- And: Các bước xung quanh được renumber ngay lập tức (optimistic UI)
- And: Changes được persist vào database (gọi API reorder endpoint)
- And: Khác bước không bị ảnh hưởng (no full page refresh)

**AC4 — Delete step:**
- Given: Process Designer click "Xóa" trên một node
- When: Xóa được xác nhận (nhẹ, không cần modal confirmation dài)
- Then: Bước bị xóa khỏi danh sách
- And: Các bước còn lại được re-index (order 1, 2, 3... liên tiếp)
- And: Xóa được persist vào database
- And: Undo không có — xóa là final (nhưng có flash message xác nhận "Bước đã xóa")

**AC5 — Edit existing step:**
- Given: Process Designer click vào một step node hoặc click "Chỉnh sửa" icon
- When: Inline edit form hoặc slide-out panel hiện ra với pre-filled data
- Then: Có thể chỉnh sửa: tên, mô tả, assignee type, assignee ID, duration_hours, is_required
- And: Cancel có sẵn — không lose changes nếu không click Save
- And: Changes persist vào database khi click Save

**AC6 — Save template:**
- Given: Template có ít nhất 1 bước được cấu hình
- When: Process Designer lưu template (auto-save hoặc click Save button)
- Then: Tất cả changes (add/edit/reorder/delete steps) được persist vào database
- And: Flash message hiển thị: "Template đã được cập nhật"
- And: No error nếu không có changes (idempotent)

**AC7 — Visual builder layout:**
- Given: Template editor page load
- When: User xem
- Then: Layout hai cột: trái sidebar/info, phải main editor (steps visualization)
- And: Hoặc single column trên mobile (responsive)
- And: Step nodes hiển thị từ top → bottom (linear, sequential flow, không branching)
- And: Spacing và sizing phù hợp để 3-5 steps hiển thị thoải mái, scroll nếu >5

**AC8 — Step data validation inline:**
- Given: Process Designer thêm/sửa step
- When: Họ điền dữ liệu
- Then: Validation feedback real-time (trước khi save): tên trống, duration ≤ 0, assignee_type trống
- And: Save button disabled nếu form invalid
- And: Error messages rõ ràng, inline trên field

## Tasks / Subtasks

- [x] Task 1: Backend — StepDefinition Model Update (AC2, AC3, AC4, AC5)
  - [x] Thêm `LogsActivity` trait vào `StepDefinition` model (audit trail)
  - [x] Verify `BelongsTo ProcessTemplate` relationship đã có

- [x] Task 2: Backend — Actions (Business Logic Layer)
  - [x] Tạo `app/Actions/Step/CreateStepDefinition.php` (append order, transaction)
  - [x] Tạo `app/Actions/Step/UpdateStepDefinition.php`
  - [x] Tạo `app/Actions/Step/DeleteStepDefinition.php` (soft delete + re-index order)
  - [x] Tạo `app/Actions/Step/ReorderStepDefinition.php` (swap orders, transaction)

- [x] Task 3: Backend — FormRequests + Resource
  - [x] Tạo `app/Http/Requests/Step/StoreStepRequest.php`
  - [x] Tạo `app/Http/Requests/Step/UpdateStepRequest.php`
  - [x] Tạo `app/Http/Requests/Step/ReorderStepRequest.php`
  - [x] Tạo `app/Http/Resources/StepDefinitionResource.php`

- [x] Task 4: Backend — Controller + Routes + Wayfinder
  - [x] Tạo `app/Http/Controllers/StepDefinitionController.php` (store, update, destroy, reorder)
  - [x] Thêm routes trong `routes/web.php`
  - [x] Chạy `php artisan wayfinder:generate`

- [x] Task 5: Backend — Enhance ProcessTemplateController::show()
  - [x] Eager load `stepDefinitions` vào template
  - [x] Pass `steps` array qua Inertia props

- [x] Task 6: Frontend — TypeScript Types
  - [x] Tạo `resources/js/types/step.ts` với interface StepDefinition
  - [x] Update `resources/js/types/index.d.ts`

- [x] Task 7: Frontend — StepCard Component (AC1, AC7)
  - [x] Tạo `resources/js/components/template-builder/StepCard.vue`
  - [x] Display mode: order, name, assignee info, duration badge
  - [x] Action buttons: Edit, Delete, Move Up/Down

- [x] Task 8: Frontend — StepForm Component (AC2, AC5, AC8)
  - [x] Tạo `resources/js/components/template-builder/StepForm.vue`
  - [x] Fields: name, description, assignee_type (Select), assignee_id, duration_hours, is_required (Checkbox)
  - [x] Inline validation (required name, duration > 0)
  - [x] Submit + Cancel buttons

- [x] Task 9: Frontend — StepBuilder Component (AC3, AC4, AC6)
  - [x] Tạo `resources/js/components/template-builder/StepBuilder.vue`
  - [x] Hiển thị list StepCard theo thứ tự
  - [x] Optimistic reorder (move up/down)
  - [x] Delete với flash confirmation
  - [x] Add Step form ở cuối (toggle show/hide)

- [x] Task 10: Frontend — Templates/Show.vue Enhancement (AC1, AC7)
  - [x] Replace placeholder với StepBuilder component
  - [x] Update props: nhận thêm `steps` + `can.update`
  - [x] Layout hai cột: sidebar info + main editor

- [x] Task 11: Feature Tests (tất cả ACs)
  - [x] Tạo `tests/Feature/Step/StepDefinitionTest.php`
  - [x] Tests: add, edit, delete, reorder, 403 security, validation errors
  - [x] Run all tests — không regression

## Developer Context

### What needs to be implemented

Story 2-2 xây dựng trên nền tảng Story 2-1 (template list & create). Bây giờ Process Designer có thể:
- Xem template đã tạo ở dạng visual workflow (step cards/nodes)
- Thêm, xóa, chỉnh sửa từng step
- Sắp xếp lại thứ tự steps
- Validate dữ liệu step trước save
- Tất cả changes persist vào database (step_definitions table)

Điểm khác biệt quan trọng so với Story 2-1:
- Story 2-1: tạo template container (name, description)
- **Story 2-2: populate template với step details** (tên, người phụ trách, deadline, mô tả, config)

### Key architectural patterns this story must follow

1. **API Resource Pattern (ADR-023, ADR-025):**
   - StepDefinitionResource dùng để serialize step data từ backend
   - Return plain array (không wrapper `data` key) cho Inertia props
   - Component nhận `steps: StepDefinition[]` + `can: { edit_steps: boolean }`

2. **Inertia Controller Pattern (ADR-025):**
   - `Templates/Show.vue` route nhận props: `template`, `steps`, `can`
   - Không pass raw Eloquent model — dùng Resource
   - `$this->authorize('update', $template)` là dòng đầu tiên (từ Story 2-1)

3. **Form Request Validation (ADR-022):**
   - Mỗi action (store, update, reorder, delete step) có riêng FormRequest
   - Validation rules check: tên không trống, duration > 0, assignee_type valid, order valid
   - Validation message Vietnamese-friendly

4. **State Management — Minimal UI State (ADR-021):**
   - Steps list quản lý bởi Inertia (server-driven data)
   - UI state: `editingStepId` (null nếu không edit), `isLoading` per action
   - Optimistic UI cho reorder/delete (update local array, API call in background)

5. **Event-Driven Audit (ADR-018, ADR-005):**
   - Mỗi thay đổi step ghi log qua `LogsActivity` trait trên StepDefinition model
   - Log synchronously (không queue)
   - Activity log xem được trong instance detail sau này (Story 3.5)

6. **Transactional Consistency:**
   - Reorder: database transaction đảm bảo all steps được update hoặc not at all
   - Delete: cascade delete trên step_messages, step_attachments nếu có (future)
   - Thứ tự steps luôn liên tiếp (1, 2, 3... không gaps)

### Critical dependencies from Story 2-1

**Models & Database (đã có từ Story 2-1):**
- `ProcessTemplate` model — có relationship `hasMany('stepDefinitions')`
- `StepDefinition` model — có `order` column, unique constraint `['template_id', 'order']`
- Migrations `process_templates`, `step_definitions` tables

**Controllers & Routes (đã có):**
- `ProcessTemplateController::show()` — render Templates/Show page
- Route `process-templates.show` — parameter binding `{process_template}`

**Policies (đã có):**
- `ProcessTemplatePolicy::update()` → checks `manage_templates` permission
- Dùng same policy check cho step operations (steps belong to template)

**Tests (đã có):**
- Test patterns từ Story 2-1 (Pest feature tests, unauthorized tests)
- Base seeder setup (RequiredDataSeeder, RolesSeeder)

### Performance & security constraints

**Performance:**
- Eager load steps trong controller: `$template->load('stepDefinitions')`
- Reorder operation: batch update, không N individual queries (use transaction + single SQL or ORM batch update)
- Frontend: optimistic UI update, don't wait for server confirmation (but handle errors gracefully)

**Security:**
- RBAC: only `process_designer` + `admin` can edit steps (policy `manage_templates`)
- Input validation: sanitize step name/description (no script injection)
- Authorization on every step action: store, update, delete, reorder
- Activity log every change for audit trail

**Constraints from Architecture:**
- ADR-036: `duration_hours` NOT NULL, default 24
- ADR-036: `assignee_type` can be null (lazy design, filled when publish)
- ADR-043: unique constraint `['template_id', 'order']`
- ADR-043: soft deletes on StepDefinition

## Architecture Compliance

### Database Schema (ADR-036)

Step definitions table **already created** in Story 2-1:

```sql
step_definitions (
    id BIGINT PRIMARY KEY,
    template_id BIGINT (FK → process_templates.id, restrictOnDelete),
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    order INT NOT NULL,
    assignee_type ENUM('user','role','department') NULL,
    assignee_id BIGINT NULL,
    duration_hours INT NOT NULL DEFAULT 24,
    is_required BOOLEAN DEFAULT true,
    config_data JSON NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_template_order (template_id, order),
    SOFT DELETES at deleted_at
)
```

**No new migrations needed** — use existing step_definitions table.

### API Endpoints Required

**GET /process-templates/{template}** (Story 2-1, enhanced for 2-2)
- Returns: template resource + array of step resources
- Use eager loading: `->load('stepDefinitions')`
- Authorization: `manage_templates` permission

**POST /step-definitions** (Story 2-2 new)
- Create new step for template
- Request body: `template_id`, `name`, `description`, `assignee_type`, `assignee_id`, `duration_hours`, `is_required`
- Calculates `order` = max(order) + 1 in template
- Authorization: `manage_templates` on related template

**PATCH /step-definitions/{step}** (Story 2-2 new)
- Update step fields
- Body: any of `name`, `description`, `assignee_type`, `assignee_id`, `duration_hours`, `is_required`
- Authorization: `manage_templates` on related template

**PATCH /step-definitions/{step}/reorder** (Story 2-2 new)
- Move step up/down
- Body: `new_order` (integer)
- Logic: shift existing steps, maintain unique constraint
- Database: transaction to ensure atomic update
- Returns: full updated steps array (so frontend can sync)

**DELETE /step-definitions/{step}** (Story 2-2 new)
- Delete step
- Logic: re-number remaining steps (order 1, 2, 3...)
- Database: transaction
- Returns: success message

### RBAC & Authorization (ADR-004)

All endpoints check: `$this->authorize('update', $step->template)`
- Policy resolves to `manage_templates` permission
- Only `process_designer` + `admin` allowed
- Logs unauthorized attempts (ADR-005)

### UI/Component Patterns (ADR-025)

**Component Structure:**
```
Templates/Show.vue
├── TemplateHeader (template name, status, buttons)
├── StepBuilder (main editor)
│   ├── StepCard (per step)
│   │   ├── StepInfo (display mode)
│   │   ├── StepForm (edit mode)
│   │   └── StepActions (edit, delete, move buttons)
│   └── AddStepForm (new step form at bottom)
└── SaveButton (persist all changes)
```

**Component Design Pattern (ADR-025, UX-DR7):**
- Use shadcn/ui components: Button, Input, Textarea, Select, Checkbox, Card
- Form validation with VeeValidate + Zod (ADR-022 — complex form builder)
- Responsive: single column on mobile, side-by-side on desktop
- Visual hierarchy: large step title, small meta info (order, assignee, deadline)

**Real-time Feedback (UX-DR4, UX-DR12):**
- Optimistic UI: reorder/delete update immediately
- Visual feedback: loading state on buttons, success/error toasts
- Progressive disclosure: edit form hidden by default, show on click

## Technical Requirements

### Vue 3 + TypeScript Component Pattern

```typescript
// Resources/js/pages/Templates/Show.vue — enhanced from Story 2-1
interface StepDefinition {
  id: number
  template_id: number
  name: string
  description: string | null
  order: number
  assignee_type: 'user' | 'role' | 'department' | null
  assignee_id: number | null
  duration_hours: number
  is_required: boolean
  created_at: string
}

interface Props {
  template: ProcessTemplate
  steps: StepDefinition[]
  can: {
    update: boolean
    publish: boolean
  }
}

// State
const editingStepId = ref<number | null>(null)
const newStepForm = reactive({
  name: '',
  description: '',
  assignee_type: null,
  assignee_id: null,
  duration_hours: 24,
  is_required: true,
})
const isLoading = ref(false)
```

### Form Validation (VeeValidate + Zod)

```typescript
import { z } from 'zod'

const stepSchema = z.object({
  name: z.string().min(1, 'Tên bước không được trống').max(255),
  description: z.string().nullable(),
  assignee_type: z.enum(['user', 'role', 'department']).nullable(),
  assignee_id: z.number().nullable(),
  duration_hours: z.number().min(1, 'Thời hạn phải > 0'),
  is_required: z.boolean(),
})

// Use in form component with VeeValidate
const { values, errors, handleSubmit } = useForm({
  validationSchema: toTypedSchema(stepSchema),
  initialValues: editingStep || stepSchema.parse(newStepForm),
})
```

### API Calls (Inertia useForm + axios)

```typescript
// For step creation (Inertia.useForm)
const addStepForm = useForm({
  template_id: props.template.id,
  name: '',
  description: '',
  assignee_type: null,
  assignee_id: null,
  duration_hours: 24,
  is_required: true,
})

addStepForm.post(route('step-definitions.store'), {
  onSuccess: () => {
    // Optimistic: local array already updated
    addStepForm.reset()
  },
  onError: (errors) => {
    // Show error toast
  },
})

// For reorder (axios directly for simpler response handling)
const reorderStep = async (stepId: number, newOrder: number) => {
  isLoading.value = true
  try {
    const response = await axios.patch(
      route('step-definitions.reorder', stepId),
      { new_order: newOrder }
    )
    // Response contains updated steps array, sync locally
    steps.value = response.data.steps
  } catch (error) {
    // Show error, revert local changes
  } finally {
    isLoading.value = false
  }
}
```

### Testing Patterns

**Unit Tests (Vitest):**
- Validate step schema (Zod validation)
- Calculate new order on add/reorder
- Re-index steps after delete

**Feature Tests (Pest):**
- Add step to template ✅
- Edit step in template ✅
- Delete step (renumber remaining) ✅
- Reorder steps ✅
- Unauthorized access → 403 ✅
- Invalid step data → 422 ✅
- Template with steps → state consistency ✅

**Security Tests (Pest — mandatory per ADR-013):**
- non-designer user can't add step → 403
- executor can't edit steps → 403
- guest → redirect to login
- Authorization failure logged (ADR-005)

### Code Quality Standards (ADR-035)

- Pint formatting: `./vendor/bin/pint`
- ESLint Vue 3 + TypeScript: `npm run lint`
- Larastan level 5: `./vendor/bin/phpstan analyse --level=5`
- TypeScript: `npx tsc --noEmit`

## File Structure & Dependencies

### Files to Create

**Backend:**
1. `app/Http/Controllers/StepDefinitionController.php` — store, update, destroy, reorder
2. `app/Http/Requests/Step/StoreStepRequest.php` — validation for create
3. `app/Http/Requests/Step/UpdateStepRequest.php` — validation for update
4. `app/Http/Requests/Step/ReorderStepRequest.php` — validation for reorder
5. `app/Http/Resources/StepDefinitionResource.php` — serialize step for API
6. `app/Actions/Step/CreateStepDefinition.php` — business logic (ADR-017)
7. `app/Actions/Step/UpdateStepDefinition.php`
8. `app/Actions/Step/DeleteStepDefinition.php` (with re-indexing logic)
9. `app/Actions/Step/ReorderStepDefinition.php` (with transaction)
10. `tests/Feature/Step/StepDefinitionTest.php` — all 9+ tests

**Frontend:**
1. `resources/js/pages/Templates/Show.vue` — enhanced from Story 2-1 placeholder
2. `resources/js/components/template-builder/StepCard.vue` — display one step
3. `resources/js/components/template-builder/StepForm.vue` — edit/create form
4. `resources/js/components/template-builder/StepBuilder.vue` — manager of all steps
5. `resources/js/types/step.ts` — TypeScript definitions

### Files to Modify

1. `app/Http/Controllers/ProcessTemplateController.php` — enhance `show()` method
   - Add eager load: `->load('stepDefinitions')`
   - Pass steps array to Inertia

2. `routes/web.php` — add step definition routes
   ```php
   Route::resource('step-definitions', StepDefinitionController::class)
       ->only(['store', 'update', 'destroy'])
       ->middleware(['auth', 'verified']);
   Route::patch('step-definitions/{step}/reorder', [...])->name('step-definitions.reorder');
   ```

3. `app/Models/StepDefinition.php` — add/update relationships
   - Add LogsActivity trait (audit)
   - relationship back to ProcessTemplate: `belongsTo(ProcessTemplate::class)`

4. `database/seeders/demo/SampleTemplatesSeeder.php` — add sample steps
   - Create sample templates with 3-5 steps each
   - Useful for manual testing and screenshots

5. `resources/js/routes/step-definitions.ts` — (auto-generated by wayfinder)

6. `resources/js/types/index.d.ts` — add StepDefinition interface

### Database Migrations

**No new migrations needed** — `step_definitions` table already created in Story 2-1.

If needed for future enhancements:
- Add column for step type (`step_type` enum: 'normal', 'approval', 'create_account')
- Add column for max_attempts
- Add column for escalation rules (JSON)

## Testing Requirements

### Feature Tests (Pest)

```bash
# tests/Feature/Step/StepDefinitionTest.php
test('designer can add step to template', function () {
    $designer = User::factory()->create()->assignRole('process_designer');
    $template = ProcessTemplate::factory()->create(['created_by' => $designer->id]);
    
    $this->actingAs($designer)->post(route('step-definitions.store'), [
        'template_id' => $template->id,
        'name' => 'Review Documents',
        'description' => 'Check all docs',
        'assignee_type' => 'role',
        'assignee_id' => null,
        'duration_hours' => 24,
        'is_required' => true,
    ])->assertValid();
    
    $this->assertDatabaseHas('step_definitions', [
        'template_id' => $template->id,
        'name' => 'Review Documents',
        'order' => 1,
    ]);
});

test('designer can edit step', function () { ... });
test('designer can delete step and remaining steps are renumbered', function () { ... });
test('designer can reorder steps', function () { ... });
test('executor cannot add step', function () { ... });
test('step name validation', function () { ... });
test('step duration validation', function () { ... });
test('concurrent reorder is safe (transaction)', function () { ... });
```

### Unit Tests (Vitest)

```bash
# tests/unit/Step/StepValidation.test.ts
describe('Step validation', () => {
  test('validates required fields', () => {
    const result = stepSchema.safeParse({ name: '' });
    expect(result.success).toBe(false);
    expect(result.error.errors[0].message).toContain('không được trống');
  });
  
  test('validates duration > 0', () => {
    const result = stepSchema.safeParse({ duration_hours: 0 });
    expect(result.success).toBe(false);
  });
});
```

### Component Tests (Vitest + Vue Test Utils)

```bash
# tests/unit/StepCard.test.ts
describe('StepCard component', () => {
  test('renders step info in display mode', () => {
    const wrapper = mount(StepCard, {
      props: { step, editable: false },
    });
    expect(wrapper.text()).toContain(step.name);
  });
  
  test('toggles edit mode on edit button click', async () => {
    const wrapper = mount(StepCard, {
      props: { step, editable: true },
    });
    await wrapper.find('[data-test="edit-button"]').trigger('click');
    expect(wrapper.find('form').exists()).toBe(true);
  });
});
```

## Project Context Reference

**Epic 2: Process Template Management**
- Story 2-1 (done): Template list & create
- **Story 2-2 (ready-for-dev)**: Configure steps with visual builder
- Story 2-3 (backlog): Edit existing template
- Story 2-4 (backlog): Publish/unpublish template

**Related Stories:**
- Story 3-1: Launch instance — uses template + snapshot all step_definitions
- Story 2-4: Publish template — validates each step has assignee_type + duration_hours
- Story 3.3: Auto-step progression — iterates steps by order, creates step_executions

**Key Files from Story 2-1:**
- `app/Models/ProcessTemplate.php` — has relationship to StepDefinition
- `app/Models/StepDefinition.php` — soft delete, timestamps
- `app/Http/Controllers/ProcessTemplateController.php` — show() method returns template
- `resources/js/pages/Templates/Show.vue` — needs step builder component
- `ProcessTemplatePolicy` — apply same authorization to step operations

## Dependencies & Learnings from Story 2-1

### Code Patterns Established

1. **API Resource serialization:**
   - Story 2-1: ProcessTemplateResource serializes template
   - Story 2-2: create StepDefinitionResource, same pattern

2. **RBAC & Policy checking:**
   - Story 2-1: authorize('create', ProcessTemplate::class)
   - Story 2-2: authorize('update', $template) on step operations

3. **Vue component patterns:**
   - Story 2-1: Templates/Index.vue (list view) + Templates/Show.vue (detail)
   - Story 2-2: enhance Show.vue with step builder components

4. **Pest feature tests:**
   - Story 2-1: test unauthorized access, validation, create
   - Story 2-2: add reorder, delete, edit tests + concurrency tests

### Testing Approaches That Worked

1. **RBAC test template:**
   ```php
   test('non-designer gets 403', function () {
       $this->actingAs($user)->get(route('...'))->assertForbidden();
   });
   ```
   — Reuse for every step operation

2. **Database assertion:**
   ```php
   $this->assertDatabaseHas('step_definitions', [...]);
   ```
   — Verify persistence

3. **Security test for every Policy method:**
   — Mandatory per ADR-013

### Problems & Solutions from Story 2-1

| Problem | Solution Applied |
|---------|------------------|
| N+1 query on step count | Use eager loading `->load()` + Resource fallback |
| Auth duplicate in controller + FormRequest | Keep ONLY in FormRequest (inherited from base) |
| Flash message type safety | Type check with `usePage().props.flash` |
| Route generation after code | Use wayfinder CLI post-routes |
| Soft delete constraint index | Add raw SQL `WHERE deleted_at IS NULL` |

---

## References

- [Epic 2] Epics breakdown: `_bmad-output/planning-artifacts/epics.md#Epic-2`
- [ADR-004] RBAC + Policy: `_bmad-output/planning-artifacts/architecture.md#ADR-004`
- [ADR-005] Audit logging: `_bmad-output/planning-artifacts/architecture.md#ADR-005`
- [ADR-017] Actions pattern: `_bmad-output/planning-artifacts/architecture.md#ADR-017`
- [ADR-018] Domain events: `_bmad-output/planning-artifacts/architecture.md#ADR-018`
- [ADR-025] Component structure: `_bmad-output/planning-artifacts/architecture.md#ADR-025`
- [ADR-036] Template schema: `_bmad-output/planning-artifacts/architecture.md#ADR-036`
- [UX-DR7] Visual flow representation: `_bmad-output/planning-artifacts/ux-design-specification.md#UX-DR7`
- [Story 2-1] Template list & create: `_bmad-output/implementation-artifacts/2-1-template-list-create-new-template.md`
- [ProcessTemplate Model] `app/Models/ProcessTemplate.php`
- [StepDefinition Model] `app/Models/StepDefinition.php`
