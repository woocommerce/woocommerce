# WooPayments → WooCommerce core — merge transition tooling

Transition-only tooling for merging the WooPayments client into WooCommerce core
(Option C, "truly native"). Everything here is scaffolding for the staged merge and
is **removed at stage A6 cleanup** — it is not part of the shipped product.

Design + staging: `~/Work/a8c/ai-prompts/goals/woopayments-merge/`
(`design-spec.md`, `staging-outline.md`, `follow-up/implementation-plan.md`,
`follow-up/bc-manifest.md`, `follow-up/bc-extraction/`).

> **Local-only.** All verification runs against the **local** stores and the **local**
> WPCOM/Transact env, plus the **Stripe CLI** for raw account/event data. Never the
> remote WPCOM sandbox.

> **Start here for running the merge:** [`HARNESS.md`](HARNESS.md) — the implementor runbook
> (env, gates, the one-command loop `verify.sh`, shadow-mode/cross-store activation, decision
> rules, how to extend). This README is the per-tool reference.

---

## Lifecycle — keeping this OUT of WooCommerce core

This harness is **transition-only** and must not land in WooCommerce trunk when the merge PRs merge.
Three properties already protect that, plus one action to take:

- **It never ships to users.** It lives at repo-root `tools/`, **outside `plugins/woocommerce/`**, so it
  is never in the built/released plugin zip (built from `plugins/woocommerce/` only) — true even now.
- **It is fully self-contained.** Everything is in this one directory; no shipped code, CI, or build
  config references it. `git rm -r tools/woopayments-merge` removes it with zero impact on core.
- **Its commits are separable.** Every harness commit touches only this directory; the shipped A0
  product (the `NativePaymentsRuntimeArbiter` + its test + changelog, under `plugins/woocommerce/`) is
  in separate commits.

**Action — pick one so it never reaches trunk:**
- *Recommended:* don't include this directory in the core-code PR(s) — ship only the product commits
  (keep the harness on a parallel tooling branch, or strip it before the final merge).
- *Or:* let it ride the branch and delete it as the **first action of stage A6**:
  `git rm -r tools/woopayments-merge && git commit -m "Remove WooPayments-merge transition harness"`.

The plugin-side reverse-gated stand-down is NOT here — it lives in a dedicated `woocommerce-payments`
clone (the plugin repo), never in WC core.

---

## 1. The runtime arbiter (A0 keystone) — built

`plugins/woocommerce/src/Internal/Payments/NativePaymentsRuntimeArbiter.php`

Single source of truth for **which payments runtime owns a site** — the standalone
WooPayments plugin or core-native — guaranteeing mutual exclusion (design-spec §4.5).
Every core-native registration must consult `should_native_register()` before doing
anything mutating; while the plugin owns the runtime, native registers nothing.

Detection uses the **active-plugins list** (per-site `active_plugins` + network
`active_sitewide_plugins`), which is reliable in the early-boot window and correct
per-site under multisite — `class_exists('WC_Payments')` is only a fallback, because
the plugin defines that bootstrap class late (`wcpay_init()` at `plugins_loaded:11`,
an explicit require, not the autoloader).

Dormant by default: `OWNER_NATIVE` requires the `woocommerce_native_payments_enabled`
flag (off in A0) **and** the plugin to be absent (or to have yielded).

### Reverse-gated plugin stand-down handshake (cross-repo)

The arbiter exposes the seam; the **WooPayments plugin** must consult it (a coordinated
reverse-gated release in the `woocommerce-payments` repo, not this repo):

| Filter | Owner | Meaning |
|---|---|---|
| `woocommerce_native_payments_enabled` | core (feature flag) | native runtime enabled for this site |
| `woocommerce_native_payments_plugin_yielded` | **the plugin** | plugin is present but has stood its runtime down → native may take over |
| `woocommerce_native_payments_runtime_owner` | ops/escape hatch | **conservative only**: `none` = global kill switch; `plugin` = force plugin-wins (when present). Cannot promote native over a present, non-yielded plugin. |

**The handover contract (must be atomic in the plugin release):** when the plugin
decides native should own, it returns `true` for `..._plugin_yielded` **and** skips
booting its own runtime, in lockstep. The arbiter only lets native take over a still-
present plugin when that yield signal is set — so a stray/third-party filter cannot
induce a dual-runtime state. The dangerous direction (force-native) is fenced; only the
plugin's own yield opens it.

A5 (cutover) hardens the yield into a verified, option-backed handshake written by the
plugin's stand-down routine, plus the webhook/AS drain-or-rebind continuity gate
(§4.5 #4) — the arbiter provides the ownership signal that gate guards. Until the
reverse-gated release ships and saturates, **plugin-wins** is the only state on any
site that has the plugin active.

---

## 2. BC-manifest drift gate (A0) — built

`bc-drift-gate.sh` — re-runs the BC extraction commands (the "Regeneration Commands"
blocks in `follow-up/bc-extraction/*.md`) against the **live** WooPayments source,
normalizes each category into a churn-stable surface signature, and diffs against a
committed baseline in `bc-drift-baseline/`. Any undispositioned add/remove of a BC
surface line fails the gate. It is a **drift gate, not a re-extraction**: the prose
inventories remain the human disposition record; the baseline is the machine snapshot.

```sh
# Check (CI + local gate): exit 1 on any drift.
tools/woopayments-merge/bc-drift-gate.sh

# Accept the current live surface as the new baseline (after dispositioning).
tools/woopayments-merge/bc-drift-gate.sh --update

# Point at a specific WooPayments checkout (CI):
WCPAY_SRC=/path/to/woocommerce-payments tools/woopayments-merge/bc-drift-gate.sh
```

Categories & captured baseline sizes (raw match-lines; higher than the corpus's deduped
prose counts because each grep hit is one line): `scheduler` 161, `php_api` 307,
`persisted_data` 755, `endpoints` 63, `hooks_filters` 91, `tracks` 159.

The **`tracks`** category enforces the non-negotiable telemetry-continuity contract
(`bc-manifest.md` §0.3/§3.6, `bc-extraction/tracks-events.md`): the roster of Tracks
emitters (PHP recorders + JS `recordEvent` call sites + the `Track_Events` constants) must
not silently change for surfaces that survive the merge. This is the *static, name-level*
half; *prop-level* parity is the runtime Tracks-parity check (§3).

**When the gate fails:** disposition each new/removed row in `bc-manifest.md`
(PRESERVE / PRESERVE-AS-FACADE / REDESIGN-FREELY / EXTRACT / DROP / DROP-AFTER-MIGRATION),
then `--update` to accept the new baseline. This is the check that would have caught the
"two AS groups" slip (implementation-plan §1).

---

## 3. Verification harness (A0 §1) — status

The harness is the **delta** on top of the already-wired env + existing test suites
(`tests/e2e-pw`, `playwright.performance.config.ts`, `tests/performance`,
`tests/metrics`, `tests/php`; WooPayments `tests/e2e` + `tests/fixtures`; WP-CLI):

| Piece | Status | Notes |
|---|---|---|
| **Orchestrator** | **built** | `verify.sh` — one entry point; runs every gate, per-gate verdict + aggregate exit; self-check (A0) + cross-store (A1). 5/5 PASS on the unmodified plugin |
| Manifest drift check | **built** (§2) | `bc-drift-gate.sh`; 6 categories incl. `tracks`; PASS + fail-closed proven |
| Bucket-E surface dump | **built** | `dump-bucket-e-surface.{php,sh}` — per-order status/meta/notes/refunds; deterministic |
| Parity differ | **built** | `parity-diff.sh` — self-check PASS on unmodified plugin; fail-closed proven; env-noise excluded at data level |
| Perf baseline + check gate | **built** | `perf-baseline.{php,sh}` + `perf-baseline.json` — §5.3 surfaces, query-count RULE-1 gate |
| Financial reconciliation | **built; e2e proven** | `financial-reconcile.sh` — reconciled a real £48 refund (WC 4800 === Stripe 4800 on the connected account); fail-closed |
| **Flow drivers** | **built** | `flow-drive.sh` — charge/refund/dispute/payout via Test Lab → structured order ids the gates consume |
| **Tracks parity** | **built** | static: the `tracks` drift category. runtime: `tracks-capture.php` (server) + `tracks-normalize.py` + `tracks-parity.sh` (validated: PASS on masked volatile values; FAIL on type *or* enum-value drift) + the client spy in `HARNESS.md`. Enforces `bc-manifest.md` §0.3/§3.6 |

The A0 harness gate — "reproduces the status quo on the *unmodified* plugin before any
native code exists" — **is met**: `verify.sh --self-check` is 5/5 green on the unmodified plugin
(drift incl. `tracks`, flow-drive, Bucket-E parity, perf, financial reconciliation on a real refund).
The **cross-store** parity differs (Bucket-E **and** Tracks props) and the client-side Tracks spy
become load-bearing at **A1 shadow mode** (no native output to diff against until then); at A0 they
are validated in self-check. See [`HARNESS.md`](HARNESS.md) for the full operating loop.

### Listeners (operator-run)

Provider events reach the stores via a listener the operator runs. The reference store
(`:8082`) routes to the **legacy Transact env** (`~/Work/a8c/transact-platform-server`):
`npm run stripe listen`. (A store routed to wpcom-local would use `transact listen`.) Never
the remote WPCOM sandbox; the Stripe CLI is the only allowed raw-source reader.

## 4. Reverse-gated plugin stand-down — built (dev copy in a clone)

The plugin side of the handshake lives in a **dedicated clone** (`~/Work/a8c/woocommerce-payments-standdown`,
branch `feat/native-standdown`), wired into this repo's `:8889` env via the wp-env override
(`plugins/woocommerce/.wp-env.override.json` maps `woocommerce-payments` → the clone). The
reference env (`:8082`) stays on the pristine `../woocommerce-payments` as the parity oracle.

- `includes/class-wc-payments-native-standdown.php` — `WC_Payments_Native_Standdown`:
  `should_stand_down()` = core-native present **AND** native enabled **AND** rollout gate open
  (fail-closed on all three); `yield_to_native()` (atomic with skip-boot); `record_saturation()`.
- Gates in `wcpay_init` (atomic yield + skip-boot + saturation), `wcpay_init_subscriptions_core`,
  `wcpay_tasks_init`. Jetpack init left ungated (connection is shared infra).
- Validated: plugin logic `tests/unit/test-native-standdown-logic.php` → 12/12; core handshake
  proven live in `:8889` (native-on + yielded → owner=native; forced-native-without-yield fenced).

To complete the single-boot integration proof: `pnpm wc:env` (re-provision `:8889` onto the
clone), then enable native + open the rollout gate and confirm the plugin skips its boot.
