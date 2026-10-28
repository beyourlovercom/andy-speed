# Andy Speed v0.2.1 Stable

**Independent WordPress performance plugin.** This is a source-controlled GitHub Stable package. Publishing this release does **not** update or deploy WordPress installations.

## Verified behavior
- Default Audit Only and global Emergency OFF. All optimization switches OFF by default; explicitly enable Safe for desired site integration.
- Optional SHA-256-guarded BYL V3 common CSS bundle; preserves theme stylesheet handles/dependencies, restores original six stylesheets on mismatch or Emergency OFF.
- Optional Andy Core Analytics diagnostics filter and Newsletter defer gate (both OFF in Dev acceptance).
- Does not modify cache, products, discounts, WooCommerce shipping, tax, checkout, Stripe, order processing, tracking or affiliate attribution.

## Acceptance evidence
- Owner explicitly accepted BYL Dev GTmetrix **B / Performance 86% / Structure 93% / LCP 1.7s / TBT 0ms / CLS 0**, and manual visual/nonpayment UAT **5/5 PASS**.
- Isolated Speed + BYL V3 Theme coexistence and fail-closed CSS rollback checks **4/4 PASS**.
- Dev WordPress source hash parity, current installed Speed v0.2.1, Safe CSS ON; automated CI safety/cache/manifest validation PASS.
- Separate paid checkout, Stripe, campaign gift/coupon, complex tax/shipping and full Dev-to-WWW migration are **not** signed off by this release.

## Provenance and signing
- Plugin *source snapshot*: `ebb64abb79273e4806806d4f3c1109f0a38fcd60` from PR #1. The release tag points to the reviewed release-infrastructure commit on `main`, not a new plugin functional version.
- ZIP: `andy-speed-v0.2.1.zip`; SHA-256 `cce5f9755f7944e4ddbf0776ed87ec3b4d29721101da2d88cff2a0eb84aba9ae`.
- Signed `update-metadata.json` and detached `update-metadata.sig` use a dedicated Andy Speed Ed25519 signing key held in the protected GitHub Environment `andy-speed-signing`.
- Independently pinned public verification key: `config/andy-speed-release-public-key.b64` (included as a Release asset). Public-key SHA-256 fingerprint: `97f2093baa3da899ecbb82a62fdbf458c4c8b142e95cab32892610fcd1d0340b`.
- Verify ZIP integrity with `SHA256.txt`; verify detached signature over **original exact metadata bytes** and ZIP SHA with the pinned public key. Do not interpret a checksum alone as a signature.

## Deployment boundary
The package is ready for download and review, **not** an automatic WWW install or a signal to enable plugins in production. Dev and WWW upgrades, other Andy Core/Theme PR merges, and payment release remain separately approved work. Existing plugin OFF/rollback behavior is documented in `ROLLBACK_GUIDE_v0.2.1.md`.

See [Issue #2](https://github.com/beyourlovercom/andy-speed/issues/2) and the signed-verification workflow [#40652429786](https://github.com/beyourlovercom/andy-speed/actions/runs/40652429786).
