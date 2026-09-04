# Back in Stock Notifications — Playwright tests

Covers the scenarios from the original plugin test plan that have a target in core:

- `signing-up.spec.ts` — PDP form rendering + signup flow across the settings
  matrix: signups disabled, logged-in single opt-in (with the "Manage
  notifications" CTA and the nonce rejection), guest single and double opt-in,
  account creation on signup (consent checkbox, welcome email), and the
  requires-account prompt through to a logged-in signup. Also the server-side
  rejections for an invalid email and a tampered product id.
- `receiving-confirmations.spec.ts` — verify email + verified email + unsubscribe
  flow (double opt-in), the frontend verify/unsubscribe notices, the logged-in
  footer of the verified email, and rejected verify links (tampered key, expired).
- `receiving-notifications.spec.ts` — back-in-stock email dispatch on restock +
  unsubscribe flow, the logged-in footer of the back-in-stock email, and a
  rejected (tampered) unsubscribe link.
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

## Configuration-branch notes

- The signup nonce is only verified when the
  `woocommerce_customer_stock_notifications_personalization_enabled` filter is
  on and the shopper is logged in (or an account is required), so guest forms
  survive HTML caching. The nonce test turns personalization on through the
  test helper's `e2e-filters` cookie (`setFilterValue()`), which is why it runs
  in the logged-in describe.
- Verify-link expiry is a filter
  (`woocommerce_customer_stock_notifications_verification_expiration_time_threshold`),
  not an option, so `expireVerificationLinks()` sets it to a negative value
  through the same cookie. Both tests clear the cookie afterwards with
  `clearFilters()`.
- The email templates fork on `$is_guest`, which is "the signup has no
  `WP_User`", not "the shopper was logged out": a guest signup with an email
  that already belongs to an account, or one that created an account on signup,
  also takes the logged-in branch. The specs cover it through the shared
  `customer` account (`signUpAsCustomer()`).
- Account-creation tests register a real customer for the guest's address. The
  address comes from the `accountEmail` fixture, whose teardown looks it up and
  deletes any account it finds, so a failed assertion (or a retry with a fresh
  address) doesn't leave the account behind.

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
