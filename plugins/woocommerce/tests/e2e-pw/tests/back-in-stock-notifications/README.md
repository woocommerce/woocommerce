# Back in Stock Notifications — Playwright tests

Covers the four scenarios from the original plugin test plan that have a target in core:

- `signing-up.spec.ts` — PDP form rendering + signup flow (logged-in, guest single-opt-in, guest double-opt-in, requires-account).
- `receiving-confirmations.spec.ts` — verify email + verified email + unsubscribe flow (double opt-in).
- `receiving-notifications.spec.ts` — back-in-stock email dispatch on restock + unsubscribe flow.
- `managing-notifications.spec.ts` — admin list rendering + Resend on PENDING + Resend guard on ACTIVE + admin Cancel.

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

- BIS is gated behind the `WOOCOMMERCE_BIS_ALPHA_ENABLED` PHP constant, which is
  set to `true` for the tests env via `plugins/woocommerce/.wp-env.json`. If you
  bring the env up manually, ensure the constant is defined.
- The tests assume the WP Mail Logging plugin is installed and active (it is,
  via `.wp-env.json` tests plugins list).
