## Deferred from: code review of 1-4-admin-user-account-management (2026-04-16)

- D1→reactivate() inline pattern: không có UserReactivated event, inconsistent với deactivate(). Thêm ReactivateUser action + event khi có listener cần thiết (Story 3.x+).
- D2→Schema::hasTable() DDL per-event: mỗi lần UserDeactivated fire đều query DB metadata. Chấp nhận ở MVP; optimize với config flag khi step_executions table tồn tại.
- D3→Pagination last-page redirect: deactivate user trên last page → "Page N of N-1". Fix redirect sang page hợp lệ khi build pagination UX polish.
- D4→Race condition deactivate+assign: concurrent deactivate + assign-designer để user inactive có elevated role. Cần transaction hoặc state check khi có concurrent admin feature.
- D5→Soft-deleted email block: unique:users,email block email của soft-deleted user. Fix: Rule::unique()->whereNull('deleted_at') khi có delete UI.
- D6→Missing creation audit: không có activity log khi tạo user mới. Thêm activity log trong store() action để đồng nhất với các mutating operations.
- D7→Double-deactivate non-idempotent: deactivate user đã inactive re-fires event và log. Thêm early-exit nếu target đã inactive.

## Deferred from: code review of 1-3-role-based-access-control-5-roles-policy-layer (2026-04-16)

- D1→N+1 permission queries: 9× permission checks per request in HandleInertiaRequests without eager-load. Optimize with `$user->load('roles.permissions')` if Redis cache cold causes performance issue. Acceptable for MVP.
- D2→IP logging TrustProxies vulnerability: Unauthorized access logging uses `$request->ip()` which can be spoofed if TrustProxies misconfigured. Verify TrustProxies config at deploy time before production.
- D3→Soft-deleted user auth gap: No explicit integration test for soft-deleted user with valid session. Implement if session persistence layer evolves.
- D4→Mixed auth strategies: Some policies use permission-based checks, others use role-based. Both patterns work per tests; consistency is a design choice, not a bug.
- D5→Weak admin password default: Fallback password is `'changeme'` (weak). Acceptable for dev environments; ensure ADMIN_PASSWORD env var is set in production.
- D6→ProcessTemplatePolicy overly-permissive: Manager can view templates via `launch_instances` permission even though matrix doesn't grant `manage_templates`. Intentional — managers need template visibility to choose when launching instances.
- D7→Frontend-backend permission drift: AppServiceProvider gate checks role, but AppSidebar sidebar checks permission. They align because roles have matching permissions. Add comment to document this coupling assumption.
- D8→Cache driver test/prod mismatch: Tests use array cache for speed; production uses database cache. Acceptable trade-off; test behavior may differ from prod under concurrent requests.
- D9→Dashboard gate missing null user guard: Gate doesn't explicitly check for null user. Middleware should prevent unauthenticated access, but defensive null check possible.

## Deferred from: code review of 1-2-authentication-login-logout-session-security (2026-04-15)

- D1: `Fortify::authenticateUsing(app(...))` eager resolution tại boot — không thể swap trong test; chuyển sang closure hoặc class string khi cần testability.
- D2: 9 × `$user->can()` mỗi Inertia request — optimize bằng eager-load `$user->load('roles.permissions')` nếu Redis cache cold gây performance issue.
- D3: Thiếu test "soft-deleted user không thể login" — SoftDeletes scope xử lý implicitly; thêm explicit test khi viết auth regression suite.
- D4: `redirect()->intended()` có thể follow external URL — thêm same-origin validation nếu cần defense-in-depth.
- D5: Không có test assert GET /logout trả 405 — thêm nếu cần coverage đầy đủ.
- D6: Rate-limit IP spoofable nếu TrustProxies không config đúng — kiểm tra TrustProxies trước go-live (kế thừa từ story 1.1).
- D7: Functional test simulate session expiry chưa có — thêm vào test suite khi có session management integration tests.
- D8: `verified` middleware no-op — quyết định implement email verification flow hay bỏ middleware, tách ra story riêng.
- D9: ADR-026 (no Sanctum) — verify bằng audit `routes/api.php` và middleware stack khi codebase lớn hơn.
- D10: ADR-010 (Redis caching permissions) — thêm query count assertion trong SessionTest để verify 0 DB queries cho permission checks.
- D11: ADR-019 (Policy authoritative) — implement Policy classes cho từng resource trong các Epic tiếp theo.

## Deferred from: code review of 1-1-initialize-project-configure-development-infrastructure (2026-04-12)

- Rate Limiter Guest sharing: Guest users share the same rate limit if they are behind a proxy, as it defaults to `$req->ip()`. This is acceptable for MVP but should be addressed for high-traffic environments.

## Deferred from: code review of 2-3-edit-existing-template.md (2026-05-14)
- Update method consistency: Show.vue uses .patch while some projects prefer .put.
- Complex JSON casting: Future proofing for template_snapshot_data in ProcessInstance.
- Test suite performance: Optimization of RequiredDataSeeder usage in ProcessTemplateEditTest.php.

## Deferred from: code review of 3-1-launch-process-instance.md (2026-05-15)
- Static Versioning — Template version field is never incremented. Pre-existing/Future concern.
- Template Deletion Protection — Error message claims 'running' processes but blocks if *any* history exists. Pre-existing issue.

## Deferred from: code review of 3-2-executor-acknowledge-complete-step.md (2026-05-15)
- Role-Coupled Authorization Flaw — ProcessInstancePolicy restricts view based on role, not just assignment. Pre-existing.

## Deferred from: code review of 3-4-manager-override-step-cancel-instance.md (2026-05-15)
- Double Update/Missing Transaction Lock in Override — `OverrideStep` mutates state then transitions without explicit locking, though `transitionTo` handles the save. Potential future refactor to explicit `save()` if Spatie library changes.

## Deferred from: code review of 3-5-activity-log-full-audit-trail.md (2026-05-15)
- Hardcoded Translations — `ActivityResource` hardcodes Vietnamese strings instead of using Laravel's localization system.
- Fat Controller — The polymorphic ORM query in `ProcessInstanceController` should be extracted to a dedicated Service or Action class.
- Scale & Performance Risks — Missing pagination for activity logs, which could degrade performance for long-running processes.

## Deferred from: code review of 3-2-executor-acknowledge-complete-step.md (2026-05-16)
- StepExecutionPolicy completeness — Policy only checks assigned_to for acknowledge/complete. Does not account for instance status (Cancelled/Paused) or upcoming Manager overrides. — để mặc định

## Deferred from: code review of 4-2-filter-search-instances.md (2026-05-16)
- Thiếu cấu hình Pinia — Dù đã cài pinia, hệ thống vẫn dùng @vueuse/core để quản lý state. Không cấu hình Pinia trong app.ts. — VueUse is sufficient for MVP
- Load toàn bộ Select Options bằng get() và thiếu Pagination — acceptable for MVP scale
- Hardcode namespace backend và text tiếng Việt ở Frontend — pre-existing convention
- Rủi ro Full table scan do LOWER() LIKE — implemented to support SQLite tests

## Deferred from: code review of 4-4-real-time-dashboard-instance-updates-via-websocket.md (2026-05-16)
- Phụ thuộc Client Time — Tính toán isOverdue dựa trên client time thay vì server time. — acceptable for MVP
- Thiếu context_data trong UI — pre-existing scope của story 3.2/4.3. — pre-existing

## Deferred from: code review of 5-1-executor-inbox-consolidated-sorted-task-list.md (2026-05-16)
- Logic tính toán Urgency Status (sai lệch mốc thời gian) — Dùng created_at thay vì lúc được gán (started_at). — acceptable for MVP
- Lỗi logic đếm thời gian quy trình bị hủy — Dùng now() nếu completed_at null. — acceptable for MVP
- DashboardController Bypass Policy — Sử dụng hasRole thay vì authorize. Thuộc phạm vi story 4.1. — pre-existing
