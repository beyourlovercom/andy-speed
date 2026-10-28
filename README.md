# Andy Speed

Independent, opt-in WordPress performance auditing and release gate.

**Current default:** read-only; **all optimization switches OFF**. The first integration applies only to the Andy Core *analytics diagnostics* script when explicitly enabled in Safe mode. No checkout, payment, caching, fonts, product assets, GTM injection, or database migration operations.

Implementation is isolated behind feature gates and GitHub PR reviews.


## v0.2.1 — Opt-in BYL V3 immutable CSS adapter

On BYL Dev only, six currently deployed source CSS hashes must match the manifest
before Andy Speed substitutes one bundled CSS request. This adapter lives in
Andy Speed to avoid replacing concurrent Dev Theme/Commerce/Currency changes.

- Default Audit / Emergency OFF; fail-closed on any CSS changes.
- Preserves all six historical WP style handles and dependency order.
- Bundled font paths are relative to wp-content and validated by CI.
- No checkout, payment, price, cookie, cache, or product writes.
- Emergency OFF restores original request URLs on the next frontend request.
- Rebuild from verified site source and re-UAT when a source changes.

This adapter is explicitly not a universal CSS minifier or caching feature.
