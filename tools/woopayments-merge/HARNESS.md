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

| Piece | What | How you reach it |
|---|---|---|
| **Reference store** (the oracle) | current WooPayments behavior on `:8082` (`develop` plugin mounted from `../../../../woocommerce-payments`) | `WP="docker exec -i wcpay_wp_default wp --allow-root"` |
| **Target store** | core (this repo) + the (unmodified) WooPayments plugin, on `:8889` | `WP="docker exec -i <cli-container> wp"` (the `…-cli-1` of the `:8889` wp-env stack) |
| **Connected test account** | processes test payments; connected to the local Transact | already connected on both stores |
| **Event listener** | provider events → stores (operator-run) | reference routes to the **legacy Transact** (`~/Work/a8c/transact-platform-server`): `npm run stripe listen` |
| **Stripe CLI** | raw provider source (charges/refunds/disputes/payouts) | `stripe charges retrieve … --stripe-account <connected_acct>`; needs a valid `stripe login` |
| **Dev-tools Test Lab** | mints real orders/charges/refunds/disputes/payouts | `wp wcpay-dev test-lab <charges|refunds|disputes|payouts>` |
| **Existing test suites** | what the harness extends, not replaces | WC core `tests/e2e-pw`, `playwright.performance.config.ts`, `tests/performance`, `tests/metrics`, `tests/php`; plugin `tests/e2e`, `tests/fixtures` |

**Oracle discipline:** the reference store (`:8082`) must stay the *pristine, unmodified plugin* —
never edit `../../../../woocommerce-payments`'s working tree. This merge makes **no plugin-side
changes** (a site moves to native by core deactivating the plugin — `design-spec.md` §4.5a), so there
is no plugin dev-clone to maintain; the parity oracle is simply the unmodified plugin as shipped.

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
| **Tracks parity — server-side** (`tracks-parity.sh` + `tracks-capture.php`) | the ~33 **PHP** Tracks emitters' name + normalized props | the ~156 **client-side JS** emitters (the majority) — see runbooks |
| **Perf probe** (`perf-baseline.sh`) — *narrow* | query-count on **3 gateway-resolution surfaces** | checkout render, admin pages, `process_payment`, cold cache, **bundle size (RULE 3)** — this is a weak signal, NOT RULE-1 verification |

Each is **fail-closed** (refuses PASS unless it positively verified the property).

### Runbooks (JUDGED by the implementor — NOT automated; do not fake a PASS)

For these, run the playbook against the local env (WP-CLI / browser) and record a judged verdict + evidence:

- **Browser checkout matrix** — classic/Blocks/express/WooPay, **3DS/SCA**, saved-method, redirect. Extend `tests/e2e-pw` + WooPayments `tests/e2e`; judge the result. (`flow-drive.sh` only exercises *server-side* `process_payment` — it does **not** cover the browser/Stripe.js flows.)
- **Client-side Tracks** — the JS emitters via the `window.wcTracks.recordEvent` spy (§5; spec'd, **not yet built/validated**).
- **Admin screens** render/behavior/network vs reference; **bundle size**; **broad perf** (`tests/performance`); **disputes/payouts/captures/fees** financial matrix; **subscription renewals / token meta / multi-currency** state.

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

### Tracks runtime parity recipe (both stores)

```sh
# one-time per store: install the capture drop-in + enable tracking
docker cp tools/woopayments-merge/tracks-capture.php <container>:/var/www/html/wp-content/mu-plugins/
WP eval 'update_option("woocommerce_allow_tracking","yes");'   # WC_Tracks early-returns otherwise

# per comparison:
WP="$REF" tracks-parity.sh reset ; <drive flow on ref> ; WP="$REF" tracks-parity.sh normalize > ref.txt
WP="$TGT" tracks-parity.sh reset ; <drive flow on tgt> ; WP="$TGT" tracks-parity.sh normalize > tgt.txt
tracks-parity.sh diff ref.txt tgt.txt      # PASS = same name+props contract; exit 1 on drift
```

The normalizer (`tracks-normalize.py`) freezes name + prop keys/types + **stable enum string values**
and the custom envelope props WCPay adds, while **masking volatile values** (ids/timestamps/numbers)
and dropping the auto-injected envelope — exactly the §0.3 boundary. (Validated: PASS when only volatile
values differ; FAIL on a type change *or* an enum-value change.)

`tracks-capture.php` is the **server-side** half (the WC_Tracks path). The **client-side** majority
(admin React + shopper) is captured by spying the JS sink inside the e2e flow drivers — add to a
Playwright `addInitScript`:

```js
await page.addInitScript(() => {
  window.__wcpayTracks = [];
  const sink = (name, props) => window.__wcpayTracks.push({ event: name, props: props || {} });
  const hook = () => {
    const t = window.wcTracks || (window.wc && window.wc.tracks);
    if (t && t.recordEvent && !t.__spied) {
      const orig = t.recordEvent.bind(t);
      t.recordEvent = (n, p) => { sink(n, p); return orig(n, p); };
      t.__spied = true;
    }
  };
  hook(); new MutationObserver(hook).observe(document.documentElement, { childList: true, subtree: true });
});
// after the flow: const events = await page.evaluate(() => window.__wcpayTracks);
// pipe `events` (as JSONL) through tracks-normalize.py and diff reference vs target.
```

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
