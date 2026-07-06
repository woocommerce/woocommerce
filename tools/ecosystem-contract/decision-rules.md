# Breaking-change decision rules

Every candidate breaking change in a release candidate is classified by the gate
(`gate.py`) against the ecosystem usage index. The tier determines what the release
lead must do — the goal is not "never break", it is **no unknowing breaks**: every
break that ships is priced, approved, and traceable to a named decision.

## Tiers

### Tier 0 — Unused surface → break freely

No plugin or theme in the corpus (top 500 WooCommerce extensions + top 50 themes on
WP.org) touches the surface.

- **Action:** proceed. Add a line to the release changelog under "Developer-facing
  changes". No approval needed.
- **Caveat:** the corpus does not see private/custom code or off-WP.org commercial
  extensions (WooCommerce.com marketplace, CodeCanyon). Tier 0 means *no evidence of
  use*, not *proof of non-use*. High-traffic surfaces (hooks fired on every page
  load, checkout classes) deserve judgement even at tier 0.

### Tier 1 — Low-usage surface → break with notification

Used, but by extensions totalling fewer than 100k active installs (configurable via
`--notify-threshold`).

- **Action:** proceed, but before the release ships: file an issue or email to each
  affected extension's author (the report names them), and list the break prominently
  in the release post. Prefer a deprecation shim if it costs less than an hour.

### Tier 2 — High-impact surface → deprecation window required

Either ≥100k total installs depend on the surface, **or** the change is
critical-severity with fatal-class usage (a plugin `implements`/`extends` the changed
contract — these fatal on load, they don't degrade).

- **Action:** the gate blocks the release tag. Options, in order of preference:
  1. **Don't break it.** Add the method to the concrete class instead of the
     interface; introduce a new interface alongside; supply a default via an abstract
     base class; keep the old hook firing.
  2. **Deprecate with a shim.** Old symbol stays working, logs a deprecation notice,
     removal scheduled ≥2 majors out and announced in the developer blog.
  3. **Acknowledge and ship anyway.** Requires an entry in the ack file
     (`--ack acks.json`) with a reason and an approver. This is the escape hatch for
     genuinely necessary breaks — it exists so the decision is *recorded*, not to be
     routine.

## Hard rules (regardless of tier)

- **Never add a method to an existing interface that external code implements.**
  Existing implementers fatal on load (WooCommerce 10.9.0 / `FeedInterface::get_entry_count`
  / WooCommerce Stripe Gateway — reverted on WP Cloud). Same applies to adding
  abstract methods to non-final abstract classes.
- **Deprecate, don't rename.** Old symbol stays through the deprecation window.
- **`Internal` namespace is not a free pass.** The gate measures actual usage; if the
  ecosystem consumes an Internal symbol, it is a contract in practice. Fixing the
  leak (making it genuinely internal) is a tier-2 project with a deprecation window,
  not a silent change.
- **DOM/CSS:** selector removals are reported at low severity. Selectors documented
  as stable (e.g. `wc-block-*` conventions) should be treated as tier-2 when
  theme/extension usage shows up in the index; undocumented internals follow the
  tiers like any other surface.

## Acknowledgement file format

```json
[
  {
    "surface": "Automattic\\WooCommerce\\Internal\\Foo\\BarInterface",
    "member": "new_method",
    "reason": "Coordinated with the two affected extensions; both shipped compatible versions.",
    "approved_by": "release-lead-handle",
    "linked_pr": "https://github.com/woocommerce/woocommerce/pull/XXXXX"
  }
]
```

An acknowledged tier-2 break passes the gate but stays in the report, so the release
post can enumerate every deliberate break with its rationale.
