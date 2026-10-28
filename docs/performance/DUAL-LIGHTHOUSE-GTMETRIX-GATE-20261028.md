# Andy Speed — BYL Dev dual performance release gate

2026-10-28 — This is an acceptance contract and read-only test evidence,
**not** a GTmetrix A certification or permission to change WWW.

## Owner targets

1. Dev mobile Lighthouse: 3+ runs, median Performance **at least 90**.
2. Dev mobile Lighthouse with actual DevTools throttling: LCP **below 2,500 ms**,
   preferably 3+ runs and median to avoid relying on one unusually fast result.
3. GTmetrix Seattle desktop, Chrome 154 / Lighthouse 12.6.1 (match the user's
   report's Analysis Options): **Grade A**, Performance **90+** and Structure
   **90+**, CLS near zero, TBT target **150ms or less**.
4. No hosting fee increase, no WWW deployment, no loss of checkout,
   promotions, nonces, newsletter subscriptions, currency, shipping/tax,
   affiliate attribution, GTM or analytics conversions.

GTmetrix official Grade blend: 60% Performance + 40% Structure; A begins
at 90 points. Score 90 Performance + 92 Structure would give 90.8 points
and thus A, but an actual GTmetrix rerun is required to certify it.

## Evidence and comparison (all are *different test environments*)

- User-provided GTmetrix report generated 2026-10-27 18:20 -0700:
  Grade B; Performance 77; Structure 92; LCP 1.3s; TBT 390ms; CLS 0.
  Report: https://gtmetrix.com/reports/dev.beyourlover.com/VaPu1Xft/
  Full interactive GTmetrix page is currently Cloudflare-challenged from
  available search/remote environments, so GTmetrix detailed long-task
  waterfall and a new authentic GT report are **not yet verified**.
- Independent US Mac mini, Lighthouse **12.6.1**, desktop, Chrome 156:
  99 Performance, LCP 0.85s, TBT 0ms (default desktop settings).
- Same Lighthouse desktop with DevTools **4x CPU** slowdown:
  96 Performance, LCP 1.09s, TBT 91ms.
- Same Lighthouse desktop with DevTools **8x CPU** slowdown:
  89 Performance, LCP 1.19s, TBT 213ms. Long tasks were attributed
  to the main document, with about 1,397 DOM elements and considerable
  layout/style calculation; this suggests sensitivity to hardware, not
  that any specific payment/marketing asset is proven harmful.
- Independently verified BYL Dev mobile Lighthouse three runs
  87/92/93 (median 92), LCP median ~3.19s.
- Alternative preconnect/preload test in a fixed-HTML isolated browser
  produced only ~66ms median LCP improvement. No duplicate Hero request,
  but insufficient evidence for a live deploy.
- An isolated 760px Hero candidate showed positive LCP results with
  substantial run-to-run variance and differing CDN/cache conditions;
  not automatically deployed without an equivalent real-Lighthouse gate.
- Isolated off-screen content-visibility test made no replicable TBT
  improvement and affected placeholder layout height: rejected.
- Recent Dev asset inventory includes WooCommerce, AffiliateWP, Customer
  Reviews and Andy Core public scripts. The documented GTmetrix TBT 390ms
  has **not been reliably reproduced** on our own desktop runs. Do not
  attribute it to any particular plugin without source trace evidence.

## Required next measurements

- Obtain **fresh GTmetrix report** from identical Seattle/device/network
  Analysis Options after the current Dev image+CSS build. Use Performance,
  Structure, Waterfall and the top Long Tasks; log exactly which version and
  run date. GTmetrix allows downloading full PDF and HAR to account holders.
- Keep current Dev settings (Andy Speed Safe, CSS bundle ON,
  Newsletter/Analytics OFF) and the working responsive assets unchanged
  while diagnosing; do not enable generic caching.
- Test source ownership before any dequeue/defer and explicitly verify
  home -> product -> Add to Cart -> nonempty cart -> checkout (no charges),
  affiliate attribution, country/currency, coupon/gift rules, newsletter.
- If GTmetrix TBT cannot be reproduced, treat B as **not yet revalidated**,
  not as proof that disabling checkout scripts is justified.
- Store every run, including failures and variations, in evidence.

## Release decisions

- Never equate a local 99 Performance with GTmetrix Grade A.
- A genuine new GTmetrix A/Performance>=90 + site UAT is the separate gate.
- Production changes are not authorized by this document or by PR CI.



## Later Owner acceptance override — 2026-10-28

The targets above are preserved as historical engineering aspirations
and diagnostics, **not current blocking performance release criteria**.
After the verified GTmetrix retest (B / Performance 86 / Structure 93 /
LCP 1.7s / TBT 0ms / CLS 0) and Owner visual UAT 5/5 PASS, Owner
explicitly approved **current Dev performance as meeting this phase's
acceptance criteria**, without demanding further A/90 optimization.
GTmetrix A/90 and mobile real-throttle LCP <2.5s remain optional
follow-up goals; they have NOT been measured as achieved.

Other independent payment/marketing/Dev-shipping source preservation
and production approval requirements are unchanged. No PR merge,
Production deployment or server-cost increase is implied.
