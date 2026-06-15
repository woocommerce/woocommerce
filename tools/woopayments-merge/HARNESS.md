# WooPayments → WooCommerce core — verification harness (implementor runbook)

**Audience:** the autonomous implementor merging WooPayments into WooCommerce core, **end-to-end
with no human in the loop (no-HITL)**. This is the operating guide: what the env gives you, every
gate, how to run the loop, what green/red means, and how to extend it as you migrate each surface.

The harness is *the enabler of no-HITL.* It turns "a human could click through and eyeball it" into
"an agent drives a flow, captures structured output, diffs it automatically, in a loop, fail-closed."
Everything here is **local-only** — the two local stores, the local WPCOM/Transact env, and the
Stripe CLI for raw source. **Never the remote WPCOM sandbox.**

Design context: `../../../ai-prompts/goals/woopayments-merge/` — `design-spec.md` (architecture,
§4.5 arbiter, §5.3 perf surfaces), `follow-up/implementation-plan.md` (stages A0–A6, gates),
`follow-up/bc-manifest.md` (the 5 hard-preserve external sets + dispositions), `follow-up/staging-log.md`
(gate evidence). Tool reference: `README.md` (next to this file).

---

## 1. The substrate (already provided — do not rebuild)

### 1a. Environment topology — READ THIS FIRST, do not conflate the checkouts

There are **two WooCommerce-core checkouts on this machine, on purpose**, and a **third unrelated one**:

| Checkout (path) | Role | Local env | Why |
|---|---|---|---|
| **`~/Work/a8c/woocommerce-develop`** | **pristine WC trunk** (unmodified) | **REFERENCE / oracle** — `http://localhost:8082` | the baseline you diff against — must stay unmodified |
| **`~/Work/a8c/woocommerce-develop-2`** (THIS repo) | **our working clone** — where native payments is built | **TARGET** — `http://store8889.localhost:8889` | where all your changes go |
| `~/Work/a8c/woocommerce-develop` again, separate env | unrelated day-to-day WC work | `http://localhost:8888` | **NOT this project — ignore it** |

**Why two checkouts (critical mental model):** the reference must run *unmodified* WC + the
*unmodified* WooPayments plugin to be a valid oracle. If the reference mounted *our* working clone,
our native changes would appear in **both** the reference and the target — there'd be nothing clean
to diff against. So the reference uses a **separate pristine `woocommerce-develop`** clone. Both
checkouts **start aligned on the same WC trunk commit**; the *only* intended difference is our native
work in `woocommerce-develop-2`. **Discipline:** keep the two WC bases aligned to the same trunk
point over time (realign periodically) so the parity diff reflects *only* our changes.

The **WooPayments plugin is unmodified** by this merge, so both envs mount the **same**
`~/Work/a8c/woocommerce-payments` repo as the plugin — there is **no separate plugin clone** (the
retired stand-down model needed one; the settled model does not). Plugin active-state is per-env
(per-DB), so deactivating it on the target for cutover tests never touches the reference.

### 1b. How to reach each piece

| Piece | What | How you reach it |
|---|---|---|
| **Reference store** (oracle) | pristine `woocommerce-develop` WC + unmodified WooPayments plugin + connected account + Test Lab, on `:8082` | `WP="docker exec -i wcpay_wp_default wp --allow-root"` |
| **Target store** | this repo's `woocommerce-develop-2` WC core + unmodified WooPayments plugin (active) + dev-tools + subscriptions, on `:8889` | `WP="docker exec -i $(docker ps --format '{{.Names}}' | grep -- '-cli-1' | grep -v tests) wp"` — currently `24860d14de30dc62f7b324ebef10b5fb-cli-1` (the hash is wp-env-instance-specific) |
| **Connected test account** | processes test payments | **`:8082` connected ✓. `:8889` PENDING** (needs local-WPCOM/Transact onboarding — see §1c) |
| **Event listener** | provider events → stores (operator-run) | reference routes to the **legacy Transact** (`~/Work/a8c/transact-platform-server`): `npm run stripe listen` |
| **Stripe CLI** | raw provider source (charges/refunds/disputes/payouts) | `stripe charges retrieve … --stripe-account <connected_acct>`; needs a valid `stripe login` |
| **Dev-tools Test Lab** | mints real orders/charges/refunds/disputes/payouts | `wp wcpay-dev test-lab <charges|refunds|disputes|payouts>` (account-dependent ops need the connected account) |
| **Tracks sink** | captures **all** Tracks (client + server) the store posts, as NDJSON | wpcom-local sink at `~/.wpcom-local/logs/<checkout>/provider-sinks/tracks-events.ndjson` (CLI `wpcom-local tracks …` — see §1c) |
| **Existing test suites** | what the harness extends, not replaces | WC core `tests/e2e-pw`, `playwright.performance.config.ts`, `tests/performance`, `tests/metrics`, `tests/php`; plugin `tests/e2e`, `tests/fixtures` |

### 1c. Setup status of the TARGET env (`:8889`) — for the implementor

- **WC core (`woocommerce-develop-2`) mapped + WooPayments plugin mounted & active** ✓ (arbiter returns `owner=plugin` while the plugin is active).
- **Connected account: PENDING.** No Stripe/Transact account is connected on `:8889` yet, so native/plugin payment *processing* there is not exercisable until onboarding is done via the local WPCOM/Transact integration.
- **Tracks → sink bridge: INSTALLED, end-to-end validation pending.** The `wpcom-local-helper` plugin (`~/Work/a8c/wpcom-local-helper`) is mounted **and active on both envs** (`:8082` via `woocommerce-payments/docker-compose.override.yml`; `:8889` via `.wp-env.override.json`) — this is the bridge that posts a store's Tracks to the local WPCOM sink endpoint; without it the sink stays empty for our stores. The bridge→sink path itself is proven (the sink already holds a real `browser_tkq` event). **Not yet confirmed for our stores:** that `:8082`/`:8889` events now land in the sink — this needs a **browser/web request** (a CLI `wp eval` Tracks call does not do the real HTTP pixel post the bridge rewrites, so it isn't a valid test). Also note the `wpcom-local` CLI is config-version-blocked (read the sink NDJSON directly meanwhile): `~/.wpcom-local/logs/<checkout>/provider-sinks/tracks-events.ndjson`.
- The `:8889` wp-env config lives in `plugins/woocommerce/.wp-env.override.json` (the source of truth for its plugin mappings); a clean `wp-env start` regenerates the Docker compose from it.

**Oracle discipline:** never edit `~/Work/a8c/woocommerce-develop` or `~/Work/a8c/woocommerce-payments`
working trees (the reference's WC + plugin). All your changes go in **`woocommerce-develop-2`** only.

---

## 2. The one command — the verification loop

```sh
# A0 — prove the harness agrees with reality on the UNMODIFIED plugin (the trust gate):
tools/woopayments-merge/verify.sh --self-check "docker exec -i wcpay_wp_default wp --allow-root"

# A1+ — cross-store parity (reference plugin vs target native), once native emits:
tools/woopayments-merge/verify.sh --ref "<ref WP>" --target "<target WP>" [--with-tracks]
```

`verify.sh` runs every gate, prints a per-gate verdict, and sets an **aggregate exit code** you can
loop on:

- **exit 0 / RESULT: PASS** — every gate agrees with reality. Safe to advance.
- **exit 1 / RESULT: FAIL** — a real regression. **Stop. Do not advance.** Read the failing gate's diff.
- **exit 3 / RESULT: INCOMPLETE** — a precondition is unmet (e.g. no `stripe login`); not a regression,
  but not green either. Fix the precondition and re-run.

The A0 self-check must be green **before any native code exists** — that is the proof the harness
itself is trustworthy. (Validated: 5/5 gates PASS on the unmodified plugin.)

---

## 3. The gates — and EXACTLY what each does / does not cover

> **Read this honestly. A green gate means only what its "covers" column says — never more.** The
> harness is a change-detection + determinism substrate for a *bounded* surface, not a comprehensive
> merge verifier. Full per-tool capability ledger: `../../../ai-prompts/goals/woopayments-merge/follow-up/harness-capability-audit.md`.

### Automated-deterministic gates (trust within the bound)

| Gate | Covers (deterministic) | Does NOT cover |
|---|---|---|
| **BC + Tracks drift** (`bc-drift-gate.sh`) | grep-matched BC surface didn't change vs baseline | dynamic/variable hook & event names, var-built meta keys, indirect registrations; it's drift on the *reference*, not proof native reproduces it |
| **Bucket-E parity** (`parity-diff.sh`) | byte-identical **status / pattern-matched meta / notes / refunds / total / txn_id** for the **orders you dump** | meta keys outside the pattern; customer/token/subscription/session/option state; **final state only, not the transition sequence**; only sampled orders |
| **Financial reconciliation** (`financial-reconcile.sh`) | WC total-refunded === Stripe `amount_refunded` for given charges; fail-closed | **refunds only** — not charge amount, captures/auths, disputes, payouts, fees, multi-currency |
| **Tracks parity** (`tracks-parity.sh`, via the wpcom-local sink) | name + normalized props for **both client (`browser_tkq`) and server (`server_pixel`)** Tracks the store posts, attributed by `store_id` | only events a *driven flow actually fires* (drive the surface); cross-store needs store-config alignment (§5) |
| **Perf probe** (`perf-baseline.sh`) — *narrow* | query-count on **3 gateway-resolution surfaces** | checkout render, admin pages, `process_payment`, cold cache, **bundle size (RULE 3)** — this is a weak signal, NOT RULE-1 verification |

Each is **fail-closed** (refuses PASS unless it positively verified the property).

### Runbooks (JUDGED by the implementor — NOT automated; do not fake a PASS)

For these, run the playbook against the local env (WP-CLI / browser) and record a judged verdict + evidence:

- **Browser checkout matrix** — classic/Blocks/express/WooPay, **3DS/SCA**, saved-method, redirect. Extend `tests/e2e-pw` + WooPayments `tests/e2e`; judge the result. (`flow-drive.sh` only exercises *server-side* `process_payment` — it does **not** cover the browser/Stripe.js flows.)
- **Admin screens** render/behavior/network vs reference; **bundle size**; **broad perf** (`tests/performance`); **disputes/payouts/captures/fees** financial matrix; **subscription renewals / token meta / multi-currency** state.

(Client-side Tracks are **no longer a runbook** — they're captured deterministically at the wpcom-local sink alongside server-side, §5. The remaining Tracks caveat is just that you must *drive the surface* that fires them, and align store config for cross-store.)

---

## 4. Flow drivers — exercising a surface deterministically

```sh
# Mint a real order+charge (returns structured json with the order id):
WP="$REF" tools/woopayments-merge/flow-drive.sh charge --count=1 --type=success
#   -> {"op":"charge","order_id":352,"charge_id":"ch_..."}
WP="$REF" tools/woopayments-merge/flow-drive.sh refund  --type=partial
WP="$REF" tools/woopayments-merge/flow-drive.sh dispute
WP="$REF" tools/woopayments-merge/flow-drive.sh payout
```

Drivers go through the Test Lab, which calls the **real** gateway (`process_payment`/refund), so
orders and Stripe objects link exactly as in production. Feed the emitted `order_id`s into the gates:

```sh
ids=$(WP="$REF" flow-drive.sh charge | sed -n 's/.*"order_id":\([0-9]*\).*/\1/p')
WP="$REF" parity-diff.sh --self-check "$REF" $ids
WP="$REF" financial-reconcile.sh $ids
```

Richer flows (classic/Blocks/express/WooPay checkout, 3DS/SCA, saved-method, subscription renewal)
extend the existing Playwright suites (`tests/e2e-pw`, plugin `tests/e2e`) — drive the browser flow,
then capture the Bucket-E + Tracks surface on the resulting order.

---

## 5. Shadow mode & cross-store parity (the A1 activation)

At **A0** the parity differs run in **self-check** (one store, twice → zero diff) because there is no
native output yet. From **A1 (shadow mode)** onward they become **cross-store**:

1. Drive the *same* input on both stores (or run native read-only beside the plugin on the same store
   via the shadow hook).
2. Capture the surface on each: Bucket-E via `dump-bucket-e-surface.sh`; Tracks via the capture below.
3. Diff: **zero diff on the preserve surface** is the gate. Any diff is a RULE-0 regression.

### Tracks runtime parity recipe (capture at the wpcom-local sink)

Tracks are captured at the **wpcom-local sink** — the local endpoint every store posts to (via the
`wpcom-local-helper` bridge), capturing **both** client (`source: browser_tkq`) and server
(`source: server_pixel`) events. This is the actual pipeline destination, so it's complete — no
per-emitter spy needed. **Prereqs (validated in place):** the helper is active on both stores, WC
usage tracking is on (`woocommerce_allow_tracking=yes` — `WC_Tracks` early-returns otherwise), and
the wpcom-local sink is enabled (`wpcom-local tracks status`).

The sink is **shared by all stores** in the checkout, so discriminate by **`store_id`** (each
install's `woocommerce_store_id` UUID rides on every event). Get each: `wp option get woocommerce_store_id`.

**Same-store parity (the rigorous A4 gate — recommended):** native-off vs native-on on `:8889` — same
store, same config, so the *only* variable is native-vs-plugin (no store-config confound):
```sh
tracks-parity.sh reset            # wpcom-local tracks clear
# ...drive flow with native OFF (plugin emits)...   then capture, then repeat with native ON
tracks-parity.sh normalize --store "$STORE_8889" > plugin.txt   # (after the native-off run)
tracks-parity.sh normalize --store "$STORE_8889" > native.txt   # (after the native-on run; reset between)
tracks-parity.sh diff plugin.txt native.txt
```

**Cross-store parity (reference vs target):** captures both from the shared sink, separated by store_id:
```sh
tracks-parity.sh reset
<drive same flow on :8082 AND :8889>
tracks-parity.sh normalize --store "$STORE_8082" > ref.txt
tracks-parity.sh normalize --store "$STORE_8889" > tgt.txt
tracks-parity.sh diff ref.txt tgt.txt
```
**⚠ Cross-store has store-CONFIG confounds.** Validated: a clean charge on each store matched on every
prop *except* `coming_soon` (`no` on :8082 vs `site` on :8889) — a CORE-added, store-config prop, not a
WooPayments/native difference; aligning it (`wp option update woocommerce_coming_soon no`) → **zero
drift**. So for cross-store you must **align store config** (coming-soon mode, feature flags, products)
between the two envs — the same "keep them aligned" discipline as the WC trunk base. Same-store parity
avoids this entirely; prefer it for the rigorous gate.

The normalizer (`tracks-normalize.py`) freezes event name + prop keys/types + **stable enum string
values** + WCPay's deliberate custom props, while **masking volatile values** (ids, UUIDs, versions,
dates, decimals/amounts, numbers) and dropping the auto-injected envelope; it excludes synthetic sink
sources (`mock`/`helper_smoke`). (Validated against real sink data: PASS when only volatile/config
values differ; FAIL on a prop type change *or* a real enum-value change.)

---

## 6. Decision rules (no-HITL)

- **A gate is red → stop and fix; never advance a stage on a red gate or an undispositioned BC surface.**
  Treat the gate output as ground truth, not your reasoning about it.
- **Validate every "done" against the harness, and every harness finding against source.** For each
  "done" claim, have a fresh subagent try to break it (parity diff, edge case, perf, money-safety)
  before it counts. (This is how the authoring run caught real bugs in its own tools.)
- **Fixed a BC surface intentionally?** Disposition it in `bc-manifest.md`, then `bc-drift-gate.sh --update`.
- **Money path changed?** It does not ship until `financial-reconcile.sh` is green on a driven refund.
- **Touched a surviving surface that emits Tracks?** It does not ship until `tracks-parity.sh` is green
  for that surface (name + props). Telemetry continuity is non-negotiable (`bc-manifest.md` §0.3).
- **Record gate evidence** (numbers/diffs) in `staging-log.md` at the end of each work package.

---

## 7. Extending the harness (per stage / per surface)

- **New persisted meta/notes/refund shape on a surface** → it's already covered by the Bucket-E dump
  (pattern-matched). If a new key family appears, widen `dump-bucket-e-surface.php`'s `$key_pattern`.
- **New BC surface category** → add a probe to `bc-drift-gate.sh` + a `bc-extraction/<cat>.md`, then
  `--update` to baseline.
- **New flow** → add a driver path (Test Lab subcommand or a Playwright spec) that emits the affected
  order id(s); the gates consume them unchanged.
- **New Tracks events** (additive) are fine; the rule only forbids breaking existing contracts on
  surviving surfaces. New events need no disposition beyond appearing in the `tracks` baseline.

---

## 8. Status (what is built vs activated at A1)

Built + validated against the unmodified plugin (A0 trust gate green): drift gate (incl. `tracks`),
Bucket-E dump + parity differ, perf baseline + check, financial reconciliation (proven on a real
refund), flow drivers, Tracks capture + normalizer + differ, and `verify.sh` (5/5 PASS).

Cross-store parity (Bucket-E **and** Tracks props) and the client-side Tracks spy become load-bearing
at **A1 shadow mode** — there is no native output to diff against until then; at A0 they are validated
in self-check. Financial reconciliation e2e needs a valid host `stripe login`.
