# AI review guideline evals

Each of the original 40 review guidelines has two bug cases and two safe boundary cases: 160 cases in total. The root `ai-review-config.json` links each guideline to its suite. The later `perf-blocking-remote-call` guideline is outside this initial corpus.

## Run locally

Use an installed `a8c-review` version with the `eval` command. From the WooCommerce repository root:

```sh
a8c-review eval --plan
a8c-review eval
a8c-review eval --guideline sql-clause-filter-contract
```

`--plan` validates the definitions and reads the fixtures without calling models. Live runs inherit this repository's personas, models, prompts and routing. They require credentials for both the configured Claude and Codex engines. No model override is introduced by these suites.

Each `fixtures/<case-id>/` directory contains before and after source files. The suite's `path` is the simulated WooCommerce source location used for routing and finding matching. Identical scenario comments in both versions supply the original assumptions about callers, settings and framework behavior. These are synthetic source fixtures, not executable WordPress integration tests.

A `finding` expectation requires a finding on that path containing the specified case-insensitive literal. A `quiet` expectation requires no findings, including suppressed ones. Wording changes can fail a case even when the reviewer detects the bug. Routing alone does not determine whether a case passes.

## Fixture corrections and limitations

Three safe controls have explicit replacement IDs:

- `G19C-v2` includes setting normalization and the sole private caller; the original omitted the producer's type contract.
- `G26C-v2` changes a private tuple and its sole consumer together; the original changed a publicly callable positional return.
- `G26D-v2` preserves the type guard after filtering; the original relied on an unproven filtered product type.

The original experiment and its failed outcomes remain preserved separately. These are corrected fixtures, not successful retries of the original cases. The historical runs also included a false positive for `G07D` and missed intended bugs for `G27A` and `G36B`; their intended expectations remain unchanged. Prior semantic adjudications are not passing results from this literal-matching evaluator. In particular, `G36B` requires an injection finding rather than an unrelated finding from the same change.

## Coverage

| Guideline | Bug cases | Safe boundary cases |
| --- | --- | --- |
| [hook-payload-contract](hook-payload-contract/cases.json) | G01A, G01B | G01C, G01D |
| [hook-firing-scope](hook-firing-scope/cases.json) | G02A, G02B | G02C, G02D |
| [php-loose-type-juggling](php-loose-type-juggling/cases.json) | G03A, G03B | G03C, G03D |
| [public-php-api-bc](public-php-api-bc/cases.json) | G04A, G04B | G04C, G04D |
| [upgrade-time-class-load](upgrade-time-class-load/cases.json) | G05A, G05B | G05C, G05D |
| [action-scheduler-lifecycle](action-scheduler-lifecycle/cases.json) | G06A, G06B | G06C, G06D |
| [request-input-validation](request-input-validation/cases.json) | G07A, G07B | G07C, G07D |
| [missing-object-deref](missing-object-deref/cases.json) | G08A, G08B | G08C, G08D |
| [meta-option-value-semantics](meta-option-value-semantics/cases.json) | G09A, G09B | G09C, G09D |
| [hpos-sync-on-read](hpos-sync-on-read/cases.json) | G10A, G10B | G10C, G10D |
| [migration-upgrade-state](migration-upgrade-state/cases.json) | G11A, G11B | G11C, G11D |
| [proxy-predicate-inference](proxy-predicate-inference/cases.json) | G12A, G12B | G12C, G12D |
| [wc-query-shop-front-page](wc-query-shop-front-page/cases.json) | G13A, G13B | G13C, G13D |
| [request-lifecycle-state](request-lifecycle-state/cases.json) | G14A, G14B | G14C, G14D |
| [template-and-js-default](template-and-js-default/cases.json) | G15A, G15B | G15C, G15D |
| [rest-response-contract](rest-response-contract/cases.json) | G16A, G16B | G16C, G16D |
| [session-cart-persistence](session-cart-persistence/cases.json) | G17A, G17B | G17C, G17D |
| [check-then-write-race](check-then-write-race/cases.json) | G18A, G18B | G18C, G18D |
| [order-totals-recalc](order-totals-recalc/cases.json) | G19A, G19B | G19C-v2, G19D |
| [shipping-rate-pipeline](shipping-rate-pipeline/cases.json) | G20A, G20B | G20C, G20D |
| [sql-clause-filter-contract](sql-clause-filter-contract/cases.json) | G21A, G21B | G21C, G21D |
| [cache-invalidation-and-poisoning](cache-invalidation-and-poisoning/cases.json) | G22A, G22B | G22C, G22D |
| [perf-refactor-semantic-drift](perf-refactor-semantic-drift/cases.json) | G23A, G23B | G23C, G23D |
| [fix-and-test-verification](fix-and-test-verification/cases.json) | G24A, G24B | G24C, G24D |
| [perf-sql-query-shape](perf-sql-query-shape/cases.json) | G25A, G25B | G25C, G25D |
| [perf-cache-option-priming](perf-cache-option-priming/cases.json) | G26A, G26B | G26C-v2, G26D-v2 |
| [perf-hot-path-eager-work](perf-hot-path-eager-work/cases.json) | G27A, G27B | G27C, G27D |
| [script-handle-loading-contract](script-handle-loading-contract/cases.json) | G28A, G28B | G28C, G28D |
| [classic-jquery-contract](classic-jquery-contract/cases.json) | G29A, G29B | G29C, G29D |
| [js-public-api-contract](js-public-api-contract/cases.json) | G30A, G30B | G30C, G30D |
| [checkout-extensibility-payload](checkout-extensibility-payload/cases.json) | G31A, G31B | G31C, G31D |
| [css-layout-regression](css-layout-regression/cases.json) | G32A, G32B | G32C, G32D |
| [js-request-failure-handling](js-request-failure-handling/cases.json) | G33A, G33B | G33C, G33D |
| [react-render-and-keyboard](react-render-and-keyboard/cases.json) | G34A, G34B | G34C, G34D |
| [i18n-string-identity](i18n-string-identity/cases.json) | G35A, G35B | G35C, G35D |
| [sec-sql-prepare-and-identifiers](sec-sql-prepare-and-identifiers/cases.json) | G36A, G36B | G36C, G36D |
| [sec-rest-and-ajax-permissions](sec-rest-and-ajax-permissions/cases.json) | G37A, G37B | G37C, G37D |
| [sec-order-cart-ownership](sec-order-cart-ownership/cases.json) | G38A, G38B | G38C, G38D |
| [sec-sanitize-and-escape](sec-sanitize-and-escape/cases.json) | G39A, G39B | G39C, G39D |
| [sec-auth-tokens-and-cookies](sec-auth-tokens-and-cookies/cases.json) | G40A, G40B | G40C, G40D |
