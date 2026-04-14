---
stepsCompleted: ['step-01-init', 'step-02-context', 'step-03-starter', 'step-04-decisions', 'step-05-patterns', 'step-06-structure', 'step-07-validation', 'step-08-complete']
lastStep: 'step-08-complete'
status: 'complete'
completedAt: '2026-04-12'
inputDocuments:
  - '_bmad-output/planning-artifacts/prd.md'
  - '_bmad-output/planning-artifacts/prd-validation-report.md'
  - '_bmad-output/planning-artifacts/ux-design-specification.md'
workflowType: 'architecture'
project_name: 'workflow'
user_name: 'huyph'
date: '2026-04-12'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

## Project Context Analysis

### Requirements Overview

**Functional Requirements:**
37 FRs phân thành 7 nhóm domain: Process Template Management (FR1-6),
Instance Execution (FR7-15), Manager Dashboard (FR16-19), Executor Task
Management (FR20-23), Beneficiary Interface (FR24-26), Notification &
Communication (FR27-32), System Administration (FR33-37).

Các FRs phản ánh hệ thống có vòng lõi rõ ràng: tạo template → khởi chạy
instance → tracking real-time → can thiệp khi cần. Đặc biệt: immutable
template versioning (FR6) và beneficiary auto-account creation (FR36) là
hai FR đòi hỏi xử lý data flow phi tuyến tính.

**Non-Functional Requirements:**
- Performance: Dashboard load < 3s với 100 concurrent users (NFR1);
  Notification delivery ≤ 60s từ event trigger (NFR2)
- Reliability: Uptime trong giờ làm việc 08:00-18:00, backup/restore
  không mất dữ liệu (NFR3, NFR4)
- Security: Data on-premise, RBAC enforcement với audit log vi phạm (NFR5, NFR6)
- Deployability: Cài đặt < 1 giờ bởi người có kiến thức server cơ bản,
  Docker packaging (NFR8, NFR9)

**Scale & Complexity:**

- Primary domain: Full-stack web (SaaS B2B, on-premise / single-tenant)
- Complexity level: Medium
- Target scale: 100 concurrent users, ~30 active processes, single organization
- Estimated architectural components: ~8-10 (auth, template engine,
  instance runner, dashboard API, executor API, notification service,
  scheduler, admin API, activity log)

### Technical Constraints & Dependencies

- **Single-tenant**: Mỗi cài đặt phục vụ 1 tổ chức — không cần multi-tenant
  data isolation
- **No horizontal scaling for MVP**: Vertical scaling đủ cho 100 users
- **SMTP only**: Kênh notification duy nhất trong MVP — không phụ thuộc
  external messaging service
- **No external API integrations**: MVP hoàn toàn self-contained
- **Docker packaging**: Deployment phải có thể thực hiện bằng docker-compose
  hoặc tương đương
- **Design system locked**: Shadcn/ui + Tailwind CSS (đã quyết định ở UX spec)
- **Web responsive only**: Không cần native app; responsive web đủ cho
  cả desktop lẫn mobile

### Cross-Cutting Concerns Identified

1. **Authentication & RBAC (5 roles)** — Ảnh hưởng mọi API endpoint và
   data query. Access scope khác nhau theo từng vai trò cần được enforce
   nhất quán ở tầng middleware/service, không phải controller.

2. **Audit Logging** — FR15 yêu cầu log đầy đủ mọi hành động trên instance.
   Cần chiến lược append-only event log — ảnh hưởng thiết kế data model.

3. **Real-time Data Delivery** — Dashboard và Executor Inbox cần cập nhật
   khi có thay đổi. Phải chọn mechanism phù hợp (WebSocket / SSE / polling)
   cân bằng giữa complexity và self-hosted deployment.

4. **Background Job Scheduling** — Deadline monitoring (FR28-FR30) và
   notification delivery cần scheduler chạy độc lập với request cycle.

5. **Template Versioning / Immutable Snapshot** — Instance phải giữ nguyên
   cấu trúc template tại thời điểm khởi tạo. Yêu cầu snapshot strategy
   hoặc versioning scheme trong data model.

6. **Optimistic UI Consistency** — UX spec yêu cầu immediate feedback khi
   hoàn thành task. Cần cơ chế rollback và error handling khi server
   trả về lỗi sau optimistic update.

---

## Starter Template Evaluation

### Primary Technology Domain

Full-stack web application — Laravel monolith với Inertia.js làm cầu nối
server-side routing và Vue 3 SPA frontend. Không cần API layer riêng biệt.

### Starter Options Considered

1. **Laravel Official Vue Starter Kit** (laravel/vue-starter-kit) — Inertia 2,
   Vue 3, TypeScript, shadcn-vue, Tailwind. Bao gồm đầy đủ auth scaffolding.
2. **T3 Stack** — loại bỏ vì không dùng Next.js/React.
3. **Custom Breeze install** — Vue Starter Kit đã thay thế Breeze cho stack này.

### Selected Starter: Laravel Official Vue Starter Kit

**Rationale for Selection:**
- shadcn-vue tích hợp sẵn → không cần setup thủ công design system
- Inertia 2 + Vue 3 Composition API + TypeScript đúng stack yêu cầu
- Laravel 12 ecosystem: Reverb (WebSocket), Queue, Scheduler — mọi
  cross-cutting concern đã có first-party solution
- Single-tenant on-premise deployment với Docker hoàn toàn khả thi

**Initialization Command:**

```bash
laravel new workflow --using=vue
# TypeScript: Yes | Inertia SSR: No | Testing: Pest
```

**Architectural Decisions Provided by Starter:**

**Language & Runtime:**
PHP 8.2+ (backend) + TypeScript 5.x (frontend). Shared type contracts
qua Inertia page props — không cần tRPC hay OpenAPI.

**Styling Solution:**
Tailwind CSS + shadcn-vue. Components publish vào `resources/js/components/ui/`.
Design tokens định nghĩa một lần trong `tailwind.config.js`.

**Build Tooling:**
Vite — HMR trong dev, chunking tối ưu trong production build.

**Testing Framework:**
Pest PHP (feature + unit tests). Vitest cho Vue component tests.

**Code Organization:**
```
app/           — Controllers, Models, Services, Jobs, Events
resources/js/  — Vue pages, components, composables, layouts
resources/js/components/ui/  — shadcn-vue components
routes/web.php — Inertia routes (không cần api.php)
database/      — Migrations, seeders, factories
```

---

## Architecture Decision Records

### Infrastructure & Platform (ADR-001 đến ADR-015)

#### ADR-001: Database Engine — PostgreSQL

**Decision:** PostgreSQL (bỏ qua MySQL, SQLite)

**Rationale:**
- Concurrent writes từ nhiều nguồn (dashboard real-time + scheduler +
  executor inbox) không bị write lock như SQLite
- Transaction isolation mạnh cho audit log (FR15) và template versioning
- JSON column support cho flexible step output data (Growth features)
- Row-level locking phù hợp với instance state machine transitions

**Trade-offs:**
- (+) Robust transactions, JSON support, LISTEN/NOTIFY capability
- (+) Tích hợp tốt với Laravel Eloquent qua `pgsql` driver
- (-) Thêm 1 container trong Docker Compose (PostgreSQL service)

---

#### ADR-002: Real-time Mechanism — Laravel Reverb

**Decision:** Laravel Reverb WebSocket (defer đến Story 2+, không phải Story 1)

**Rationale:**
- First-party Laravel, built on FrankenPHP — không phải separate server nặng
- Pusher-compatible protocol → Laravel Echo tích hợp Vue composable dễ dàng
- Bi-directional khi cần in-system messaging (FR: ping step owner)
- Polling loại bỏ: 100 users × polling interval = load không cần thiết

**Trade-offs:**
- (+) Laravel Echo on frontend, broadcast events từ Jobs/Models
- (+) Dùng lại cho in-system messaging trong Growth features
- (-) Cần `REVERB_*` env vars, queue worker cho broadcasts
- (-) Port 8080 expose thêm trong Docker

---

#### ADR-003: Queue Driver — Redis + Named Queues

**Decision:** Redis với 3 named queues riêng biệt

**Queue separation:**
```
'broadcasts'     → Reverb events (highest priority, real-time)
'notifications'  → emails, database notifications (normal)
'default'        → everything else (low priority)
```

**Docker workers:**
```yaml
queue-broadcasts:
  command: php artisan queue:work --queue=broadcasts --tries=3 --max-jobs=500
  restart: unless-stopped
queue-default:
  command: php artisan queue:work --queue=notifications,default --tries=3 --max-jobs=500
  restart: unless-stopped
```

**Trade-offs:**
- (+) Broadcast events không bị block bởi email queue backlog
- (+) `--max-jobs=500` prevent memory leaks, restart policy ensure recovery
- (+) Horizon-ready nếu cần monitoring sau này
- (-) Thêm Redis container (Alpine image, ~50MB)

---

#### ADR-004: RBAC — spatie/laravel-permission + Policy Layer

**Decision:** spatie/laravel-permission kết hợp với Laravel Policies bắt buộc

**Policy rule:** `$this->authorize('action', $model)` là dòng đầu tiên
của mọi controller action. Policies implement ownership-aware checks:

```php
// ProcessInstancePolicy::view()
return match(true) {
    $user->hasRole(['manager', 'process_designer', 'admin']) => true,
    $user->hasRole('executor') =>
        $instance->stepExecutions()->where('assigned_to', $user->id)->exists(),
    $user->hasRole('beneficiary') => $instance->created_for === $user->id,
    default => false,
};
```

**5 Roles:** `admin`, `manager`, `process_designer`, `executor`, `beneficiary`

**9 Permissions:** `manage_templates`, `publish_templates`, `launch_instances`,
`view_all_instances`, `manage_instances`, `complete_assigned_steps`,
`view_own_instances`, `manage_users`, `manage_system`

**Trade-offs:**
- (+) Role + permission matrix, built-in cache, audit integration
- (+) Ownership checks prevent URL manipulation attacks
- (-) Mọi controller action cần explicit `authorize()` call — discipline required

---

#### ADR-005: Audit Log — spatie/laravel-activitylog (SYNC, không queue)

**Decision:** spatie/laravel-activitylog với `queue: false` bắt buộc

**Critical config:**
```php
// config/activitylog.php
'queue' => false,  // NEVER queue — nếu action succeeded, log PHẢI ghi ngay
```

**Rationale:** Mất log = compliance failure với FR15. Sync write là
bắt buộc để đảm bảo audit trail không có gaps.

**Trade-offs:**
- (+) Audit trail không bao giờ có gaps
- (+) `LogsActivity` trait + manual `activity()->log()` cho business events
- (-) Slight overhead per request (negligible, DB write ~1-2ms)

---

#### ADR-006: Template Versioning — JSON Snapshot trong Instance

**Decision:** Serialize toàn bộ template config vào `template_snapshot_data`
JSON column khi launch instance.

```php
// process_instances table
$table->json('template_snapshot_data'); // full template at launch time
$table->foreignId('template_id')->constrained(); // reference giữ nguyên
```

**Trade-offs:**
- (+) Instance tự contained — template có thể thay đổi sau khi launch
- (+) Zero extra tables, serialize từ normalized step_definitions
- (-) Instance size tăng nhẹ (~2-5KB per instance)

---

#### ADR-007: File Storage — Local Filesystem + Laravel Storage Facade + Backup Script

**Decision:** Local filesystem với abstraction đúng cách

```php
Storage::disk('local')->put($path, $file); // never hardcode paths
```

**Backup script (`backup.sh`):**
```bash
DATE=$(date +%Y%m%d_%H%M)
docker compose exec postgres pg_dump -U postgres workflow > backups/db_${DATE}.sql
tar -czf backups/storage_${DATE}.tar.gz \
  -C $(docker volume inspect workflow_storage_data --format '{{.Mountpoint}}') .
find backups/ -mtime +30 -delete  # retain 30 days
```

**Trade-offs:**
- (+) `FILESYSTEM_DISK` env var → switch sang S3/MinIO không refactor code
- (+) Backup script cover cả DB + storage volume
- (-) Backup phải bao gồm cả storage volume, không chỉ DB dump

---

#### ADR-008: Email — Queued Mailables + Mailpit

**Decision:** Tất cả Mailables implement `ShouldQueue`, Mailpit trong dev

**Trade-offs:**
- (+) SMTP failure/timeout không ảnh hưởng request cycle
- (+) Mailpit: visual email preview trong dev, zero config
- (-) Cần queue worker (đã có từ ADR-003)

---

#### ADR-009: Optimistic UI — Defer, Inertia Loading States

**Decision:** Dùng Inertia `form.processing` flag thay vì optimistic state

```vue
<Button :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
  {{ form.processing ? 'Đang lưu...' : 'Hoàn thành' }}
</Button>
```

**Trade-offs:**
- (+) Zero rollback complexity, consistent server-client state
- (-) Slight perceived latency trên slow connections (acceptable LAN)

---

#### ADR-010: Cache — Redis, Scope Giới Hạn + Cache Warm-up

**Decision:** Redis cache driver, chỉ cache permissions + config. Không bao giờ
cache business data (instance state, task status).

**Post-restart warm-up (deploy.sh):**
```bash
php artisan permission:cache-reset
php artisan config:cache
php artisan route:cache
```

**Trade-offs:**
- (+) Permissions cached: spatie auto-cache, ~0ms cho auth checks
- (-) Business data luôn fresh từ DB — consistency với audit log

---

#### ADR-011: Docker Structure — Split Compose + Multi-stage + Named Volumes + Restart Policies

**Decision:** 3 compose files + multi-stage Dockerfile + named volumes

```yaml
# docker-compose.yml (base — production)
volumes:
  postgres_data: {}
  redis_data: {}
  storage_data: {}          # CRITICAL: file attachments persistent

services:
  app:
    volumes: [storage_data:/var/www/html/storage/app]
    # PHP-FPM: pm.max_children=20, pm.start_servers=5
  queue-broadcasts:
    restart: unless-stopped # CRITICAL: auto-recovery
  queue-default:
    restart: unless-stopped
  scheduler:
    restart: unless-stopped
  postgres:
    volumes: [postgres_data:/var/lib/postgresql/data]
  redis:
    volumes: [redis_data:/data]
```

```
docker-compose.yml          # base: all services với named volumes
docker-compose.override.yml # dev: Mailpit, bind mounts cho hot reload
docker-compose.prod.yml     # prod: resource limits
Dockerfile                  # multi-stage: builder + slim runtime
```

**Trade-offs:**
- (+) Named volumes: data persist qua container restarts/recreates
- (+) `restart: unless-stopped`: workers tự recover sau crash
- (+) Multi-stage: prod image không có composer/npm — nhỏ hơn, an toàn hơn
- (-) 3 file cần maintain

---

#### ADR-012: Dev Workflow — Laravel Sail + Override

**Decision:** Laravel Sail cho individual dev, `docker-compose.override.yml`
là canonical dev config cho team

**Trade-offs:**
- (+) `sail up -d`: toàn bộ dev environment trong 1 lệnh
- (-) Sail adds own docker-compose layer — cần sync với custom compose

---

#### ADR-013: Testing — Pest Feature > Vitest > Unit, Security Tests Bắt buộc

**Decision:** Pest feature tests ưu tiên cao nhất. Security test template
bắt buộc cho mọi Policy method.

```
Pest Feature Tests (highest priority):
  - Auth flows, RBAC middleware
  - Process instance lifecycle
  - Notification dispatch assertions
  - Audit log entries
  - MANDATORY: unauthorized access test cho mọi Policy method

Vitest: shadcn-vue customizations, composables, page props types

Pest Unit Tests: ProcessStateMachine, DeadlineCalculator, PermissionMatrix

Playwright E2E: defer to post-MVP
```

**Trade-offs:**
- (+) Feature tests catch integration bugs + security regressions
- (-) No E2E = possible browser rendering regression (acceptable MVP)

---

#### ADR-014: Observability — Telescope Dev-only + Structured Logs

**Decision:** Telescope chỉ dev (`TELESCOPE_ENABLED=false` production),
structured JSON logs cho production

```php
// prod: LOG_CHANNEL=stack, LOG_STACK=daily,stderr
// Levels: CRITICAL (system down), ERROR (exception), WARNING (unexpected),
//         INFO (significant business events), DEBUG (dev only)
// Always include: user_id, entity IDs. Never include: passwords, tokens
```

**Trade-offs:**
- (+) Telescope không bao giờ leak vào production
- (+) Stderr logs: Docker logging system handle natively

---

#### ADR-015: Deployment — Maintenance Window + Backwards-compatible Migrations + CI Gate

**Decision:** Deploy ngoài giờ hành chính với health checks

**CI gate cho destructive migrations:**
```bash
grep -rE "renameColumn|dropColumn|->change\(\)" database/migrations/ \
  && echo "⚠️  Destructive migration — requires 2nd approver" && exit 1
```

**deploy.sh:**
```bash
docker compose pull
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan config:cache
docker compose run --rm app php artisan route:cache
docker compose run --rm app php artisan view:cache
docker compose up -d
# Health checks:
curl -sf http://localhost/up || (echo "App not responding" && exit 1)
php artisan db:monitor || (echo "DB connection failed" && exit 1)
test -d storage/app || (echo "Storage volume not mounted" && exit 1)
echo "✅ Deploy verified"
```

**Migration rules:**
```
✅ Add nullable column | Add new table | Add index → safe anytime
❌ Drop column → separate migration 1+ deploys after code change
❌ Rename column → add new + migrate data + remove old (3 deploys)
```

---

### Application Architecture (ADR-016 đến ADR-025)

#### ADR-016: State Machine — spatie/laravel-model-states

**States:**
```
ProcessInstance: Pending → Running → (Completed | Cancelled)
                 Running ↔ Paused
StepExecution:   Pending → InProgress → (Completed | Skipped | Escalated)
                 InProgress ↔ Blocked
```

**Trade-offs:**
- (+) Invalid transitions throw exception — bắt được ở test
- (+) Built-in transition history tích hợp với activitylog

---

#### ADR-017: Service Layer — Single-action Invokable Classes (Actions Pattern)

**Structure:**
```
app/Actions/
  Process/ LaunchProcessInstance, CancelProcessInstance, PauseProcessInstance
  Step/    CompleteStepExecution, EscalateStep, ReassignStep
  Template/ PublishTemplate, CreateTemplateVersion
```

**Controller stays thin:**
```php
public function launch(LaunchRequest $request, Template $template) {
    $instance = app(LaunchProcessInstance::class)($template, $request->validated());
    return redirect()->route('instances.show', $instance);
}
```

**Trade-offs:**
- (+) 1 class = 1 responsibility, trivial to unit test
- (+) Actions reusable từ controller, job, console command, future API
- (-) Nhiều files hơn Service approach

---

#### ADR-018: Domain Events — Laravel Events + Queued Listeners

**Event architecture:**
```php
StepCompleted    → LogStep(sync), NotifyAssignees(queued:notifications),
                   CheckProcessCompletion(queued:default),
                   BroadcastStepUpdate(queued:broadcasts)
ProcessLaunched  → NotifyFirstStepAssignees(queued), BroadcastProcessCreated(queued)
ProcessCompleted → NotifyManager(queued:notifications, high priority)
StepEscalated    → NotifyManager(queued:notifications), LogEscalation(sync)
DeadlineApproaching → dispatched by Scheduler → NotifyAssignee(queued)
```

**Trade-offs:**
- (+) Add notification channel mới = add 1 Listener, không touch Action
- (+) Queued listeners: request trả về ngay, side effects async

---

#### ADR-019: Inertia Shared Data — HandleInertiaRequests + Permission-based

**Shared props (TypeScript):**
```typescript
interface SharedProps {
  auth: {
    user: User
    can: {
      manage_templates: boolean; publish_templates: boolean;
      launch_instances: boolean; view_all_instances: boolean;
      complete_assigned_steps: boolean; manage_users: boolean;
      // ... 9 granular permissions
    }
  }
  flash: { success?: string; error?: string }
}
```

**Security rule:** Frontend `can[]` chỉ để hide/show UI — không phải security
gate. Server-side Policy check là authoritative.

---

#### ADR-020: Broadcast Channels — Private RBAC-aware + Executor Channel Fix

**Channel design:**
```php
// routes/channels.php
Broadcast::channel('organization.{orgId}', fn($user, $orgId) =>
    $user->hasAnyRole(['admin', 'manager', 'process_designer'])
);
Broadcast::channel('user.{userId}', fn($user, $userId) =>
    (int)$user->id === (int)$userId
);

// StepCompleted broadcasts to ALL relevant channels:
public function broadcastOn(): array {
    return [
        new PrivateChannel("organization.{$this->instance->org_id}"),
        new PrivateChannel("user.{$this->step->assigned_to}"),       // executor inbox
        new PrivateChannel("user.{$this->nextStep?->assigned_to}"),  // next assignee
    ];
}
```

**Broadcast rate limiting:** Dashboard reconnect polls "missed events since
last_event_id" — không nhận N individual broadcasts đồng thời.

---

#### ADR-021: State Management — Pinia, Minimal Stores

**Decision:** Pinia với tối đa 2-3 stores cho MVP

```typescript
// stores/notification.ts — unread count, toast queue
// stores/ui.ts — sidebar state, active filters (persist localStorage)
// KHÔNG tạo store cho: auth (từ Inertia shared props), process data (server-driven)
```

---

#### ADR-022: Form Handling — Inertia useForm Default, VeeValidate + Zod cho Complex

```typescript
// Standard forms: Inertia useForm
const form = useForm({ name: '', deadline: '' })
form.post(route('instances.store'))

// Complex forms (Template Builder only): VeeValidate + Zod
const { handleSubmit, errors } = useForm({ validationSchema: templateSchema })
```

---

#### ADR-023: TypeScript Types — Manual + Ziggy Routes

```typescript
// resources/js/types/index.d.ts
export interface User { id: number; name: string; email: string }
export interface ProcessInstance { id: number; status: InstanceStatus; ... }
export type InstanceStatus = 'pending'|'running'|'paused'|'completed'|'cancelled'

// Ziggy: route('instances.show', { instance: 1 }) — type-safe, no hardcoded URLs
```

---

#### ADR-024: Real-time Composable — useEcho() với Auto-cleanup

```typescript
// composables/useEcho.ts — memory leak không thể xảy ra
export function useEcho(channel: string, event: string, callback: Function) {
  onMounted(() => window.Echo.private(channel).listen(event, callback))
  onUnmounted(() => window.Echo.leave(channel))  // auto-cleanup
}
```

---

#### ADR-025: Component Structure — Hybrid Flat/Feature

```
resources/js/
├── pages/          # Inertia pages, match route names
│   ├── Auth/       Dashboard/ Instances/ Templates/ Executor/
├── components/
│   ├── ui/         # shadcn-vue (generated, do not edit)
│   ├── shared/     # cross-feature reusable
│   └── [feature]/  # feature-specific
├── composables/    # useEcho, usePermission
├── layouts/        # AppLayout, AuthLayout
├── stores/         # Pinia (2-3 max)
├── types/          # TypeScript interfaces
└── lib/utils.ts    # shadcn-vue cn() utility
```

---

### Security & Operations (ADR-026 đến ADR-030)

#### ADR-026: Authentication — Session Auth (Web Middleware)

**Decision:** Laravel session auth. Không dùng Sanctum cho MVP.

**Rationale:** Inertia là server-driven SPA — cookie session là đúng pattern,
bảo mật hơn localStorage tokens. Sanctum có thể add sau cho mobile API.

---

#### ADR-027: Security Headers — Minimal Now, CSP Defer

```php
$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-XSS-Protection', '1; mode=block');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
$response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
// CSP: defer — calibrate với Vite nonce sau khi prod stable
```

---

#### ADR-028: Rate Limiting — Tiered

```php
RateLimiter::for('auth', fn($req) => Limit::perMinute(5)->by($req->ip()));
RateLimiter::for('notifications', fn($req) => Limit::perMinute(10)->by($req->user()?->id));
RateLimiter::for('uploads', fn($req) => Limit::perMinute(20)->by($req->user()?->id));
```

---

#### ADR-029: Deadline Scheduler — Hybrid Polling + Immediate Dispatch + Monitoring

```php
Schedule::job(CheckApproachingDeadlines::class)
    ->everyMinute()->withoutOverlapping()->onOneServer();

// Alert nếu failed_jobs > 0:
Schedule::job(CheckFailedJobs::class)->hourly();

// State consistency canary:
Schedule::job(CheckStateConsistency::class)->hourly()->at(':30');
// Detect: instances running nhưng tất cả steps completed
// Detect: steps in_progress với deactivated assignee
```

---

#### ADR-030: Future API — YAGNI Routes, Actions Pattern Ensures Readiness

**Decision:** Không add `/api` routes trong MVP. Actions pattern (ADR-017)
đảm bảo business logic reusable khi cần API controllers sau này.

---

### Naming Conventions (ADR-031 đến ADR-035)

#### ADR-031: PHP Naming

```
Actions:    VerbNoun.php           → LaunchProcessInstance
Events:     NounVerbed.php         → StepCompleted, ProcessLaunched
Listeners:  VerbNounOnEvent.php    → SendNotificationOnStepCompleted
States:     NounState.php          → RunningState, CompletedState
Jobs:       VerbNoun.php           → CheckApproachingDeadlines

DB columns:
  Foreign keys: {model}_id         → template_id, assigned_user_id
  Timestamps:   {event}_at         → launched_at, completed_at
  Booleans:     is_{state}         → is_active, is_published
  JSON:         {name}_data        → template_snapshot_data

Broadcast channels: {entity}.{id} → organization.1, user.42
Broadcast events:   {Entity}{Verbed} → StepCompleted, ProcessLaunched
```

---

#### ADR-032: Vue/TypeScript Naming

```
Components: PascalCase, feature-prefixed → ProcessStatusBadge, ExecutorTaskCard
Composables: use* prefix              → useEcho, usePermission
Pinia stores: use*Store               → useNotificationStore
Pages: match route segments           → route('instances.show') → Instances/Show.vue
TypeScript: PascalCase, no 'I' prefix → interface ProcessInstance (not IProcessInstance)
```

---

#### ADR-033: Exception Handling — Tiered

```php
// Domain exceptions → user-friendly message (HTTP 422/403)
class InvalidStateTransitionException extends DomainException {}
class InsufficientPermissionException extends DomainException {}
class TemplateNotPublishedException extends DomainException {}

// System exceptions → log ERROR + generic 500 (never expose stack trace)
// Handler: DomainException → back()->with('error', $e->getMessage())
//          \Exception → Inertia::render('Error', ['status' => 500])
```

---

#### ADR-034: Logging Standards

```
CRITICAL: system không hoạt động (Redis down, DB unreachable)
ERROR:    exception cần investigation (notification fail, queue error)
WARNING:  unexpected nhưng handled (overdue steps detected)
INFO:     significant business events (process launched) — dùng sparingly
DEBUG:    dev only, never production

Always include: user_id, relevant entity IDs
Never include:  passwords, tokens, PII beyond user_id
```

---

#### ADR-035: Code Quality — Pint + Prettier + Larastan 5 + CI Gates

```bash
# Pre-commit (zero friction):
./vendor/bin/pint          # PHP formatting (Laravel preset)
npx prettier --write .     # JS/Vue/TypeScript formatting

# CI gates (block merge):
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=5
npx tsc --noEmit
npm run lint               # ESLint Vue 3 + TypeScript
php artisan test           # Pest full suite
```

---

### Data Model (ADR-036 đến ADR-045)

#### ADR-036: Template Structure — Normalized Tables + Validation Before Publish

**Schema:**
```sql
process_templates: id, name, description, created_by, is_published,
                   published_at, version, deleted_at

step_definitions:  id, template_id, name, description, order,
                   assignee_type (enum: user|role|department),
                   assignee_id (nullable), duration_hours (NOT NULL DEFAULT 24),
                   is_required, config_data (json), deleted_at
```

**Publish flow:** Draft → PublishTemplateValidator → Preview → Confirm → Published

**PublishTemplateValidator checks:**
- Mọi step phải có `duration_hours` (NOT NULL DEFAULT 24)
- Mọi step phải có `assignee_type`
- Template phải có ít nhất 1 step

---

#### ADR-037: Instance Tracking — step_executions Table + Dry-run Feature

**Schema:**
```sql
process_instances:  id, template_id, template_snapshot_data (json),
                    name, status, launched_by, launched_at, completed_at,
                    cancelled_at, deleted_at

step_executions:    id, instance_id, step_definition_id (nullable),
                    step_snapshot_data (json), name, order, status,
                    assigned_to (nullable), started_at, deadline_at,
                    completed_at, completion_notes, completed_by,
                    deadline_notified_at
```

**Dry-run / Test Instance:** status='test', không trigger notifications,
không hiện trên dashboard, Prunable sau 24h.

---

#### ADR-038: User/Role Model — Standard + Deactivation Handler

```sql
users: id, full_name, email, password, is_active (default true),
       last_login_at, deleted_at
```

**UserDeactivating event → ReassignOpenStepsOnUserDeactivation listener:**
- Query `step_executions WHERE assigned_to = $user->id AND status IN ('pending','in_progress')`
- Set `assigned_to = null, status = 'pending'`
- Notify Manager: "X steps cần reassign sau khi user bị deactivate"

---

#### ADR-039: Notifications — Laravel DB Notifications + step_messages

```sql
-- notifications: Laravel default table (id, type, notifiable_*, data, read_at)

step_messages: id, step_execution_id, sender_id, recipient_id,
               content, read_at, created_at
```

---

#### ADR-040: Attachments — Dedicated step_attachments Table

```sql
step_attachments: id, step_execution_id, uploaded_by, original_filename,
                  stored_path, mime_type, size_bytes, created_at
```

---

#### ADR-041: Migration Naming — Semantic + Atomic

```
000001-000002: users foundation
000010-000013: core domain (templates → instances → executions)
000020-000021: supporting tables (messages, attachments)
000030-000031: indexes (separate migration)
```

---

#### ADR-042: Indexes — Requirements-driven (7 Composite Indexes)

```php
// step_executions (most queried table):
['status', 'deadline_at']                          // Manager dashboard
['assigned_to', 'status', 'deadline_at']           // Executor inbox
['instance_id', 'order']                           // Instance detail
['deadline_at', 'deadline_notified_at', 'status']  // Scheduler query

// process_instances:
['status', 'launched_at']    // Dashboard overview
['launched_by', 'status']    // My instances filter

// step_messages:
['recipient_id', 'read_at']  // Unread count
```

**Mandatory eager loading rule:** Mọi Inertia controller load collection
phải có explicit `with()`. PR checklist item: "eager loading verified".

---

#### ADR-043: DB Constraints — FK + Unique, Check Constraints Defer

```php
// Foreign keys:
template_id:   restrictOnDelete  // không xóa template có instances
instance_id:   cascadeOnDelete   // xóa instance → xóa step_executions
assigned_to:   nullOnDelete      // user deactivate → step unassigned

// Unique constraints:
['template_id', 'order']   // step order unique trong template
['instance_id', 'order']   // step execution order unique

// Soft deletes: process_templates, process_instances, users, step_definitions
// No soft deletes: step_executions, step_messages, step_attachments
```

---

#### ADR-044: Seeders — Required (All Envs) vs Demo (Dev/Staging)

```
RequiredDataSeeder (always):
  PermissionsSeeder → RolesSeeder → AdminUserSeeder (from .env)

DemoDataSeeder (dev/staging only):
  SampleTemplatesSeeder → SampleUsersSeeder → SampleInstancesSeeder
```

---

#### ADR-045: Data Retention — Entity-specific via Prunable

```
Indefinite: process_instances, process_templates, users (compliance)
365 days:   step_messages (Prunable)
90 days read: notifications (Prunable, read_at IS NOT NULL)
Cascade:    step_attachments follow instance lifecycle
```

---

## Implementation Patterns & Consistency Rules

**Potential conflict points identified:** 6 areas where AI agents
could make incompatible choices.

---

### Naming Patterns

#### Route Naming (Laravel web.php)

Tất cả routes dùng **Laravel resource convention** + kebab-case URL:

```php
// ĐÚNG: resource routes
Route::resource('process-templates', ProcessTemplateController::class);
Route::resource('process-instances', ProcessInstanceController::class);
Route::resource('step-executions', StepExecutionController::class);

// ĐÚNG: custom actions dùng {resource}.{verb}
Route::post('process-instances/{instance}/launch', [...])->name('process-instances.launch');
Route::post('step-executions/{step}/complete', [...])->name('step-executions.complete');
Route::post('step-executions/{step}/escalate', [...])->name('step-executions.escalate');

// SAI
Route::post('launch-process', ...);  // ❌ verb trong resource name
Route::post('complete-step', ...);   // ❌
```

#### Inertia Component → Route Mapping

```
route name prefix    → pages/ subfolder
process-templates.*  → pages/Templates/
process-instances.*  → pages/Instances/
step-executions.*    → pages/StepExecutions/
dashboard            → pages/Dashboard/
executor-inbox       → pages/Executor/
auth.*               → pages/Auth/
```

---

### Inertia Controller → Page Props Pattern

**API Resource bắt buộc** cho mọi Eloquent model trả về Inertia:

```php
// ĐÚNG: flat props, API Resource, explicit can[] per page
public function show(ProcessInstance $instance): Response
{
    return Inertia::render('Instances/Show', [
        'instance' => ProcessInstanceResource::make($instance->load('stepExecutions')),
        'can' => [
            'cancel' => auth()->user()->can('cancel', $instance),
            'pause'  => auth()->user()->can('pause', $instance),
        ],
    ]);
}

// SAI
return Inertia::render('Instances/Show', [
    'data' => ['instance' => $instance],  // ❌ nested data key
    'instance' => $instance,              // ❌ raw Eloquent model
]);
```

**Resources location:** `app/Http/Resources/` —
ProcessTemplateResource, ProcessInstanceResource, StepExecutionResource, UserResource

---

### Vue Component Patterns

#### defineProps với TypeScript

```typescript
// ĐÚNG: TypeScript generic
interface Props {
  instance: ProcessInstance
  can?: { cancel: boolean; pause: boolean }
}
const props = withDefaults(defineProps<Props>(), {
  can: () => ({ cancel: false, pause: false }),
})

// SAI: runtime declaration
defineProps({ instance: Object })  // ❌ not typed
```

#### defineEmits với TypeScript

```typescript
// ĐÚNG: typed, kebab-case, past tense
const emit = defineEmits<{
  'step-completed': [stepId: number]
  'instance-cancelled': [instanceId: number]
  'update:modelValue': [value: string]
}>()

// SAI
const emit = defineEmits(['stepCompleted'])  // ❌ camelCase, untyped
```

---

### Form Request Pattern

```php
class CompleteStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        // LUÔN delegate sang Policy
        return $this->user()->can('complete', $this->route('step'));
    }

    public function rules(): array
    {
        return [
            'completion_notes' => ['nullable', 'string', 'max:2000'],
            'attachments'      => ['nullable', 'array', 'max:5'],
            'attachments.*'    => ['file', 'max:10240'],
        ];
    }
}

// SAI
public function authorize(): bool {
    return $this->user()->role === 'executor';  // ❌ không dùng Policy
}
```

---

### Inertia Redirect Patterns

```php
// Sau action thành công → redirect về trang liên quan
return redirect()->route('process-instances.show', $instance)
    ->with('success', 'Quy trình đã được khởi động.');

// Action trên trang hiện tại → redirect back
return redirect()->back()->with('success', 'Bước đã hoàn thành.');

// SAI
return response()->json(['success' => true]);        // ❌ không dùng JSON
return redirect()->route('process-instances.index'); // ❌ không về index sau mọi action
```

**Flash message keys:** `success` | `error` | `warning`
Không dùng: `message`, `status`, `info`

---

### Composable Return Pattern

```typescript
// ĐÚNG: trả về object (destructure theo tên)
export function usePermission() {
  const { props } = usePage<SharedProps>()
  return {
    can: (permission: keyof SharedProps['auth']['can']) =>
      props.auth.can[permission] ?? false,
  }
}
const { can } = usePermission()

// SAI: trả về array
export function usePermission() {
  return [can, permissions]  // ❌ dễ nhầm thứ tự
}
```

---

### Enforcement Summary

**AI Agents PHẢI:**
- Dùng API Resources cho mọi Eloquent model → Inertia props
- Dùng Policy trong Form Request `authorize()`, không viết logic trực tiếp
- TypeScript generic syntax cho `defineProps` và `defineEmits`
- Event Vue: kebab-case, past tense (`step-completed`, không phải `stepCompleted`)
- Flash keys: `success`, `error`, `warning` (không thêm key khác)
- Route names: Laravel resource convention `{resource}.{action}`
- Composables trả về object, không array

**AI Agents KHÔNG ĐƯỢC:**
- Trả raw Eloquent model qua Inertia (phải qua Resource)
- Viết authorization logic trực tiếp trong Form Request
- Return JSON response từ Inertia controllers
- Nest props trong key `data` khi truyền Inertia

---

## Project Structure & Boundaries

### Complete Project Directory Structure

```
workflow/
├── .github/
│   └── workflows/
│       └── ci.yml                    # Pest + Pint + Larastan + Vitest + tsc
├── app/
│   ├── Actions/                      # ADR-017: Single-action Invokables
│   │   ├── Process/
│   │   │   ├── LaunchProcessInstance.php
│   │   │   ├── CancelProcessInstance.php
│   │   │   └── PauseProcessInstance.php
│   │   ├── Step/
│   │   │   ├── CompleteStepExecution.php
│   │   │   ├── EscalateStep.php
│   │   │   ├── ReassignStep.php
│   │   │   └── SkipStep.php
│   │   ├── Template/
│   │   │   ├── PublishTemplate.php
│   │   │   ├── ArchiveTemplate.php
│   │   │   └── DuplicateTemplate.php
│   │   └── User/
│   │       ├── DeactivateUser.php
│   │       └── InviteUser.php
│   ├── Console/
│   │   └── Commands/
│   │       ├── CheckStateConsistency.php
│   │       └── CheckFailedJobs.php
│   ├── Events/                       # ADR-018: NounVerbed naming
│   │   ├── StepCompleted.php
│   │   ├── StepEscalated.php
│   │   ├── StepReassigned.php
│   │   ├── ProcessLaunched.php
│   │   ├── ProcessCompleted.php
│   │   ├── ProcessCancelled.php
│   │   └── DeadlineApproached.php
│   ├── Exceptions/                   # ADR-033
│   │   ├── DomainException.php
│   │   ├── InvalidStateTransitionException.php
│   │   ├── InsufficientPermissionException.php
│   │   └── TemplateNotPublishedException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── ProcessTemplateController.php
│   │   │   ├── StepDefinitionController.php
│   │   │   ├── ProcessInstanceController.php
│   │   │   ├── StepExecutionController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ExecutorInboxController.php
│   │   │   ├── BeneficiaryController.php
│   │   │   ├── StepMessageController.php
│   │   │   └── Admin/
│   │   │       ├── UserController.php
│   │   │       └── SystemController.php
│   │   ├── Middleware/
│   │   │   ├── HandleInertiaRequests.php
│   │   │   └── SecurityHeaders.php
│   │   ├── Requests/
│   │   │   ├── Template/
│   │   │   │   ├── StoreTemplateRequest.php
│   │   │   │   └── PublishTemplateRequest.php
│   │   │   ├── Process/
│   │   │   │   └── LaunchProcessRequest.php
│   │   │   └── Step/
│   │   │       ├── CompleteStepRequest.php
│   │   │       └── EscalateStepRequest.php
│   │   └── Resources/
│   │       ├── ProcessTemplateResource.php
│   │       ├── StepDefinitionResource.php
│   │       ├── ProcessInstanceResource.php
│   │       ├── StepExecutionResource.php
│   │       ├── StepMessageResource.php
│   │       └── UserResource.php
│   ├── Jobs/
│   │   ├── CheckApproachingDeadlines.php
│   │   ├── CheckStateConsistency.php
│   │   └── CheckFailedJobs.php
│   ├── Listeners/                    # ADR-018: VerbNounOnEvent
│   │   ├── LogStepCompletionOnStepCompleted.php      # sync
│   │   ├── SendNotificationOnStepCompleted.php       # queued:notifications
│   │   ├── CheckProcessCompletionOnStepCompleted.php # queued:default
│   │   ├── BroadcastStepUpdateOnStepCompleted.php    # queued:broadcasts
│   │   ├── NotifyManagerOnStepEscalated.php
│   │   ├── BroadcastOnProcessLaunched.php
│   │   ├── NotifyManagerOnProcessCompleted.php
│   │   └── ReassignOpenStepsOnUserDeactivating.php
│   ├── Models/
│   │   ├── ProcessTemplate.php       # LogsActivity, SoftDeletes
│   │   ├── StepDefinition.php        # SoftDeletes
│   │   ├── ProcessInstance.php       # HasStates, LogsActivity, SoftDeletes
│   │   ├── StepExecution.php         # HasStates, LogsActivity
│   │   ├── StepMessage.php           # Prunable (365 days)
│   │   ├── StepAttachment.php
│   │   └── User.php                  # HasRoles, SoftDeletes
│   ├── Notifications/
│   │   ├── StepAssignedNotification.php
│   │   ├── DeadlineApproachingNotification.php
│   │   ├── StepEscalatedNotification.php
│   │   └── ProcessCompletedNotification.php
│   ├── Policies/
│   │   ├── ProcessTemplatePolicy.php
│   │   ├── ProcessInstancePolicy.php
│   │   ├── StepExecutionPolicy.php
│   │   └── UserPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── States/                       # ADR-016: spatie/model-states
│       ├── ProcessInstance/
│       │   ├── PendingState.php
│       │   ├── RunningState.php
│       │   ├── PausedState.php
│       │   ├── CompletedState.php
│       │   └── CancelledState.php
│       └── StepExecution/
│           ├── PendingState.php
│           ├── InProgressState.php
│           ├── CompletedState.php
│           ├── BlockedState.php
│           ├── EscalatedState.php
│           └── SkippedState.php
├── database/
│   ├── migrations/
│   │   ├── ..._000001_create_users_table.php
│   │   ├── ..._000002_add_profile_fields_to_users_table.php
│   │   ├── ..._000010_create_process_templates_table.php
│   │   ├── ..._000011_create_step_definitions_table.php
│   │   ├── ..._000012_create_process_instances_table.php
│   │   ├── ..._000013_create_step_executions_table.php
│   │   ├── ..._000020_create_step_messages_table.php
│   │   ├── ..._000021_create_step_attachments_table.php
│   │   ├── ..._000030_add_indexes_to_step_executions_table.php
│   │   └── ..._000031_add_indexes_to_process_instances_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── PermissionsSeeder.php
│       ├── RolesSeeder.php
│       ├── AdminUserSeeder.php
│       └── demo/
│           ├── SampleTemplatesSeeder.php
│           ├── SampleUsersSeeder.php
│           └── SampleInstancesSeeder.php
├── resources/
│   └── js/
│       ├── app.ts
│       ├── bootstrap.ts              # Echo + Axios
│       ├── components/
│       │   ├── ui/                   # shadcn-vue (do not edit)
│       │   ├── shared/
│       │   │   ├── ProcessStatusBadge.vue
│       │   │   ├── StepStatusBadge.vue
│       │   │   ├── DeadlineCountdown.vue
│       │   │   ├── UserAvatar.vue
│       │   │   └── ConfirmDialog.vue
│       │   ├── dashboard/
│       │   │   ├── ProcessInstanceRow.vue
│       │   │   ├── OverdueStepAlert.vue
│       │   │   └── DashboardFilters.vue
│       │   ├── template-builder/
│       │   │   ├── StepCard.vue
│       │   │   ├── StepForm.vue
│       │   │   └── TemplatePreview.vue
│       │   └── executor/
│       │       ├── TaskCard.vue
│       │       └── CompletionForm.vue
│       ├── composables/
│       │   ├── useEcho.ts
│       │   ├── usePermission.ts
│       │   └── useDeadlineFormat.ts
│       ├── layouts/
│       │   ├── AppLayout.vue
│       │   └── AuthLayout.vue
│       ├── lib/
│       │   └── utils.ts
│       ├── pages/
│       │   ├── Auth/
│       │   │   ├── Login.vue
│       │   │   ├── Register.vue
│       │   │   └── ForgotPassword.vue
│       │   ├── Dashboard/
│       │   │   └── Index.vue
│       │   ├── Templates/
│       │   │   ├── Index.vue
│       │   │   ├── Show.vue
│       │   │   ├── Create.vue
│       │   │   └── Edit.vue
│       │   ├── Instances/
│       │   │   ├── Index.vue
│       │   │   ├── Show.vue
│       │   │   └── Create.vue
│       │   ├── StepExecutions/
│       │   │   └── Show.vue
│       │   ├── Executor/
│       │   │   └── Index.vue
│       │   ├── Beneficiary/
│       │   │   └── Show.vue
│       │   └── Admin/
│       │       ├── Users/
│       │       │   ├── Index.vue
│       │       │   └── Show.vue
│       │       └── System/
│       │           └── Index.vue
│       ├── stores/
│       │   ├── notification.ts
│       │   └── ui.ts
│       └── types/
│           ├── index.d.ts
│           └── inertia.d.ts
├── routes/
│   ├── web.php
│   ├── channels.php
│   └── console.php
├── tests/
│   ├── Feature/
│   │   ├── Auth/
│   │   ├── Template/
│   │   ├── Process/
│   │   ├── Notification/
│   │   └── Security/               # unauthorized access tests
│   └── Unit/
│       ├── States/
│       └── Actions/
├── docker-compose.yml
├── docker-compose.override.yml
├── docker-compose.prod.yml
├── Dockerfile
├── deploy.sh
├── backup.sh
├── redis.conf
├── .env.example
├── phpstan.neon
├── pint.json
├── vite.config.ts
├── tailwind.config.ts
└── tsconfig.json
```

### Architectural Boundaries

**Request → Response Flow:**
```
Browser → routes/web.php → Controller → authorize(Policy)
       → validate(FormRequest) → Action → Event → Listeners(queued)
       → Inertia::render(Page, [Resource::make(Model)]) → Vue
```

**Real-time Flow:**
```
Listener(queued:broadcasts) → BroadcastEvent → Reverb
→ Echo.private(channel) → useEcho() composable → Vue reactive update
```

**Background Jobs Flow:**
```
routes/console.php → Job::dispatch() → Redis 'default' queue
→ queue-worker → Notification(queued:notifications) → SMTP
```

### Requirements to Structure Mapping

| FR Group | Controllers | Actions | Pages |
|---|---|---|---|
| FR1-6 Template Mgmt | ProcessTemplateController, StepDefinitionController | Template/* | Templates/* |
| FR7-15 Instance Exec | ProcessInstanceController, StepExecutionController | Process/*, Step/* | Instances/*, StepExecutions/* |
| FR16-19 Dashboard | DashboardController | — | Dashboard/Index |
| FR20-23 Executor | ExecutorInboxController | Step/Complete, Step/Escalate | Executor/Index |
| FR24-26 Beneficiary | BeneficiaryController | — | Beneficiary/Show |
| FR27-32 Notifications | — (via Events) | — | — |
| FR33-37 Admin | Admin/UserController | User/* | Admin/* |

### Cross-Cutting Concerns Location

```
Authentication:   app/Http/Middleware/ + routes/web.php
RBAC:             app/Policies/ + app/Http/Requests/authorize()
Audit Log:        app/Models/ (LogsActivity) + app/Listeners/ (manual)
Real-time:        app/Events/ + app/Listeners/ + routes/channels.php
Scheduling:       routes/console.php + app/Jobs/Check*.php
Error Handling:   app/Exceptions/ + bootstrap/app.php
Shared UI State:  resources/js/stores/
Type Safety:      resources/js/types/ + app/Http/Resources/
```

---

## Architecture Validation Results

### Coherence Validation ✅ PASSED

Tất cả 45 ADRs tương thích với nhau. Không có quyết định mâu thuẫn.
Minor note: activitylog sync adds DB write per request — acceptable ở 100 users,
cần revisit nếu scale >500 users.

### Requirements Coverage ✅ PASSED

| FR Group | Status | Coverage |
|---|---|---|
| FR1-6 Template Mgmt | ✅ | ProcessTemplateController + PublishTemplateValidator + ADR-036 |
| FR7-15 Instance Exec + Audit | ✅ | Actions/Process + state machine + activitylog sync |
| FR16-19 Manager Dashboard | ✅ | DashboardController + Reverb real-time + composite indexes |
| FR20-23 Executor Task Mgmt | ✅ | ExecutorInboxController + CompleteStepExecution |
| FR24-26 Beneficiary Interface | ✅ | BeneficiaryController + Policy (own instances only) |
| FR27-32 Notification & Comms | ✅ | Queued Mailables + scheduler everyMinute + Listeners |
| FR33-37 System Admin | ✅ | Admin controllers + DeactivateUser + RBAC |

| NFR | Status | Architectural Support |
|---|---|---|
| NFR1 Dashboard < 3s | ✅ | Eager loading rule + composite indexes + FPM tuning |
| NFR2 Notification ≤ 60s | ✅ | Redis 'broadcasts' queue (highest priority) + scheduler |
| NFR3 Uptime 08:00-18:00 | ✅ | restart:unless-stopped + maintenance window deploy |
| NFR4 Backup/restore | ✅ | backup.sh (DB + storage volume, 30 days retention) |
| NFR5 RBAC + audit log | ✅ | spatie/permission + Policies + activitylog sync |
| NFR6 On-premise data | ✅ | Docker self-hosted + local storage + named volumes |
| NFR8 Install < 1 hour | ✅ | docker compose up + .env.example + RequiredDataSeeder |
| NFR9 Docker packaging | ✅ | Multi-stage Dockerfile + split compose files |

### Gap Analysis

**Critical Gaps: Không có** — Mọi FR và NFR đều được cover.

**Deferred (post-MVP, không block implementation):**
- Telegram notification channel (UX spec mention, ngoài MVP FR scope)
- Google Sheet integration (UX spec mention, ngoài MVP FR scope)
- CSP headers (defer until production stable)
- Playwright E2E tests (defer until core flows stable)

**Action required trước implementation:**
FR36 Beneficiary auto-account: thêm logic vào `LaunchProcessInstance` action —
nếu beneficiary email chưa có tài khoản → tạo User role 'beneficiary'
+ dispatch `InviteUser` + send invitation email.

### Architecture Completeness Checklist

- [x] Project context analyzed (37 FRs, 9 NFRs, 8-10 components)
- [x] Technical constraints identified (single-tenant, Docker, Shadcn/ui)
- [x] Cross-cutting concerns mapped (Auth, Audit, Real-time, Scheduler, Versioning)
- [x] Starter template selected (Laravel Vue Starter Kit)
- [x] 45 Architecture Decision Records documented
- [x] 14 Pre-mortem amendments integrated (Amendments A-N)
- [x] Implementation patterns defined (6 conflict points resolved)
- [x] Project structure complete (full directory tree, 7 FR domains mapped)
- [x] Requirements → structure mapping complete
- [x] Coherence validated, no contradictions found
- [x] All NFRs architecturally supported

### Architecture Readiness Assessment

**Status: READY FOR IMPLEMENTATION**

**Confidence Level: HIGH**

**Key Strengths:**
- First-party Laravel ecosystem — minimal external dependencies
- Actions pattern → business logic reusable, testable, API-ready
- spatie ecosystem (permission, model-states, activitylog) — proven, maintained
- Pre-mortem analysis caught 12 critical failure modes trước implementation
- Security-first: Policy layer mandatory, activitylog sync, named Docker volumes

**Areas for Future Enhancement:**
- Telegram notification channel (post-MVP)
- Google Sheet export (post-MVP)
- CSP headers configuration
- Laravel Horizon for queue monitoring
- Playwright E2E tests

### Implementation Handoff

**First Story — Project Initialization:**
```bash
laravel new workflow --using=vue
# TypeScript: Yes | Inertia SSR: No | Testing: Pest
composer require spatie/laravel-permission spatie/laravel-activitylog \
  spatie/laravel-model-states laravel/reverb
npm install -D vitest @vue/test-utils
```

**Suggested Implementation Order:**
1. Docker Compose setup + .env configuration
2. Database migrations (000001 → 000031) + Seeders
3. Auth + RBAC + HandleInertiaRequests
4. Process Template CRUD (FR1-6)
5. Process Instance Launch + Step Execution (FR7-15)
6. Manager Dashboard (FR16-19)
7. Executor Inbox (FR20-23)
8. Notification System (FR27-32)
9. Reverb real-time integration
10. Beneficiary Interface (FR24-26)
11. System Administration (FR33-37)

---

## Infrastructure Summary

```
┌──────────────────────────────────────────────────────────────┐
│  FRONTEND                                                     │
│  Vue 3 + TypeScript + Inertia 2 + shadcn-vue + Tailwind      │
│  Pinia (ui, notification) | useForm + VeeValidate+Zod        │
│  useEcho() composable → Reverb private RBAC channels         │
│  Ziggy routes | Manual TypeScript types                      │
├──────────────────────────────────────────────────────────────┤
│  BACKEND                                                      │
│  Laravel 12 — Actions pattern | Events + Queued Listeners    │
│  spatie/model-states | spatie/permission + Policies          │
│  spatie/activitylog (SYNC) | PublishTemplateValidator        │
│  HandleInertiaRequests (shared auth.can permissions)         │
├────────────────┬─────────────────┬───────────────────────────┤
│  Reverb :8080  │  Queue (Redis)  │  Scheduler (everyMinute)  │
│  Private RBAC  │  broadcasts     │  CheckDeadlines           │
│  channels      │  notifications  │  CheckStateConsistency    │
│                │  default        │  CheckFailedJobs (hourly) │
├────────────────┴─────────────────┴───────────────────────────┤
│  PostgreSQL 16     │  Redis 7 (appendonly)  │  Local Storage  │
│  (named volume)    │  (named volume)        │  (named volume) │
└────────────────────┴────────────────────────┴────────────────┘

Docker: split compose + multi-stage + named volumes + restart:unless-stopped
Deploy: maintenance window + CI migration gate + health checks post-deploy
Test:   Pest feature (+ security tests) > Vitest > unit | Telescope dev
Quality: Pint + Prettier (pre-commit) + Larastan 5 + tsc + CI gates
```
