# Back in Stock Notifications — Playwright tests

Covers the scenarios from the original plugin test plan that have a target in core:

- `signing-up.spec.ts` — PDP form rendering + signup flow (logged-in, guest single-opt-in, guest double-opt-in, requires-account).
- `receiving-confirmations.spec.ts` — verify email + verified email + unsubscribe flow (double opt-in).
- `receiving-notifications.spec.ts` — back-in-stock email dispatch on restock + unsubscribe flow.
- `managing-notifications.spec.ts` — admin list rendering + Resend on PENDING + Resend guard on ACTIVE + admin Cancel.
- `variations.spec.ts` — variable products: the form following the selected
  variation, signup against a variation, the variation's attributes in the
  emails, the back-in-stock email linking back to a fixed-value variation
  pre-selected, and the parent-level signup opt-out removing the form from the
  whole variable product page.

## Variation notes

- The form's show/hide is asserted on the `hidden` class rather than on
  visibility. That class is the contract `back-in-stock-form.js` drives; whether
  it actually hides the form depends on the theme, since
  `.woocommerce .wc_bis_form.hidden { display: none }` ships in `woocommerce.css`
  (the `woocommerce-general` handle), which bundled themes such as Twenty
  Twenty-Three replace with their own stylesheet.
- The parent-level opt-out is asserted on the product page only. On render,
  `maybe_render_form()` passes the parent from `global $product` to
  `product_allows_signups()`, so the variation branch of that method — the one
  that walks up to the parent — is only reached on the signup POST, which the
  form's absence makes unreachable from the UI.
- The plugin's "already signed up for this variation" scenario has no core
  target: that message is only rendered when the
  `woocommerce_customer_stock_notifications_personalization_enabled` filter is
  enabled (it defaults to `false`) and it resolves against the parent product on
  a variable PDP, not the selected variation.

## Skipped scenarios

Three scenarios from the original plugin test plan target features that didn't
survive PRD cuts into the core alpha. They'll be picked up alongside the
respective feature tickets:

- **Viewing signups count** — no per-product signup counter exists on the PDP in
  core. Expected to land alongside [RSM-439](https://linear.app/a8c/issue/RSM-439)
  (Data tracking / analytics).
- **Viewing account activity** — the `stock-notifications` my-account endpoint
  was scope-cut (WOOPLUG-4997). Will be added once a my-account follow-up
  ticket ships.
- **Following catalog sign-up prompts** — core BIS does not hook into the shop
  loop. Will be added if/when a catalog-prompts ticket ships.

## Prerequisites

- BIS is gated by the `customer_stock_notifications` feature toggle (WooCommerce
  → Settings → Advanced → Features → Experimental), enabled for the tests env
  via `plugins/woocommerce/tests/e2e/bin/test-env-setup.sh`. If you bring
  the env up manually, set `woocommerce_feature_customer_stock_notifications_enabled`
  to `'yes'`.
- The tests assume the WP Mail Logging plugin is installed and active (it is,
  via the `.wp-env.e2e.json` plugins list).
- `woocommerce-e2e-test-helper` zeroes
  `woocommerce_customer_stock_notifications_first_batch_delay`, so a restock
  dispatches its batch immediately instead of a minute later. Without it the
  back-in-stock specs time out with no email.
- Run these under `core-serial` (`--project=core-serial`). They set global
  options, so `playwright.config.ts` excludes them from `core-parallel`.
