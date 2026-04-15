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
