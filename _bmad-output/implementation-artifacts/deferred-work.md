## Deferred from: code review of 1-1-initialize-project-configure-development-infrastructure (2026-04-12)

- Rate Limiter Guest sharing: Guest users share the same rate limit if they are behind a proxy, as it defaults to `$req->ip()`. This is acceptable for MVP but should be addressed for high-traffic environments.
