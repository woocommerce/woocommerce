# AI review guideline evals

Each of the original 40 review guidelines has two bug cases and two safe boundary cases: 160 cases in total. The root `ai-review-config.json` links each guideline to its suite. The later `perf-blocking-remote-call` guideline is outside this initial corpus.

## Set up the runner

These evals require `a8c-review` V2 with the `eval` command. The runner is currently an internal tool and is not distributed with WooCommerce; you need access to an existing AI Review CLI checkout. Contributors without access can still inspect and edit the JSON suites and source fixtures.

Install PHP 8.2+, Composer, Node.js 18+ and Git. From your AI Review CLI checkout (replace the example path):

```sh
cd /path/to/ai-review-cli/ai-review-cli-v2
composer install
npm ci
mkdir -p "$HOME/.local/bin"
ln -s "$PWD/bin/a8c-review" "$HOME/.local/bin/a8c-review"
export PATH="$HOME/.local/bin:$PATH"
a8c-review eval --help
```

If `a8c-review` is already installed, check that `eval --help` works before creating the symlink. Keep the CLI checkout in place: the symlink points to it. To make the PATH setting permanent, add the `export PATH` line to your shell startup file, such as `~/.zshrc` or `~/.bashrc`.

The committed review configuration uses Claude and Codex. Exported API credentials take precedence over subscription logins. When `OPENAI_API_KEY` is set, the runner calls `codex login --with-api-key`, replacing the saved ChatGPT login. `ANTHROPIC_API_KEY` or `ANTHROPIC_AUTH_TOKEN` overrides the Claude subscription login. This can cause unintended API billing.

To use your subscriptions, unset these variables in the shell used for authentication and evals:

```sh
unset OPENAI_API_KEY ANTHROPIC_API_KEY ANTHROPIC_AUTH_TOKEN
```

Authenticate with both bundled clients from the same CLI directory:

```sh
node_modules/.bin/claude
# Run /login in Claude, then exit.
node_modules/.bin/codex login
```

Existing logins can be reused. Your accounts must have access to the configured models and available quota. Live evals consume model usage.

## Run locally

From the WooCommerce repository root on a branch containing these evals:

```sh
a8c-review eval --plan
a8c-review eval
a8c-review eval --guideline sql-clause-filter-contract
```

`--plan` validates the definitions and reads the fixtures without calling models. Live runs inherit this repository's personas, models, prompts and routing. They require credentials for both the configured Claude and Codex engines. No model override is introduced by these suites.

Each live run prints a new results directory in the system temporary directory. Save that directory elsewhere if you need to retain the evidence. To run just one case:

```sh
a8c-review eval --guideline hook-payload-contract --case G01A-v3
```

The default timeout is 600 seconds per reviewer attempt; `--timeout 300` changes it to 300 seconds. It is not a limit for the whole suite.

If a case reports `Native review failed`, inspect its `stdout.json` and `stderr.log` beneath the printed results directory. An authentication or quota error means the review did not complete, not that the case failed its expectation. For a quota limit, stop the run with Ctrl+C and rerun after quota is available. `--plan` does not check authentication or quota.

Each fixture directory contains before and after source files. The suite's `path` is the simulated WooCommerce source location used for routing and finding matching. These are focused source fixtures, not executable WordPress integration tests. Supporting callers, consumers and framework behavior establish the contract being tested.

The runner creates an isolated Git repository containing the case file and configured review prompts. It does not copy the whole WooCommerce checkout. Include the surrounding code needed to establish a case's behavior in its before and after files.

A `finding` expectation requires a finding on that path containing the specified case-insensitive literal. A `quiet` expectation requires no findings, including suppressed ones. Wording changes can fail a case even when the reviewer detects the bug; an unrelated finding can pass if it contains the literal. Inspect the finding's reasoning against the fixture before treating the automated score as evidence of detection. Routing alone does not determine whether a case passes.

## Fixture corrections and limitations

Cases use versioned IDs after adversarial fixture reviews. Revisions include concrete PHP callback boundaries, shipping normalization and filter consumers, actual currency package files, and regression tests with observable results. Fixture directory names may retain their original IDs; the suite identifies the current case version. Some safe cases deliberately exercise simple copy or private refactoring changes alongside harder boundaries.

The original experiment and its failed outcomes remain preserved separately. Revised scenarios have versioned case IDs and must be evaluated again. Earlier results do not establish the revised cases' results. These cases exercise review behavior on selected changes; they do not measure production-wide accuracy or isolate the effect of a guideline from the broader persona prompt.

## Coverage

| Guideline | Bug cases | Safe boundary cases |
| --- | --- | --- |
| [hook-payload-contract](hook-payload-contract/cases.json) | G01A-v3, G01B-v3 | G01C-v3, G01D-v3 |
| [hook-firing-scope](hook-firing-scope/cases.json) | G02A-v3, G02B-v3 | G02C-v3, G02D-v3 |
| [php-loose-type-juggling](php-loose-type-juggling/cases.json) | G03A-v3, G03B-v3 | G03C-v3, G03D-v3 |
| [public-php-api-bc](public-php-api-bc/cases.json) | G04A-v3, G04B-v3 | G04C-v3, G04D-v3 |
| [upgrade-time-class-load](upgrade-time-class-load/cases.json) | G05A-v3, G05B-v3 | G05C-v3, G05D-v3 |
| [action-scheduler-lifecycle](action-scheduler-lifecycle/cases.json) | G06A-v3, G06B-v3 | G06C-v4, G06D-v4 |
| [request-input-validation](request-input-validation/cases.json) | G07A-v4, G07B-v3 | G07C-v3, G07D-v3 |
| [missing-object-deref](missing-object-deref/cases.json) | G08A-v3, G08B-v3 | G08C-v3, G08D-v3 |
| [meta-option-value-semantics](meta-option-value-semantics/cases.json) | G09A-v3, G09B-v3 | G09C-v3, G09D-v3 |
| [hpos-sync-on-read](hpos-sync-on-read/cases.json) | G10A-v3, G10B-v3 | G10C-v3, G10D-v3 |
| [migration-upgrade-state](migration-upgrade-state/cases.json) | G11A-v3, G11B-v3 | G11C-v3, G11D-v3 |
| [proxy-predicate-inference](proxy-predicate-inference/cases.json) | G12A-v4, G12B-v3 | G12C-v3, G12D-v4 |
| [wc-query-shop-front-page](wc-query-shop-front-page/cases.json) | G13A-v3, G13B-v3 | G13C-v4, G13D-v3 |
| [request-lifecycle-state](request-lifecycle-state/cases.json) | G14A-v3, G14B-v3 | G14C-v3, G14D-v3 |
| [template-and-js-default](template-and-js-default/cases.json) | G15A-v3, G15B-v3 | G15C-v3, G15D-v3 |
| [rest-response-contract](rest-response-contract/cases.json) | G16A-v3, G16B-v3 | G16C-v4, G16D-v3 |
| [session-cart-persistence](session-cart-persistence/cases.json) | G17A-v3, G17B-v3 | G17C-v3, G17D-v3 |
| [check-then-write-race](check-then-write-race/cases.json) | G18A-v3, G18B-v3 | G18C-v3, G18D-v3 |
| [order-totals-recalc](order-totals-recalc/cases.json) | G19A-v4, G19B-v3 | G19C-v3, G19D-v3 |
| [shipping-rate-pipeline](shipping-rate-pipeline/cases.json) | G20A-v4, G20B-v4 | G20C-v4, G20D-v4 |
| [sql-clause-filter-contract](sql-clause-filter-contract/cases.json) | G21A-v3, G21B-v3 | G21C-v3, G21D-v3 |
| [cache-invalidation-and-poisoning](cache-invalidation-and-poisoning/cases.json) | G22A-v3, G22B-v3 | G22C-v4, G22D-v3 |
| [perf-refactor-semantic-drift](perf-refactor-semantic-drift/cases.json) | G23A-v5, G23B-v3 | G23C-v3, G23D-v3 |
| [fix-and-test-verification](fix-and-test-verification/cases.json) | G24A-v3, G24B-v3 | G24C-v4, G24D-v3 |
| [perf-sql-query-shape](perf-sql-query-shape/cases.json) | G25A-v4, G25B-v3 | G25C-v3, G25D-v3 |
| [perf-cache-option-priming](perf-cache-option-priming/cases.json) | G26A-v3, G26B-v5 | G26C-v3, G26D-v3 |
| [perf-hot-path-eager-work](perf-hot-path-eager-work/cases.json) | G27A-v4, G27B-v4 | G27C-v3, G27D-v3 |
| [script-handle-loading-contract](script-handle-loading-contract/cases.json) | G28A-v3, G28B-v3 | G28C-v3, G28D-v3 |
| [classic-jquery-contract](classic-jquery-contract/cases.json) | G29A-v3, G29B-v3 | G29C-v3, G29D-v3 |
| [js-public-api-contract](js-public-api-contract/cases.json) | G30A-v3, G30B-v3 | G30C-v3, G30D-v3 |
| [checkout-extensibility-payload](checkout-extensibility-payload/cases.json) | G31A-v3, G31B-v3 | G31C-v3, G31D-v3 |
| [css-layout-regression](css-layout-regression/cases.json) | G32A-v3, G32B-v3 | G32C-v3, G32D-v3 |
| [js-request-failure-handling](js-request-failure-handling/cases.json) | G33A-v3, G33B-v4 | G33C-v3, G33D-v3 |
| [react-render-and-keyboard](react-render-and-keyboard/cases.json) | G34A-v3, G34B-v3 | G34C-v3, G34D-v3 |
| [i18n-string-identity](i18n-string-identity/cases.json) | G35A-v3, G35B-v3 | G35C-v3, G35D-v3 |
| [sec-sql-prepare-and-identifiers](sec-sql-prepare-and-identifiers/cases.json) | G36A-v3, G36B-v4 | G36C-v3, G36D-v3 |
| [sec-rest-and-ajax-permissions](sec-rest-and-ajax-permissions/cases.json) | G37A-v5, G37B-v3 | G37C-v3, G37D-v3 |
| [sec-order-cart-ownership](sec-order-cart-ownership/cases.json) | G38A-v3, G38B-v3 | G38C-v3, G38D-v3 |
| [sec-sanitize-and-escape](sec-sanitize-and-escape/cases.json) | G39A-v5, G39B-v3 | G39C-v3, G39D-v3 |
| [sec-auth-tokens-and-cookies](sec-auth-tokens-and-cookies/cases.json) | G40A-v3, G40B-v4 | G40C-v3, G40D-v3 |
