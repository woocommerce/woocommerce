# Ecosystem contract tooling

Makes WooCommerce's *implicit* developer contract measurable, so every breaking
change in a release is a priced, deliberate decision instead of a production
surprise.

Two halves:

1. **Usage index** — a static scan of the top 500 WooCommerce extensions and top 50
   themes on WP.org, recording every WooCommerce surface each one touches: hooks,
   classes/interfaces extended or implemented, `wc_*` function calls, and
   `woocommerce`/`wc-` CSS selectors and DOM queries.
2. **Release gate** — diffs a release candidate's API surface against the previous
   stable release, intersects each breaking change with the index, and reports
   *what breaks, for whom, and how many installs* — blocking the release tag on
   unacknowledged high-impact breaks.

The motivating incident: WooCommerce 10.9.0 added `get_entry_count(): int` to
`Internal\ProductFeed\Feed\FeedInterface` (PR #64394). Older WooCommerce Stripe
Gateway versions implement that interface, so they fatalled on load, and 10.9.0 was
reverted on WP Cloud (fixed in #65965). This gate flags exactly that class of change
before the tag is cut. Running it retroactively on 10.8.0 → 10.9.0 is the acceptance
test (see below).

## Pipeline

The **usage index is built externally** by a daily scheduled workflow in
[jamesckemp/ecosystem-usage-index](https://github.com/jamesckemp/ecosystem-usage-index)
(pending transfer to the woocommerce org) and published as a release asset on its
rolling `usage-index-latest` release.

**Getting the index:** the index repo is currently private, so either ask
@jamesckemp for a copy of `usage-index.sqlite` (or for read access to the repo,
then `gh release download usage-index-latest -R jamesckemp/ecosystem-usage-index`).
Place it at `data/usage-index.sqlite` — it is gitignored. Once the repo is
org-owned/public this becomes an anonymous download.

The gate side, in this directory:

```sh
# 1. Extract the API surface of two releases
python3 extract_surface.py rel-10.8.0 --out surface-10.8.0.json
python3 extract_surface.py rel-10.9.0 --out surface-10.9.0.json

# 2. Diff them
python3 diff_surface.py surface-10.8.0.json surface-10.9.0.json --out diff.json

# 3. Gate: intersect with the index, produce the report
python3 gate.py --diff diff.json --index data/usage-index.sqlite \
  --report gate-report.md --json-out findings.json
# exit 0 = clean; exit 2 = blocking breaks found
```

Stdlib-only Python 3; no PHP runtime, no composer, no network needed (release
trees come from downloads.wordpress.org zips).

## Querying the index directly

```sh
sqlite3 data/usage-index.sqlite \
  "SELECT plugin, active_installs FROM usage
   WHERE surface LIKE '%FeedInterface' AND kind='class_implement'
   ORDER BY active_installs DESC"
```

Kinds: `hook`, `class_extend`, `class_implement`, `class_ref`, `function`, `selector`.

## Acceptance test (known-answer)

Nothing generated is committed — `data/` is gitignored workspace. Regenerate the
10.8→10.9 diff (~2 min, public zips) and run the gate:

```sh
mkdir -p data && cd data
for v in 10.8.0 10.9.0; do
  curl -sL -o wc-$v.zip https://downloads.wordpress.org/plugin/woocommerce.$v.zip
  unzip -qo wc-$v.zip -d rel-$v
done
cd ..
python3 extract_surface.py data/rel-10.8.0 --out data/s-10.8.0.json
python3 extract_surface.py data/rel-10.9.0 --out data/s-10.9.0.json
python3 diff_surface.py data/s-10.8.0.json data/s-10.9.0.json --out data/diff.json
python3 gate.py --diff data/diff.json --index data/usage-index.sqlite --ack acks.json --report data/report.md
# MUST exit 2 and flag:
#   interface_method_added: Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedInterface::get_entry_count
#   naming woocommerce-gateway-stripe among the affected implementers
```

## Refreshing the index

Handled entirely by the index repo's daily workflow — nothing in this directory
holds index state (`data/` is gitignored workspace). Just re-download a current
copy before a gate run (see "Getting the index" above). Once the index repo is
org-owned and public, `ci/release-gate.yml` fetches it automatically at tag time.

For historical reproductions ("what did the ecosystem look like when X.Y.0
shipped"), the index repo's fetcher supports version pinning:
`--pin woocommerce-gateway-stripe=9.5.1`.

## What the differ flags

- Removals and signature changes: classes, interfaces, public/protected methods,
  `wc_*` functions, hooks (including reduced arg counts), block selectors.
- **Interface/abstract method additions** — existing implementers no longer satisfy
  the contract and fatal on load (the 10.9.1 `FeedInterface` class of failure).
- **Newly-declared return types over filter-influenced values** — a method that
  newly declares a return type on a value third parties supply via `apply_filters`
  (directly, via a filter-provided callable, or forwarded one call deep within the
  class) fatals when a filter returns a non-conforming type (the 10.9.3
  `WC_Email::send_notification(): bool` class of failure). Affected extensions are
  attributed via their hook usage in the index.

## Known limitations (v1)

- **Regex-based PHP scanning**, not AST: dynamic hook names are recorded with a
  trailing `*`; reflection/metaprogramming usage is invisible; a small
  false-negative rate is expected. Trade: zero runtime deps, scans 550 plugins in
  minutes.
- **Corpus = WP.org top lists.** Commercial extensions from the WooCommerce.com
  marketplace and CodeCanyon are not scanned (no public source endpoint). Tier 0
  ("unused") therefore means *no evidence*, not proof — see decision-rules.md.
- **Rendered markup is proxied** by `wc-block-*` class-string literals in PHP, not
  actual rendered DOM. A runtime render-diff (Playground-based) is the natural v2.
- **Store API schemas** are covered indirectly via the schema classes' public-method
  surface, not the JSON schema output itself.
- Method bodies using `create_function`/`eval` and interfaces satisfied via
  `__call` are invisible to both sides of the pipeline.

## Files

| File | Purpose |
| --- | --- |
| `extract_surface.py` | Extract classes/functions/hooks/selectors from a release tree |
| `diff_surface.py` | Diff two surfaces → candidate breaking changes |
| `gate.py` | Intersect diff with index → tiered report + exit code |
| `decision-rules.md` | What each tier requires of the release lead |
| `ci/release-gate.yml` | Draft GitHub Actions workflow (tag-time gate) |
