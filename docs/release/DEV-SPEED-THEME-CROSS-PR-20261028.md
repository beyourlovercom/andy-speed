# Andy Speed + BYL V3 — cross-PR integration and release boundary

**Date:** 2026-10-28. **Scope:** Actual isolated WordPress UAT on China Mac.
**PRs:** Andy Speed #1 v0.2.1 and BYL V3 Theme #165.
No Dev/WWW write, account/coupon/order/payment write, cache toggle or additional hosting cost.

## Four real WordPress CSS coexistence cases

Imported the Theme PR candidate asset registration hook, immutable CSS
and hash manifest temporarily into the existing isolated WP fixture.
The installed Andy Speed v0.2.1 supplied its real options and filter.
WP-CLI executed wp_enqueue_scripts and inspected the six WordPress
style handles and source URLs.

| Case | Stylesheet network URLs | Result |
|---|---:|---|
| Existing Dev CSS SHA; Speed Safe CSS ON | 1 Speed bundle | PASS |
| Theme PR CSS SHA; Speed Safe CSS ON | 1 Theme bundle | PASS |
| Theme PR CSS SHA; Speed Emergency OFF | 6 original stylesheets | PASS |
| Deliberate source hash drift; Speed CSS ON | 6 original stylesheets | PASS |

All six expected WordPress style handles remained enqueued.
No duplicate bundle, missing dependency or emergency rollback failure.
All temporarily edited fixture CSS, Theme hook, manifest and options were
restored exactly after test; evidence stdout confirms byte-level restoration.

**This is NOT an authorization to overwrite Dev Theme.** Current Dev
functions.php includes four independent Dev-only shipping loader requires
not present in Theme PR #165. Protect them and the footer currency switcher
in any future source reconciliation and live exact-SHA deployment.

## Fresh guest frontend compatibility smoke

USD and EUR each used an independent anonymous headless Chrome session
at mobile viewport width 390px and DPR 2.

- Dev Home, SKULL MAN product and Checkout returned HTTP 200.
- CSS bundle and responsive Hero 640w/900w present.
- Product Add to Cart produced one item; no horizontal overflow,
  page JS errors or HTTP 500.
- Native nonempty Checkout totals: USD $96.00 and EUR €85.89.
- Both sessions had **ZERO selectable payment gateways**. Treat as
  Stripe TEST / Woo integration blocker. No order, payment, refund,
  callback, newsletter send or transactional email performed.

## Decision / current gate

Andy Speed-only source/CI, Dev performance acceptance and Owner visual
plus nonpayment UAT: **PASS**. Default is Audit / Emergency OFF; Core
analytics and Theme bundle filters are optional integrations.

Separate unfinished gates: Stripe TEST, 3DS, refunds/webhooks, real
marketing coupons/gift rules, tax/shipping edge cases, preserved
Dev-only shipping modules, final merge, signed Stable and any WWW
deployment. The three related PRs remain Draft; explicit scoped Owner
authorization is required for merge and Production writes.

Dev admin readback is blocked by expired China Mac WordPress login.
Public CSS bundle HTML is verified, but installed plugin settings
and version were NOT re-read in an authenticated admin session.
Do not attempt to bypass login.

The local test harness is retained on China Mac:
 /Users/a/AI/uat/andy-speed-closure-20261028/speed-theme-dual-css.py
Anonymous guest test evidence retained on US Mac mini:
 /Users/a/AI/uat/andy-speed-closure-20261028/final-dev-guest-smoke.json

Performance acceptance remains archived exactly as Owner approved:
GTmetrix Grade B, Performance 86%, Structure 93%, LCP 1.7s,
TBT 0ms, CLS 0 and prior mobile Lighthouse median 92.
No reopening A/90 optimization.

## Authenticated live Dev source parity — 2026-10-28 addendum

Renewed WordPress admin access resolved the prior expired-login block.
Current Dev Speed PHP SHA-256 exactly equals the separately built
v0.2.1 candidate ZIP and GitHub source; both source+manifest CSS bytes
also match the running Dev asset URLs. Live BYL V3 front-page.php and
functions.php SHA-256 values exactly equal the previously recorded
accepted Dev baselines; footer currency and four special Dev-only
shipping loaders still exist. Dev Speed Safe CSS ON, Emergency OFF
unchecked, Analytics and Newsletter gates OFF. All evidence obtained
read-only, and previously unverified admin state now **PASS**.
This does not approve a whole Theme ZIP update or Stripe activation.
