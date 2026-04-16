# Story 2.1: Template List & Create New Template (FR1, FR5)

Status: ready-for-dev

## Story

As a Process Designer,
I want to see all process templates and create new ones,
so that I can start building workflows for the organization.

## Acceptance Criteria

**AC1 — Xem danh sách templates:**
- Given: Process Designer đăng nhập và truy cập trang Templates
- When: Trang load
- Then: Danh sách tất cả templates hiển thị với: tên, trạng thái (Draft/Published), số bước, ngày tạo

**AC2 — Tạo template mới:**
- Given: Process Designer trên trang Template List
- When: Họ click "Tạo template mới" và nhập tên + mô tả
- Then: Template mới được tạo với trạng thái Draft
- And: Họ được redirect đến trang chỉnh sửa template vừa tạo (Templates/Show.vue)

**AC3 — RBAC Protection:**
- Given: User với role không phải Process Designer hoặc Admin
- When: Họ truy cập trang Templates
- Then: System trả về 403 Forbidden

**AC4 — Unique Name Validation:**
- Given: Process Designer nhập tên template trùng với template đã tồn tại
- When: Họ submit form
- Then: Form hiển thị lỗi validation "Tên template đã tồn tại"

## Tasks / Subtasks

- [ ] Task 1: Database Migrations (AC1 — số bước cần step_definitions table)
  - [ ] Tạo migration `process_templates` table: `id`, `name` (string, unique), `description` (text, nullable), `created_by` (FK users), `is_published` (bool, default false), `published_at` (timestamp, nullable), `version` (int, default 1), `deleted_at`, timestamps
  - [ ] Tạo migration `step_definitions` table: `id`, `template_id` (FK, restrictOnDelete), `name` (string), `description` (text, nullable), `order` (int), `assignee_type` (enum: user|role|department), `assignee_id` (nullable), `duration_hours` (int, NOT NULL DEFAULT 24), `is_required` (bool, default true), `config_data` (json, nullable), `deleted_at`, timestamps
  - [ ] Unique constraint: `['template_id', 'order']` trong step_definitions

- [ ] Task 2: Backend Models
  - [ ] Tạo `app/Models/ProcessTemplate.php`: SoftDeletes, LogsActivity, HasMany stepDefinitions, scope `published()`
  - [ ] Tạo `app/Models/StepDefinition.php`: SoftDeletes, BelongsTo processTemplate

- [ ] Task 3: Backend Controller + FormRequest + API Resource (AC1, AC2, AC4)
  - [ ] Tạo `app/Http/Controllers/ProcessTemplateController.php` với `index()`, `store()`, `show()` methods
  - [ ] Tạo `app/Http/Requests/Template/StoreTemplateRequest.php` với validation rules (unique name check)
  - [ ] Tạo `app/Http/Resources/ProcessTemplateResource.php`

- [ ] Task 4: Routes + Wayfinder Regeneration (AC2, AC3)
  - [ ] Thêm `Route::resource('process-templates', ProcessTemplateController::class)->only(['index', 'store', 'show'])` vào `routes/web.php` trong auth+verified middleware group
  - [ ] Chạy `php artisan wayfinder:generate` để tạo TypeScript route files

- [ ] Task 5: Frontend — Templates/Index.vue (AC1, AC2, AC4)
  - [ ] Tạo `resources/js/pages/Templates/Index.vue`: hiển thị template list (name, status badge, step count, created_at) + inline create form (name, description)

- [ ] Task 6: Frontend — Templates/Show.vue (AC2 — redirect target)
  - [ ] Tạo `resources/js/pages/Templates/Show.vue`: placeholder page hiển thị template name, description, status. Steps section sẽ được implement trong Story 2.2

- [ ] Task 7: Sidebar Navigation
  - [ ] Cập nhật `resources/js/components/AppSidebar.vue`: thêm "Templates" link khi `can('manage_templates')`

- [ ] Task 8: Tests (tất cả ACs)
  - [ ] Tạo `tests/Feature/Template/ProcessTemplateTest.php` với tests cho tất cả ACs + security tests

## Dev Notes

### ⚠️ ĐỪNG TẠO LẠI — Đã tồn tại

- `ProcessTemplatePolicy` tại `app/Policies/ProcessTemplatePolicy.php` — ĐÃ TẠO SẴN từ Story 1.3. Xem bên dưới để biết cách dùng đúng.
- `manage_templates`, `publish_templates` permissions — đã seeded trong `PermissionsSeeder`
- `process_designer` role với `manage_templates` + `publish_templates` — đã seeded trong `RolesSeeder`
- `spatie/laravel-activitylog`, `spatie/laravel-permission` — đã cài
- `usePermission()` composable tại `resources/js/composables/usePermission.ts` — dùng để check `can('manage_templates')`
- Auth+verified middleware group trong `routes/web.php` — đã có
- `AuthorizesRequests` trait trong base `Controller` — đã có

### ProcessTemplatePolicy — Cách Dùng Đúng (CRITICAL)

```php
// Policy ĐÃ TẠO SẴN tại app/Policies/ProcessTemplatePolicy.php
// viewAny() → manage_templates | launch_instances | view_all_instances
// create()  → manage_templates (process_designer + admin only)
// update()  → manage_templates
// delete()  → manage_templates
// publish() → publish_templates

// ⚠️ QUAN TRỌNG: Story 2.1 là Template MANAGEMENT — chỉ cho process_designer và admin
// AC3 nói: non-designer/non-admin → 403
// Dùng 'create' ability cho index() và store() vì nó map đúng sang manage_templates:
public function index(): Response
{
    $this->authorize('create', ProcessTemplate::class);  // ← manage_templates only
    // ...
}

public function store(StoreTemplateRequest $request): RedirectResponse
{
    $this->authorize('create', ProcessTemplate::class);  // ← DÒNG ĐẦU TIÊN
    // ...
}

public function show(ProcessTemplate $template): Response
{
    $this->authorize('update', $template);  // ← manage_templates only
    // ...
}
```

> **Lý do dùng `create` cho index:** Policy's `viewAny` rộng hơn (cho Manager xem template khi launch instance ở Story 3.1). Management index cần restrict hơn, phù hợp với `create` = `manage_templates` permission.

### Database Schema (ADR-036)

```php
// database/migrations/YYYY_MM_DD_NNNNNN_create_process_templates_table.php
Schema::create('process_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->text('description')->nullable();
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->boolean('is_published')->default(false);
    $table->timestamp('published_at')->nullable();
    $table->integer('version')->default(1);
    $table->softDeletes();
    $table->timestamps();
});

// database/migrations/YYYY_MM_DD_NNNNNN_create_step_definitions_table.php
Schema::create('step_definitions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('template_id')->constrained('process_templates')->restrictOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->integer('order');
    $table->enum('assignee_type', ['user', 'role', 'department'])->nullable();
    $table->unsignedBigInteger('assignee_id')->nullable();
    $table->integer('duration_hours')->default(24)->comment('Must NOT be null per ADR-036 publish validation');
    $table->boolean('is_required')->default(true);
    $table->json('config_data')->nullable();
    $table->softDeletes();
    $table->timestamps();

    $table->unique(['template_id', 'order']); // ADR-043: order unique trong template
});
```

**Migration naming convention (ADR-041):**
- `process_templates`: số trong range 000010
- `step_definitions`: số trong range 000011
- Dùng format: `YYYY_MM_DD_NNNNNN_create_process_templates_table.php`

### ProcessTemplate Model Pattern

```php
// app/Models/ProcessTemplate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProcessTemplate extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = ['name', 'description', 'created_by', 'is_published', 'published_at', 'version'];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'description', 'is_published']);
    }

    public function stepDefinitions(): HasMany
    {
        return $this->hasMany(StepDefinition::class, 'template_id')->orderBy('order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
```

### StoreTemplateRequest Pattern (AC4 — unique name)

```php
// app/Http/Requests/Template/StoreTemplateRequest.php
use Illuminate\Validation\Rule;

public function authorize(): bool
{
    return $this->user()->can('create', ProcessTemplate::class);
}

public function rules(): array
{
    return [
        'name'        => [
            'required', 'string', 'max:255',
            Rule::unique('process_templates', 'name')->whereNull('deleted_at'),
        ],
        'description' => ['nullable', 'string', 'max:5000'],
    ];
}

public function messages(): array
{
    return [
        'name.unique' => 'Tên template đã tồn tại.',
    ];
}
```

### ProcessTemplateController Pattern

```php
// app/Http/Controllers/ProcessTemplateController.php
namespace App\Http\Controllers;

use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Resources\ProcessTemplateResource;
use App\Models\ProcessTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProcessTemplateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('create', ProcessTemplate::class);  // DÒNG ĐẦU TIÊN

        $templates = ProcessTemplate::withCount('stepDefinitions')
            ->latest()
            ->get();

        return Inertia::render('Templates/Index', [
            'templates' => ProcessTemplateResource::collection($templates),
            'can' => [
                'create' => auth()->user()->can('create', ProcessTemplate::class),
            ],
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', ProcessTemplate::class);  // DÒNG ĐẦU TIÊN

        $template = ProcessTemplate::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('process-templates.show', $template)
            ->with('success', 'Template đã được tạo. Bắt đầu thêm các bước.');
    }

    public function show(ProcessTemplate $template): Response
    {
        $this->authorize('update', $template);  // DÒNG ĐẦU TIÊN

        $template->loadCount('stepDefinitions');

        return Inertia::render('Templates/Show', [
            'template' => ProcessTemplateResource::make($template),
            'can' => [
                'update' => auth()->user()->can('update', $template),
                'delete' => auth()->user()->can('delete', $template),
                'publish' => auth()->user()->can('publish', $template),
            ],
        ]);
    }
}
```

### ProcessTemplateResource Pattern

```php
// app/Http/Resources/ProcessTemplateResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'is_published'=> $this->is_published,
            'step_count'  => $this->step_definitions_count ?? $this->stepDefinitions()->count(),
            'created_at'  => $this->created_at->toISOString(),
            'version'     => $this->version,
        ];
    }
}
```

### Routes Configuration (ADR — Route Naming Convention)

```php
// routes/web.php — trong middleware auth+verified group
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Story 2.1: Template management (only: index, store, show — update/destroy added in 2.3)
    Route::resource('process-templates', ProcessTemplateController::class)
        ->only(['index', 'store', 'show']);
});
```

**QUAN TRỌNG:** Sau khi thêm routes, chạy:
```bash
php artisan wayfinder:generate
```
→ Tạo `resources/js/routes/process-templates.ts` với TypeScript route definitions.

### Frontend — AppSidebar.vue Update Pattern

```typescript
// THÊM vào AppSidebar.vue — theo pattern từ Story 1.5
import { index as templatesIndex } from '@/routes/process-templates';
// Icon từ lucide-vue-next: import { LayoutTemplate } from 'lucide-vue-next';

// Trong mainNavItems computed:
if (can('manage_templates')) {
    items.push({
        title: 'Templates',
        href: templatesIndex().url,
        icon: LayoutTemplate,  // hoặc icon phù hợp từ lucide-vue-next
    });
}
```

**Vị trí chèn:** Sau Dashboard item, trước User Management item.

### Templates/Index.vue Pattern

- Props: `templates: ProcessTemplateResource[]`, `can: { create: boolean }`
- Layout breadcrumbs: `[{ title: 'Dashboard', href: '...' }, { title: 'Templates', href: '...' }]`
- Hiển thị table/list: name, status badge (Draft=gray/Published=green), step count, created_at formatted
- Inline create form: input `name` + textarea `description` + submit button
- Dùng `useForm()` từ `@inertiajs/vue3` cho create form
- Flash message handling: dùng `usePage().props.flash.success` (theo ADR-019 flash key convention)
- Import routes từ wayfinder: `import { store as templatesStore } from '@/routes/process-templates'`

### Templates/Show.vue (Placeholder cho Story 2.2)

```vue
<!-- resources/js/pages/Templates/Show.vue — Story 2.1: basic template info only -->
<!-- Story 2.2 sẽ thêm visual step builder vào đây -->
<script setup lang="ts">
interface Template {
    id: number;
    name: string;
    description: string | null;
    is_published: boolean;
    step_count: number;
    created_at: string;
    version: number;
}
interface Can {
    update: boolean;
    delete: boolean;
    publish: boolean;
}
const props = defineProps<{ template: Template; can: Can }>();
defineOptions({
    layout: { breadcrumbs: [...] }
});
</script>
```

- Hiển thị: tên, mô tả, trạng thái, số bước hiện tại (0 khi mới tạo)
- Section "Các bước": placeholder text "Thêm bước quy trình trong phần này (sẽ có đầy đủ chức năng sau)"
- Flash success message nếu vừa tạo

### TypeScript Types (ADR-023)

```typescript
// resources/js/types/index.d.ts — thêm vào file hiện có
export interface ProcessTemplate {
    id: number;
    name: string;
    description: string | null;
    is_published: boolean;
    step_count: number;
    created_at: string;
    version: number;
}
```

### Testing Pattern (ADR-013)

```php
// tests/Feature/Template/ProcessTemplateTest.php
use App\Models\ProcessTemplate;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;

// MANDATORY per ADR-013: Security test cho mỗi Policy method
test('non-designer gets 403 on template index', function () {
    $this->seed(RequiredDataSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get(route('process-templates.index'))
        ->assertForbidden();
});

test('executor gets 403 on template index', function () {
    $this->seed(RequiredDataSeeder::class);
    $executor = User::factory()->create();
    $executor->assignRole('executor');

    $this->actingAs($executor)
        ->get(route('process-templates.index'))
        ->assertForbidden();
});

test('guest is redirected from templates', function () {
    $this->get(route('process-templates.index'))
        ->assertRedirect(route('login'));
});
```

### Inertia Controller Rules (ADR-025, Architecture Enforcement)

- **API Resources bắt buộc** — không pass raw Eloquent model vào Inertia props
- **$this->authorize() là dòng đầu tiên** của mọi controller action
- **Flash keys:** chỉ dùng `success`, `error`, `warning` — không dùng `message`, `status`, `info`
- **Route names:** `process-templates.index`, `process-templates.store`, `process-templates.show`

### Scope của Story 2.1 — KHÔNG IMPLEMENT

- ❌ Visual step builder (Story 2.2)
- ❌ Edit template name/description inline (Story 2.3)
- ❌ Delete template (Story 2.3)
- ❌ Publish/Unpublish (Story 2.4)
- ❌ Route `process-templates.edit`, `process-templates.update`, `process-templates.destroy`

### References

- [ADR-036] Template schema: `_bmad-output/planning-artifacts/architecture.md#ADR-036`
- [ADR-017] Actions pattern: `_bmad-output/planning-artifacts/architecture.md#ADR-017`
- [ADR-004] RBAC + Policy: `_bmad-output/planning-artifacts/architecture.md#ADR-004`
- [ADR-043] DB constraints: `_bmad-output/planning-artifacts/architecture.md#ADR-043`
- [ADR-041] Migration naming: `_bmad-output/planning-artifacts/architecture.md#ADR-041`
- [ProcessTemplatePolicy] app/Policies/ProcessTemplatePolicy.php (đã tồn tại)
- [RolesSeeder] database/seeders/RolesSeeder.php — manage_templates → process_designer + admin
- [AppSidebar.vue] resources/js/components/AppSidebar.vue — pattern thêm nav link

## Dev Agent Record

### Agent Model Used

claude-sonnet-4-6

### Debug Log References

### Completion Notes List

### File List
