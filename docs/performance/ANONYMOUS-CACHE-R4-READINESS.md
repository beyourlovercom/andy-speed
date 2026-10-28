# Andy Speed R4 — Anonymous cache readiness (read-only design)

Scope: BeYourLover Dev on 2026-10-28, no cache installation, setting
changes, plugin activation, production writes, or payment tests.

## Why R4 is necessary

The Dev server identifies as LiteSpeed, yet a homepage response sends
x-litespeed-cache-control: no-cache and cache-control: no-store,
no-cache, must-revalidate, max-age=0. Five no-session homepage requests
gave median TTFB ~608ms. Current synthetic Lighthouse Mobile performance
scores are 87/92/93 (median 92); LCP median ~3192ms.

The LCP image is already fetched with eager/high priority. Browser-only
interception tests do not justify disabling AffiliateWP, jQuery Migrate,
PhotoSwipe styles or the hero visual filter; their TTFB-adjusted apparent
LCP savings were small, noisy or unsafe.

## Observed request state

Fresh anonymous home page browser cookies, names only:
sbjs_current, sbjs_current_add, sbjs_first, sbjs_first_add,
sbjs_migrations, sbjs_session, sbjs_udata. Source attribution may
require those values even before a cart exists.

After Add to Cart in an isolated unpaid session:
woocommerce_cart_hash, woocommerce_items_in_cart,
woocommerce_recently_viewed, and wp_woocommerce_session_* are added.
These persist on return to the home page.

Dev active plugin inventory did not show an active LiteSpeed Cache
WordPress plugin. A LiteSpeed server header alone does not mean its
per-page WooCommerce cache configuration has been reviewed.

## Eligibility gates

- Bypass any public HTML cache on cart, checkout, my-account,
  pay-for-order, payment-webhook, WP admin/login and user-specific pages.
- Bypass for logged-in accounts, Woo cart/session cookies and order
  workflows. The cart drawer, sale gifts and Woo notices must never
  expose another visitor's data or display stale totals.
- Separate or bypass multi-currency values, localized country taxes,
  shipping rules, gift thresholds and promotions, as well as coupon
  sessions and CSRF/nonces. The currency switcher must not leak its
  previous visitor selection to the next one.
- Keep AffiliateWP and sbjs attribution intact; no cookie stripping to
  improve cache hit rate without independent attribution UAT.
- For public anonymous no-cart pages, require an explicit vary policy
  (currency/region/consent as applicable), private-fragment treatment
  or bypass on unproven variants, and stock/price/inventory purge rules.
- Cache OFF -> candidate ON -> OFF must preserve footer currency, cart
  count and drawer, subscription/inquiry popup nonces, analytics and
  UTM attribution. Compare network and DOM across at least two fresh
  visitors, one populated cart session and one different currency.
- Roll back immediately if totals, price, coupon, tax, shipping,
  inventory, KOL/affiliate ownership or authentication differ.
- Never assume that LiteSpeed ESI is available or free; the installed
  server tier and fragment capability must be verified. A generic
  page-cache engine does not belong inside Andy Speed.

## R4 implementation boundary

Andy Speed stays an audit/orchestration plugin with explicit OFF and
rollback controls. It should not silently become an independent
WooCommerce HTML cache provider. The actual caching provider would be
the approved LiteSpeed/host layer, subject to site-specific variance
rules and owner approval. In the current state, **no cache changes
are approved or executed**.

## Primary documentation

- https://developer.woocommerce.com/docs/best-practices/performance/configuring-caching-plugins/
- https://docs.litespeedtech.com/lscache/lscwp/cache/
- https://docs.litespeedtech.com/lscache/lscwp/faq/
