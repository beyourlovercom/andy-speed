# Andy Speed PairDoll 0.3.0-dev — Manual Install (10 Nov 2026)

**Installable WordPress ZIP:** `andy-speed-pairdoll-v0.3.0-dev-20261110.zip`

- Source PR: https://github.com/beyourlovercom/andy-speed/pull/5
- Exact source commit: `5a55410638f2ff13db6f5936cbf69740bfd93726`
- ZIP SHA-256: `f8274f0385579238e39dc8d82eb2d15dd2f34e04d9eca14c1064937b84f88b9d`
- ZIP size: 20,234 bytes
- Package root: `andy-speed/`
- Shipped plugin entry: `andy-speed/andy-speed.php`; plugin includes R2/R3 module and existing BYL V3 assets.
- ZIP integrity / WordPress PHP syntax / isolation tests: PASS; GitHub CI #72 PASS for source commit.
- **This is an experimental development build, not the signed Andy Speed Stable release.** No performance score 90+ has been certified.

## Install on pairdoll.com

1. In WordPress > Plugins > Add New > Upload Plugin, upload the exact ZIP. Click Install Now > Activate.
2. Visit Tools > Andy Speed. Upon activation the plugin starts in **Audit Only, Emergency OFF enabled**; optimization switches are off.
3. For the controlled PairDoll performance pilot, choose **Safe mode**, uncheck **Emergency OFF**, and enable only these PairDoll features:
   - PairDoll mobile hero stability (no automatic mobile slider rotation);
   - PairDoll mobile hero WebP;
   - PairDoll responsive Logo WebP.
4. Leave LiteSpeed Guest Mode and Guest Optimization ON (measured OFF configuration scored worse). Keep Andy Speed's BYL V3 CSS / Newsletter and Analytics gates OFF.
5. Save. Verify homepage on real mobile and desktop. Then compare at least three Lighthouse runs and authentic provider reports; check WooCommerce product/cart/checkout, without placing a real paid order.

To rollback, re-enable **Emergency OFF** in Tools > Andy Speed, save, and purge only the homepage LiteSpeed cache. If needed, deactivate the plugin. Original WordPress media and Elementor page data were not overwritten.

**Privacy/security:** no passwords, API keys or third-party assets are present in this installation archive.
