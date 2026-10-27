# Andy Speed — Agent governance

- Canonical source is this private GitHub repository. Never edit installed WordPress plugin source as the authority.
- Audit-only is the default. No automatic cache purging, CSS/JS broad rewrites, or cron workers.
- Performance optimizations must require an explicit per-site opt-in; include a global emergency OFF.
- Independent of Andy Core and Commerce; cooperation only through optional WordPress filters. No active cross-plugin hooks when OFF.
- Preserve dynamic commerce pages, payment, coupon, attribution, shipping, mail and customer flows.
- The admin UI is Desktop-only; the shopper-facing site must remain responsive and mobile-compatible.
- Work on branches and CI, Dev UAT before production. Production needs Owner explicit PASS; do not restart READY devices or Chrome.
