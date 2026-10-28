# Andy Speed R4 — execution evidence and Owner UAT gate

**Status:** Dev-only, not a Production or merge approval. 2026-10-28.

## 1. GTmetrix same-setting benchmark

- Owner screenshot: GTmetrix Seattle Grade **B**, Performance **77%**,
  Structure **92%**, LCP **1.3s**, TBT **390ms**, CLS **0**.
- The report URL rejects external fetch with HTTP 403. No new authentic
  GTmetrix Grade A report is available. **Gate: pending official re-test.**
- Independent Lighthouse 12.6.1 desktop scored 99 / TBT 0ms default,
  96 / TBT 91ms under 4x CPU, 89 / TBT 213ms under 8x CPU.
  **These do not certify GTmetrix A.**
- Main-thread bottleneck under slower CPU: style/layout 371ms vs
  script evaluation 162ms; no specific Stripe/AffiliateWP script proved bad.

## 2. TBT resource analysis and safe changes

- Read-only Chrome A/B blocked Photoswipe CSS, Customer Reviews home CSS,
  tried delayed below-fold content visibility and removing hero filter:
  no repeatable benefit without visual/functional tradeoffs. Therefore
  **not deployed** and no payment, tracking or marketing JS was removed.
- Existing Dev Speed v0.2.1 CSS checksum bundle stays ON; image and
  mobile performance wins remain intact, with Emergency OFF available.
- **Gate: any new GTmetrix-specific TBT fix requires source trace from
  a fresh equivalent Seattle report**; no speculative de-queue.

## 3. Anonymous HTML cache isolation — FAIL CLOSED

- Actual Dev homepage is explicitly no-store / no-cache.
- In isolated Chrome, a EUR visitor normally saw euro pricing. A
  browser-only simulated public USD HTML replay immediately showed
  USD despite EUR currency session: **unsafe currency cross-visitor
  cache bleed is reproducible**. No server cache was modified.
- WooCommerce cart session cookies persist on returning to homepage.
  Attribution uses sbjs_* cookies. Cart, Checkout, My Account, order
  payment and all state-changing APIs must be excluded.
- Experimental PHP fixture anonymous-cache-eligibility-harness.php
  confirms fail-closed candidate rules. The single positive fixture
  applies **only after** a separate owner-validated variant/nonce gate.
  No live page cache has been enabled, and no hosting expense added.
- **Gate: no safe Dev cache A/B may proceed until currency, affiliate,
  cart fragments, nonce, tax, shipping and coupon variation are proven.**

## 4. Customer-side Checkout read-only UAT

- Headless Chrome isolated sessions: USD, EUR, GBP, AUD, CAD each:
  one SKULL MAN in cart, currency-consistent subtotal and Checkout
  total, correct currency sign and no page JavaScript or HTTP 500.
- Country switching through Woo AJAX for US, GB, DE and CA succeeded
  without HTTP 500 or horizontal overflow, but no tax rows displayed
  with synthetic address-only state. This does **not** verify mixed
  tax class or the full shipping strategy.
- Invalid synthetic coupon on Cart correctly produced an error,
  retained quantity 1 and did not change total. A real valid coupon,
  mixed coupons, gift upgrades and $199 threshold were not exercised.
- **P0 BLOCKER:** with a populated Cart, Checkout presented no available
  payment methods in all five currencies. No test payment, order,
  refund, webhook or customer email was executed. This must be
  reconciled with the separate Stripe Test/UAT ownership chain;
  DO NOT disable Dev safety controls or activate gateways as a
  performance workaround.
- No Production/WWW write, no changed hosting plan or added cost.

## Human Owner UAT checklist (do not execute payment on Production)

1. Go to https://dev.beyourlover.com/ in a clean guest browser.
   Check responsive header/logo, mobile menu, currency chooser,
   Hero display, mini cart and product card.
2. Select USD and EUR separately, add a single known product, verify
   prices and $69 shipping presentation with country US/UK/DE/CA.
   Run one approved active Dev test coupon and a separate invalid coupon.
   Test BOGO/gifts only in an explicitly isolated and enabled campaign.
3. Confirm the intended Dev **Stripe TEST** gateway is actually present
   and checkout uses the approved test account; no production charges.
   Then the Stripe owner branch must run separate payment/webhook,
   refund and rollback UAT. Do not place an order with unavailable
   gateways or enter live payment card numbers.
4. Re-test GTmetrix in Seattle with the same browser/device/connection
   Analysis Options and send Performance/Structure/TBT/Waterfall/Long
   Tasks if the official report remains B.
5. Record Owner PASS/FAIL by scope. The three Draft PRs remain unmerged
   and production deployment requires a separate explicit PASS.

## Stable rollback reference

- Andy Speed plugin OFF switch restores six original CSS requests.
- Theme responsive image edits preserve original src and source files,
  but require separate exact-file backup+rollback (Speed OFF does not
  revert the theme).
- Currency shortcode and Dev-only shipping loaders are protected;
  never deploy full public theme ZIP over the current Dev theme.

## Evidence files on US Mac mini

/Users/a/AI/uat/andy-speed-r4-uat-20261028/
- checkout-currency-isolated.json
- shipping-coupon.json (country results captured, coupon test
  interrupted by hidden native button; followed by separate successful
  invalid-coupon test)
- cache-bleed.json

This document contains only test-summary metadata, no customer names,
addresses, payment credentials, nonces or private session values.

## Owner sign-off and new external GTmetrix report — 2026-10-28

Owner explicitly reported that all five manual BYL Dev visual and
non-payment Checkout checks **PASS**: header/logo/hero/fonts, mobile
layout, SKULL MAN Add to Cart/drawer, USD/EUR cart amount, and Checkout
address/total UI. This signs off **visual + non-payment manual UAT only**.
It does not waive payment, webhook, refund, GTmetrix A, or the
separately protected Dev-only shipping modules and valid marketing
campaign UAT.

New authentic GTmetrix report obtained via the user's authorized
Chrome session:
https://gtmetrix.com/reports/dev.beyourlover.com/7GN9O8fq/

- Report generated Tue Oct 27 2026 21:12 -0700, Seattle, Chrome 154,
  Lighthouse 12.6.1 (matching prior test location/browser).
- New result: **Grade B, Performance 86%, Structure 93%, LCP 1.7s,
  TBT 0ms, CLS 0**.
- Prior owner-supplied result dated Oct 27 18:20 -0700:
  Grade B, Performance 77%, Structure 92%, LCP 1.3s, TBT 390ms, CLS 0.
- Changes between reports: Performance +9 percentage points, Structure
  +1, TBT -390ms, LCP +0.4s. Not a controlled A/B, so do not attribute
  all variance to one plugin or code change.
- **GTmetrix A/Performance >=90 is NOT met**; the remaining 4
  percentage points must be investigated. TBT <=150 is now satisfied.
- Public/unauthenticated GTmetrix view shows summary only; Performance
  detail/Waterfall/Long Tasks behind account login were not available.
  Do not claim to know exact remaining score loss contributors.

Keep PR Draft and production untouched. Next: use authenticated
GTmetrix Performance report waterfall and per-metric scoring to target
FCP/Speed Index/LCP without changing Woo/Stripe/AffiliateWP behavior;
rerun GTmetrix at identical Analysis Options to sign off A/90.



## Owner acceptance decision — 2026-10-28 (supersedes A/90 as current performance gate)

On 2026-10-28, after reviewing the official GTmetrix retest and confirming
all five BYL Dev visual/non-payment checks PASS, Owner explicitly stated:
“没有必要，暂时已经很好了，可以记录为达成条件。”
(Performance is sufficiently good for now; record the phase as accepted.)

**Decision: Andy Speed Dev performance-stage acceptance = OWNER PASS.**

The accepted evidence is **GTmetrix Grade B, Performance 86%, Structure
93%, LCP 1.7s, TBT 0ms, CLS 0**, plus Mobile Lighthouse simulated
scores 87/92/93 (median 92) and the five passed manual Dev checks.
This acceptance deliberately **relaxes the former GTmetrix Grade A /
Performance >=90 release gate for this performance phase only**.
It is NOT a statement that an A or 90% score was measured. GTmetrix
Grade A / Performance 90+ is now an **optional future improvement target**,
not an active blocker requiring further optimization.

**Scope of PASS:** BYL Dev performance/visual/non-payment UAT.
No further GTmetrix retest or TBT-chasing changes are required for this
phase. Preserve the current safe Speed plugin and approved responsive
asset configuration; no new hosting costs.

**Unchanged independent gates:** Stripe TEST gateway payment methods,
3DS, refunds, webhook, marketing coupon/gift integration, shipping/tax
mixed-case checks, source-drift protection for four Dev-only shipping
loaders, three Draft PR merge/release workflow and explicit Owner
production deployment PASS. This acceptance does not approve merging
any PR or deploying to WWW. Do not rewrite historical report metrics
or represent GTmetrix Grade B as A.
