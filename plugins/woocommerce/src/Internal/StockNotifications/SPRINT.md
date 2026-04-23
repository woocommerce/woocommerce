# BIS Sprint — Running Doc

Working doc for the Back in Stock Notifications sprint on `feature/back-in-stock-notifications`. Captures decisions, deferred items, and things that must happen before the alpha is promoted. Deleted with the rest of the scratch docs at GA time.

Companion files: [`CODERABBIT-TRIAGE.md`](./CODERABBIT-TRIAGE.md) (actionable triage), [`CODERABBIT-TRIAGE-RAW.md`](./CODERABBIT-TRIAGE-RAW.md) (audit trail).

## Pre-merge / pre-ship checklist

Things that must happen before this branch — or any descendant feature work — merges back to trunk for a user-facing release. Tick as resolved.

- [ ] Delete `plugins/woocommerce/src/Internal/StockNotifications/CODERABBIT-TRIAGE.md`
- [ ] Delete `plugins/woocommerce/src/Internal/StockNotifications/CODERABBIT-TRIAGE-RAW.md`
- [ ] Delete `plugins/woocommerce/src/Internal/StockNotifications/SPRINT.md` (this file)
- [ ] Delete `plugins/woocommerce/src/Internal/StockNotifications/RSM-438-DESIGN.md`
- [ ] Delete `plugins/woocommerce/src/Internal/StockNotifications/RSM-438-TESTING.md`
- [ ] Remove or promote the `WOOCOMMERCE_BIS_ALPHA_ENABLED` gate in `plugins/woocommerce/includes/class-woocommerce.php:381` (currently alpha-only; at GA either ship unconditionally or put behind a proper feature flag).
- [ ] Decide the migration story for any real alpha-site data (likely none, but confirm). Meta-key split in this PR already invalidated in-flight alpha emails — call this out in release notes if any alpha adopters exist.
- [ ] Audit for remaining CodeRabbit Should-fix / Nice-to-have items that should ship with GA (see [`CODERABBIT-TRIAGE.md`](./CODERABBIT-TRIAGE.md)).
- [ ] Out-of-BIS-scope but blocking the min-WP narrative: clean up `function_exists( 'wp_fast_hash' )` shims that still exist in `plugins/woocommerce/includes/wc-core-functions.php:1274` and `plugins/woocommerce/includes/class-wc-session-handler.php:311-336`. Not BIS work, but someone should file a follow-up so these don't linger.
- [ ] **Playwright e2e coverage.** The alpha ships with zero browser-level tests. Tracked in [RSM-437](https://linear.app/a8c/issue/RSM-437/refine-e2e-tests-for-updated-dom) — port PRs #53641 and #55836 to the current alpha (option slug + shortcode + selector updates). Scheduled Days 9-10 of the sprint. Full context in the decision log below.

## Decisions log

Chronological, one entry per non-obvious choice. Format: date — decision, then why / blast radius.

### 2026-04-22 — Split `email_link_action_key` meta into two, dispatch by explicit URL param

`Notification::get_verification_key()` and `Notification::get_unsubscribe_key()` both wrote to the same `email_link_action_key` meta, distinguishing verification vs. unsubscribe by the presence of a `:` in the stored value (verification stored `timestamp:hash`, unsubscribe stored `hash`). `EmailActionController::validate_and_maybe_process_request()` sniffed the stored format to decide which action to run.

**New design:**

- Verification hash → `verification_action_key` meta (format `timestamp:hash`).
- Unsubscribe hash → `unsubscribe_action_key` meta (format `hash`).
- Outgoing verification + unsubscribe URLs now include an explicit `email_link_action=verify|unsubscribe` query param.
- Dispatcher switches on that param — no more format sniffing.

**Why:** Shared storage + implicit format-as-discriminator is fragile. A future contributor could break the invariant. Two meta keys + an explicit param is the boring, robust answer CodeRabbit called for.

**Blast radius:** Any verification or unsubscribe URL dispatched from an alpha site before this change no longer matches. Acceptable because the feature is alpha-only behind `WOOCOMMERCE_BIS_ALPHA_ENABLED`; if there are any real alpha adopters they'd need to re-sign up.

### 2026-04-22 — Defer `NotificationEditPage.php:107` verification-email-send bug to RSM-438

CodeRabbit flagged that the admin `send_verification_email` case shows a "Verification email sent to …" success notice without actually dispatching an email. This is one of the 9 Must-fix items from the triage.

**Decision:** Don't fix in isolation — fold into the RSM-438 verification-email-wiring work. Doing it properly requires wiring up the email dispatcher, which is a larger change. A one-line fix here would either mask the underlying missing-send bug or add an incomplete send path we'd rework anyway.

**Blast radius:** Admin action remains misleading in the interim, but only visible in the alpha-flagged path. Triage doc notes the deferral.

### 2026-04-22 — `PrivacyEraser` `set_date_cancelled(time())` instead of `current_time('mysql')`

While adding the `Factory::get_notification()` null guard (same pattern fix as `DataRetentionController`), also changed `$notification->set_date_cancelled( current_time( 'mysql' ) )` to `set_date_cancelled( time() )` to match every other caller of that setter (which pass an integer timestamp). The setter re-parses its input, so a `'mysql'`-format string worked by accident — but was inconsistent.

**Still outstanding on this site:** CodeRabbit also flagged no `is_email()` guard on the incoming email, and no batching for large erase requests. Those are separate Should-fix items — parked for later.

### 2026-04-22 — PHPStan baseline for `PrivacyEraser.php` shrunk by 8 entries

Adding the `if ( $notification instanceof Notification )` guard narrows the type inside the foreach, so 8 baselined `method.nonObject` entries on `PrivacyEraser.php` are no longer needed. Per `AGENTS.md`, the baseline "must never be added to" and "should only shrink over time" — removed the obsolete entries in the same commit that added the guard.

### 2026-04-22 — Chickpea dev env has two SQLite drivers (CLI vs HTTP)

Unrelated to the BIS code itself, but cost an hour of debugging. Captured in detail in user-memory (`reference_chickpea_sqlite.md`), reproduced here because it's load-bearing for anyone else bringing up BIS on a Studio site:

- WP-CLI uses `WP_SQLite_Translator`; HTTP requests use `WP_PDO_MySQL_On_SQLite`.
- They share one `.sqlite` file but the new driver maintains its own MySQL information-schema mirror.
- Tables created via CLI `WC_Install::install()` are invisible to HTTP requests until the PDO driver has run a `CREATE TABLE` against them itself.
- Recovery ritual when the meta tables go missing from the PDO driver's view: drop the two BIS tables, then re-run `WC_Install::install()` from an HTTP request (not WP-CLI).

### 2026-04-22 — Two historical Playwright port PRs exist (#53641, #55836), both closed unmerged

Tracked in [RSM-437](https://linear.app/a8c/issue/RSM-437/refine-e2e-tests-for-updated-dom).

- [PR #53641](https://github.com/woocommerce/woocommerce/pull/53641) ("Migrate Back in stock notifications tests to Core", Nathan Silveira, Dec 2024 → closed Mar 2025). Marked `[Do not merge]` from day one. ~2000 lines across 10 files: 7 Playwright specs + a 936-line BDD helper (`given/when/then`) + env setup + PHP acceptance helper. Head SHA `722e4ba15f3c86c93294adebc8b1787e66cf26c2`.
- [PR #55836](https://github.com/woocommerce/woocommerce/pull/55836) ("Merge Back In Stock Notifications to WC core, pt 2: tests", Peter Fabian, Feb → Apr 2025). Successor to #53641 with the same 7 specs + helper + a new `fixtures/site.setup.js`. Also closed unmerged.

Specs cover signup (simple / variable / "any"), signup count display, confirmations, notifications, management, account activity, catalog prompts.

**Why neither landed for the current core alpha:**

- All option slugs are the original external plugin's `wc_bis_*` names (e.g. `wc_bis_account_required`, `wc_bis_double_opt_in_required`). Core uses `woocommerce_customer_stock_notifications_*` names. ~20–30 rewrites required across helper + specs.
- The test-env setup creates three shortcode pages (`[bis_confirmation_received_email]`, `[bis_verify_received_email]`, `[bis_notification_received_email]`) and scrapes them for email content. Those shortcodes only exist in the original plugin. Replace with Mailpit-style assertions or REST reads of notification state.
- Selectors have drifted since early 2025 — particularly the admin create form, which this PR just fixed for malformed `<option>` markup and a missing attribute space.
- Needs storage-state / customer-user wiring in `playwright.config` to match current e2e-pw conventions.

**Action:** [RSM-437](https://linear.app/a8c/issue/RSM-437/refine-e2e-tests-for-updated-dom) holds the porting scope. Rough estimate 1–2 days. The full slug-rename table and scope checklist lives in that issue.

### 2026-04-22 — `.wp-env.override.json` does not honor `testsPort`

`wp-env` reads `testsPort` from neither `.wp-env.json` nor `.wp-env.override.json` — the generated `docker-compose.yml` uses `${WP_ENV_TESTS_PORT:-8086}`, so you must set the env var at invocation time. On this machine 8888/8086 are held by the sister `woocommerce-monorepo` clone's wp-env, so:

```sh
WP_ENV_TESTS_PORT=8899 pnpm env:start
WP_ENV_TESTS_PORT=8899 pnpm --filter=@woocommerce/plugin-woocommerce test:php:env -- --filter '...'
```

Dev port override (8898) does work from `.wp-env.override.json` — only `testsPort` is broken.

## Deferred / follow-up work

Things explicitly parked for later. Cross-reference before starting sibling work.

- **RSM-438** — verification-email wiring (double-opt-in flow). Absorbs CodeRabbit Must-fix #2 (`NotificationEditPage.php:107` verification-email-send bug) and the "`get_option_or_transient()` fatal" investigation (already downgraded to noise in triage but worth re-confirming during wiring). **Status 2026-04-23:** landed on `rsm-438-wire-in-verification-and-confirmation-emails`, browser-verified end-to-end on chickpea.
- **Guest signup notices never render.** Surfaced while browser-testing RSM-438: `FormHandlerService::handle_signup()` calls `wc_add_notice()` for all signup outcomes but never calls `WC()->session->set_customer_session_cookie( true )`, so for logged-out visitors — which is most of the signup surface — the success / "already joined + Resend verification" / error notices get written to a session that has no cookie and are discarded at request end. `EmailActionController` has the cookie-priming guard inline; `FormHandlerService` needs the same. Not RSM-438 scope but should be a Should-fix before GA.
- **11 Should-fix + 16 Nice-to-have CodeRabbit items** remain unresolved. Priority and ownership not yet assigned — see [`CODERABBIT-TRIAGE.md`](./CODERABBIT-TRIAGE.md) for the list.
- **`is_email()` guard + batching** in `PrivacyEraser::erase_notification_data()` — half-addressed in this PR (null guard + time normalisation); the rest remains Should-fix.
- **`wp_fast_hash` shim cleanup in core** — out of BIS scope; covered in the pre-ship checklist above.

## Handoff ↔ sprint reconciliation

Reconciled against the [2025-08-23 "Back in Stock Notifications: Paused" handoff post](https://somewherewarmattic.wordpress.com/2025/08/23/handoff-back-in-stock-notifications-paused/) on 2026-04-22.

**Covered by a sprint issue:**

| Handoff item | WOOPLUG | Sprint issue | Status |
| --- | --- | --- | --- |
| Verify/confirm emails | 5410 | [RSM-438](https://linear.app/a8c/issue/RSM-438) | Backlog — Urgent |
| Data tracking | 4995 | [RSM-439](https://linear.app/a8c/issue/RSM-439) | Backlog — High |
| Data migration | 5001 | [RSM-442](https://linear.app/a8c/issue/RSM-442) | Scope cut |
| E2E tests | 5412 | [RSM-437](https://linear.app/a8c/issue/RSM-437) | Backlog — Medium (enriched with PR #53641/#55836 context) |
| CodeRabbit review | 5411 | rolled into RSM-436 + RSM-443 | **Done** in [PR #64329](https://github.com/woocommerce/woocommerce/pull/64329) — 8 of 9 Must-fix landed, #9 absorbed into RSM-438 |
| Service init optimization | 5228 | inside RSM-443 | Recommended no action (constructors are cheap) |
| Rate limiter | 4996 | [RSM-441](https://linear.app/a8c/issue/RSM-441) | Backlog — Medium |
| My Account page | 4997 | [RSM-444](https://linear.app/a8c/issue/RSM-444) | Scope cut |
| Copy review | — | inside RSM-443 | Days 9–10 polish |

**In the sprint but not from the handoff** (surfaced from Technical Fitness / PRD review):

- [RSM-440](https://linear.app/a8c/issue/RSM-440) — Design pass on signup form + email (High, Days 1–3)
- [RSM-445](https://linear.app/a8c/issue/RSM-445) — Basic analytics/signups dashboard (Medium, Days 4–8; full dashboard remains out of MVP scope per PRD)
- [RSM-446](https://linear.app/a8c/issue/RSM-446) — Variations form accessibility fix (High, Days 4–8)

**Correctly excluded as beyond-i1** (handoff matches sprint scope):

- Gutenberg Email Editor support
- Frontend Blocks: BIS form block for single-product template
- `BatchProcessingController` improvements

**Gaps — handoff items that were not covered by the original sprint (RSM-436 … RSM-446):**

- [x] **Docs:** "Move and refine documentation in Core" → filed as [RSM-671](https://linear.app/a8c/issue/RSM-671) (Low, deferred post-sabbatical).
- [x] **Integrations:** Assess current BIS plugin integrations with Product Bundles, Subscriptions, Pre-Orders → filed as [RSM-673](https://linear.app/a8c/issue/RSM-673) (Medium, assessment only — may surface unbudgeted scope).
- [ ] **Post-merge coordination:** CfT (internal/external) + Product/Marketing follow-ups (handoff § Other Work). Post-merge by definition; needs a Product owner once this PR lands — not engineering scope to file.

## Open questions

Holding tank for things we need product/team decisions on. Empty = nothing blocked on others right now.

None open.
