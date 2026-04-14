# Story 1.1: Initialize Project & Configure Development Infrastructure

Status: done

## Story

As a developer,
I want the project initialized with the correct Laravel Vue Starter Kit, Docker infrastructure, CI pipeline, and design tokens,
so that the entire team has a consistent, reproducible development and deployment environment from day one, and all subsequent stories can be built on a solid foundation.

## Acceptance Criteria

**AC1 — Docker dev environment starts successfully:**
- Given: a clean machine với Docker và Docker Compose installed
- When: developer chạy `docker compose up -d` (dùng base + override)
- Then: tất cả containers khởi động thành công: `app` (PHP-FPM), `postgres`, `redis`, `queue-broadcasts`, `queue-default`, `scheduler`, `mailpit`
- And: app truy cập được tại `http://localhost` hiển thị Laravel welcome/login page

**AC2 — CI pipeline passes on first push:**
- Given: developer push code lên repository
- When: CI workflow chạy
- Then: Pint (PHP formatting), Larastan level 5 (static analysis), tsc --noEmit (TypeScript), ESLint (Vue/TS), và Pest (full test suite) đều pass không có lỗi

**AC3 — Tailwind design tokens configured:**
- Given: project được khởi tạo
- When: developer mở `tailwind.config.js` (hoặc `tailwind.config.ts`)
- Then: semantic color tokens được định nghĩa: `primary`, `muted`, `destructive`, `warning`, `success`
- And: typography font (Inter hoặc Be Vietnam Pro) được cấu hình

**AC4 — Named volumes persist across container restarts:**
- Given: production Docker Compose (`docker-compose.yml` base) được sử dụng
- When: containers restart sau sự cố hoặc `docker compose down && up`
- Then: named volumes `postgres_data`, `redis_data`, `storage_data` giữ nguyên dữ liệu
- And: `queue-broadcasts`, `queue-default`, `scheduler` tự khởi động lại nhờ `restart: unless-stopped`

**AC5 — Security headers present trên mọi response:**
- Given: bất kỳ HTTP response nào từ app
- When: browser nhận được response
- Then: security headers có mặt: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`

**AC6 — Rate limiters configured:**
- Given: `AppServiceProvider` hoặc `bootstrap/app.php` được load
- When: ứng dụng khởi động
- Then: 3 rate limiters được đăng ký: `auth` (5/min per IP), `notifications` (10/min per user), `uploads` (20/min per user)

**AC7 — Required seeders chạy được ở mọi environment:**
- Given: database được reset hoặc migrate fresh
- When: `php artisan db:seed --class=RequiredDataSeeder` được chạy
- Then: `PermissionsSeeder → RolesSeeder → AdminUserSeeder` chạy theo đúng thứ tự mà không có lỗi
- And: admin user được tạo với credentials từ `.env`

**AC8 — Deploy script functional:**
- Given: `deploy.sh` script tồn tại ở project root
- When: script được chạy trong môi trường production
- Then: script thực hiện: pull images → migrate → cache configs/routes/views → start containers → health checks
- And: health checks kiểm tra: app `/up` endpoint, DB connection, storage volume mount

## Tasks / Subtasks

- [x] Task 1: Khởi tạo project với Laravel Vue Starter Kit (AC1)
  - [x] Chạy `laravel new workflow --using=vue` với: TypeScript=Yes, Inertia SSR=No, Testing=Pest
  - [x] Verify project structure khớp với architecture: `app/`, `resources/js/pages/`, `resources/js/components/ui/`, `resources/js/composables/`, `resources/js/layouts/`, `resources/js/stores/`, `resources/js/types/`
  - [x] Xóa sample pages/components không cần thiết từ starter (giữ auth scaffolding)
  - [x] Verify Pest đã configured (không phải PHPUnit)

- [x] Task 2: Cấu hình Docker infrastructure 3 files (AC1, AC4)
  - [x] Tạo `docker-compose.yml` (base/prod): services `app`, `postgres:16`, `redis:alpine`, `queue-broadcasts`, `queue-default`, `scheduler`, `nginx`; named volumes `postgres_data`, `redis_data`, `storage_data`; `restart: unless-stopped` cho workers và scheduler
  - [x] Tạo `docker-compose.override.yml` (dev): thêm `mailpit`, bind mounts cho hot reload (`./:/var/www/html`), expose ports dev-only
  - [x] Tạo `docker-compose.prod.yml` (prod): resource limits cho containers
  - [x] Tạo multi-stage `Dockerfile`: stage `builder` (composer + npm build) → stage `runtime` (PHP-FPM slim, không có dev tools)
  - [x] Configure PHP-FPM: `pm.max_children=20, pm.start_servers=5`
  - [x] Test: `docker compose up -d` → app accessible tại localhost

- [x] Task 3: Thiết lập CI pipeline (AC2)
  - [x] Tạo `.github/workflows/ci.yml` (hoặc equivalent CI config)
  - [x] Jobs: `pint --test`, `phpstan --level=5`, `tsc --noEmit`, `eslint`, `php artisan test`
  - [x] Configure fail-fast: nếu 1 job fail → pipeline dừng
  - [x] Add `.env.testing` với test database config (SQLite in-memory hoặc PostgreSQL test DB)

- [x] Task 4: Cấu hình Tailwind design tokens (AC3)
  - [x] Extend `tailwind.config.js` với semantic color system: `primary`, `muted`, `destructive`, `warning`, `success` (dùng CSS custom properties pattern của shadcn-vue)
  - [x] Configure typography: thêm `Inter` hoặc `Be Vietnam Pro` qua Google Fonts hoặc local font (ưu tiên `Be Vietnam Pro` cho thị trường Việt Nam)
  - [x] Verify shadcn-vue components sử dụng tokens (không hardcode colors)
  - [x] Test: run `npm run build` thành công

- [x] Task 5: Implement security headers middleware (AC5)
  - [x] Tạo `app/Http/Middleware/SecurityHeaders.php`
  - [x] Headers bắt buộc: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`
  - [x] Register middleware trong `bootstrap/app.php` (Laravel 12 middleware stack)
  - [x] Viết Pest feature test: assert headers present trên response

- [x] Task 6: Cấu hình rate limiters (AC6)
  - [x] Trong `AppServiceProvider::boot()` hoặc `bootstrap/app.php`: đăng ký 3 `RateLimiter::for()`: `auth` (5/min per IP), `notifications` (10/min per user), `uploads` (20/min per user)
  - [x] Apply `auth` rate limiter vào login route
  - [x] Viết test: verify rate limiter đã registered

- [x] Task 7: Tạo Required seeders (AC7)
  - [x] Cài đặt `spatie/laravel-permission` và publish config/migration
  - [x] Tạo `PermissionsSeeder`: tạo 9 permissions: `manage_templates`, `publish_templates`, `launch_instances`, `view_all_instances`, `manage_instances`, `complete_assigned_steps`, `view_own_instances`, `manage_users`, `manage_system`
  - [x] Tạo `RolesSeeder`: tạo 5 roles và gán permissions theo matrix (xem Dev Notes)
  - [x] Tạo `AdminUserSeeder`: tạo admin user từ `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME` trong `.env`
  - [x] Tạo `RequiredDataSeeder` orchestrator: gọi 3 seeders theo đúng thứ tự
  - [x] Update `users` migration: thêm `full_name`, `is_active` (default true), `last_login_at` nullable, giữ soft deletes
  - [x] Viết Pest test: seed → assert roles/permissions tồn tại, admin có thể login

- [x] Task 8: Tạo deploy.sh và backup.sh (AC8)
  - [x] Tạo `deploy.sh` với các bước: `docker compose pull` → `migrate --force` → `config:cache` → `route:cache` → `view:cache` → `permission:cache-reset` → `up -d` → health checks
  - [x] Health checks: `curl -sf http://localhost/up`, DB connection check, storage volume check
  - [x] Tạo `backup.sh`: pg_dump + tar storage volume + xóa backup cũ hơn 30 ngày
  - [x] Chmod +x cả 2 scripts
  - [x] Tạo `.env.example` đầy đủ với tất cả required vars (DB, Redis, SMTP, ADMIN_*, REVERB_*)

- [x] Task 9: Cấu hình Telescope (dev-only) và logging (AC không trực tiếp, nhưng ADR-014 bắt buộc)
  - [x] Cài đặt `laravel/telescope` với `--dev` flag
  - [x] Configure `TELESCOPE_ENABLED=false` trong `.env.example` production section
  - [x] Configure log channel: `LOG_CHANNEL=stack, LOG_STACK=daily,stderr` trong production
  - [x] Ensure Telescope disabled trong CI và production environments

## Dev Notes

[Omitted for brevity - preserved from original]

## Dev Agent Record

### Agent Model Used

Gemini 3.1 Pro (High)

### Debug Log References

- Tests failed initially because `full_name` was expected but test cases used `name` (following Fortify defaults). Replaced usages across `ProfileUpdateRequest`, `CreateNewUser`, and `ProfileUpdateTest`.
- Vite manifest error was caused by missing frontend build assets; resolved by running `npm install && npm run build`.

### Completion Notes List

- ✅ Khởi tạo project thành công với Laravel Vue Starter Kit.
- ✅ Cấu hình Docker infrastructure đầy đủ với 3 files và named volumes.
- ✅ Custom Github Actions CI pipeline đã được triển khai.
- ✅ Tailwind (v4) với design tokens đã được apply.
- ✅ Tests chạy passed 100% (53 tests).
- ✅ Đã tạo `deploy.sh` và `backup.sh` scripts.
- ✅ Story hoàn tất.

### File List

- deploy.sh
- backup.sh
- .env.example
- tests/Feature/Settings/SecurityTest.php
- tests/Feature/Settings/ProfileUpdateTest.php
- tests/Feature/Auth/RegistrationTest.php
- tests/Feature/Auth/PasswordConfirmationTest.php
- tests/Feature/Auth/TwoFactorChallengeTest.php
- app/Concerns/ProfileValidationRules.php
- app/Actions/Fortify/CreateNewUser.php
- tests/Pest.php

### Change Log

- 2026-04-12: Story file được tạo bởi create-story workflow (bmad-create-story)

### Review Findings

- [x] [Review][Decision] Health check URL in deploy.sh — `deploy.sh` checks `localhost/up`. Nếu script chạy bên ngoài docker network mà Nginx map ra port khác, check này sẽ fail.
- [x] [Review][Patch] Missing `pnpm-lock.yaml` in Dockerfile [Dockerfile:30]
- [x] [Review][Patch] Forbidden `env()` usage in Seeder/Test [database/seeders/AdminUserSeeder.php, tests/Feature/Infrastructure/SeederTest.php]
- [x] [Review][Patch] CI Pipeline Node/Pnpm dependency [.github/workflows/ci.yml]
- [x] [Review][Patch] ADR-011 Production targets missing [docker-compose.prod.yml]
- [x] [Review][Patch] Registration Payload inconsistency [tests/Feature/Auth/RegistrationTest.php]
- [x] [Review][Defer] Rate Limiter Guest sharing [app/Providers/AppServiceProvider.php] — deferred, pre-existing

