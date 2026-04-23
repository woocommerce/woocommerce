# RSM-437 — Playwright e2e coverage for BIS alpha

Design for [RSM-437](https://linear.app/a8c/issue/RSM-437/refine-e2e-tests-for-updated-dom). Sprint scratch — delete before GA.

## Background

Two previous attempts to land Playwright coverage for BIS never merged:

- [PR #53641](https://github.com/woocommerce/woocommerce/pull/53641) ("Migrate Back in stock notifications tests to Core", Dec 2024, `[Do not merge]`, 12 files).
- [PR #55836](https://github.com/woocommerce/woocommerce/pull/55836) ("Merge Back In Stock Notifications to WC core, pt 2: tests", Apr 2025, successor to #53641, 25 files). Head SHA `eda34baaa1d6a454c8a4cb6955b890921aba4dfc`.

Both are based on the external plugin's test structure: 7 specs + a 931-line BDD `helper.js` + a 325-line PHP acceptance helper + a `site.setup.js` fixture. Neither landed because they use plugin-only option slugs, plugin-only shortcode pages for scraping email content, and selectors that have drifted since early 2025.

## Approach: port coverage, rewrite shape

We don't port `helper.js`. The BDD DSL doesn't fit anywhere else in `tests/e2e-pw`, and the email-content scraping via plugin-only shortcodes is obsolete — `tests/e2e-pw/utils/email.ts::expectEmail` + `expectEmailContent` already do this via the WP Mail Logging plugin that ships pre-installed in the test env.

The 7 old specs inline the same "given / when / then" vocabulary for 7 scenarios. We treat that as a coverage checklist, not a code source, and rewrite each as a focused `.spec.ts` that calls a small helper layer + the existing `utils/` functions.

## Scope: 4 of 7 specs

Three of the old specs target features that didn't survive PRD cuts into the core alpha:

| # | Old spec | Core target? | Disposition |
|---|---|---|---|
| 1 | signing-up | ✅ | port |
| 2 | viewing-signups-count | ❌ (no per-product counter on PDP) | skip; picked up by RSM-439 (data tracking) |
| 3 | receiving-confirmations | ✅ (RSM-438 wiring) | port |
| 4 | receiving-notifications | ✅ (back-in-stock email via `NotificationsProcessor`) | port |
| 5 | managing-notifications | ✅ (admin list + edit) | port |
| 6 | viewing-account-activity | ❌ (my-account endpoint scope-cut, WOOPLUG-4997) | skip; future my-account ticket |
| 7 | following-catalog-sign-up-prompts | ❌ (no shop-loop hooks in core) | skip; future catalog-prompts ticket |

A short `README.md` inside `tests/back-in-stock-notifications/` records the skipped three with pointers to the relevant feature tickets so they're trivially picked up once those features ship.

## Stacking

This PR stacks on `rsm-438-wire-in-verification-and-confirmation-emails` (PR #64348). Specs 2 (receiving-confirmations) and 3 (receiving-notifications) exercise email dispatch that only works with the RSM-438 wiring.

## File layout

```
plugins/woocommerce/tests/e2e-pw/
├── tests/back-in-stock-notifications/
│   ├── README.md                         # scope notes + skipped-spec pointers
│   ├── signing-up.spec.ts
│   ├── receiving-confirmations.spec.ts
│   ├── receiving-notifications.spec.ts
│   └── managing-notifications.spec.ts
├── utils/back-in-stock-notifications.ts   # focused helpers (~150–200 LOC)
└── fixtures/site.setup.ts                 # extended with BIS option defaults
plugins/woocommerce/.wp-env.json           # WOOCOMMERCE_BIS_ALPHA_ENABLED in env.tests.config
```

## Helper surface (`utils/back-in-stock-notifications.ts`)

Direct TypeScript helpers — no DSL, no class wrappers. Each function owns one job:

```ts
setBISOptions( request, baseURL, { allowSignups?, doubleOptIn?, requireAccount?, createAccountOnSignup? } ): Promise<void>
createOutOfStockProduct( request, baseURL, opts: { type?: 'simple' | 'variable', attributes?: … } ): Promise<{ id, permalink, cleanup() }>
signUpOnProductPage( page, { email?, optInCheckbox? } ): Promise<void>
getLinkFromEmailBody( page, receiver: string, subject: RegExp, hrefMatch: RegExp ): Promise<string>
triggerStockNotificationsBatch( page ): Promise<void>   // hits /?process-waiting-actions
```

Callers: per-test setup creates products + sets options; spec bodies drive the UI; asserts use `expectEmail` / `expectEmailContent` from `utils/email.ts` + the `getLinkFromEmailBody` extractor for follow-through flows.

## Test environment

- `.wp-env.json` → `env.tests.config`: add `"WOOCOMMERCE_BIS_ALPHA_ENABLED": true`. Requires a wp-env restart after checkout.
- WP Mail Logging already in `env.tests.plugins`.
- `process-waiting-actions` mu-plugin already mapped in; hit via `page.goto('/?process-waiting-actions')` for spec 3.
- `ALTERNATE_WP_CRON: false` in tests env (already set) — no implicit job firing; we fire `process-waiting-actions` explicitly.

## Spec outlines

### signing-up.spec.ts (~160 LOC)

- **Logged-in, simple OOS product**: PDP shows prompt + Notify-me; click → success notice; page shows "already joined".
- **Logged-in, variable OOS product**: choose in-stock variation → no prompt; choose OOS variation → prompt; Notify-me; revisit → "already joined".
- **Logged-in, variation with `attribute=any`**: OOS variation → Notify-me works.
- **Guest, single opt-in (no checkbox)**: enter email → Notify-me → success notice.
- **Guest, single opt-in (with checkbox)**: enter email + tick checkbox → Notify-me → success notice; untick leaves signup invalid.
- **Guest, double opt-in**: enter email → Notify-me → "check your email" prompt; verify email present in WP Mail Logging.
- **Guest, requires-account**: Notify-me → login prompt; after login → success.

### receiving-confirmations.spec.ts (~130 LOC)

- **Single opt-in confirmation email content**: simple OOS product, guest signup; assert confirmation email subject + body contains product name + unsubscribe link with correct UTM params.
- **Double opt-in verify → verified full flow**: signup with double opt-in on; assert verify email subject + UTM on link; follow link; assert status transition + confirmation/verified email subject + UTM on unsubscribe link.
- **Unsubscribe link cancels**: from confirmation email, follow unsubscribe link; assert notification status → CANCELLED and cancellation_source = USER.

### receiving-notifications.spec.ts (~110 LOC)

- **Back-in-stock email dispatch**: create ACTIVE notification (either via REST or signup with double opt-in off); flip product to in-stock; `triggerStockNotificationsBatch(page)`; assert back-in-stock email arrives with product CTA link + UTMs.
- **Unsubscribe from back-in-stock email**: follow unsubscribe link from the dispatched email; assert notification → CANCELLED.

### managing-notifications.spec.ts (~140 LOC)

- **List page rendering**: create 2 notifications (one PENDING, one ACTIVE); navigate to admin; assert both rows visible with correct statuses.
- **Admin Resend on PENDING**: PENDING row → Resend verification email action → success notice + new verify email in log (RSM-438 Must-fix #9 regression).
- **Admin Resend on ACTIVE**: ACTIVE row → UI hides the action from the dropdown. Inject the action via JS to exercise the server-side guard → error notice, no email.
- **Admin Cancel**: cancel a PENDING notification → status = CANCELLED, cancellation_source = ADMIN.

## Cleanup semantics

- Each spec creates its own product(s) via REST in `beforeEach`; `afterEach` deletes products + truncates `wc_stock_notifications` + `wc_stock_notificationmeta` (direct SQL via test-helper-apis REST endpoint, already in tests env).
- Mail log is not cleared between tests; assertions are scoped by unique guest email addresses (`signup-${ testId }@example.com`) so cross-test noise is impossible.

## Out of scope / follow-ups

- The 3 skipped specs (2 / 6 / 7) — file pointer lives in the README, tied to feature tickets.
- Theme-matrix runs (Storefront + twentytwentyfour). Not load-bearing for functional flows; adds 2× runtime for no real coverage.
- Blocks-template PDP coverage — the BIS form renders into legacy PDP only in the alpha. Blocks support is beyond-i1.
