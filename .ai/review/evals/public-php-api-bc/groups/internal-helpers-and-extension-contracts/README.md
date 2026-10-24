# Internal helpers and extension contracts

This optional group pairs a false-positive regression with a positive control. It checks review guidance that separates internal implementation helpers from supported extension contracts. The group ID is `internal-helpers-and-extension-contracts`.

| Role | Case ID | Change | Expected result |
| --- | --- | --- | --- |
| False-positive regression | `G04E-established-mcp-v1` | Historical MCP change removing an internal implementation helper | Quiet |
| Positive control | `G04A-established-stripe-v1` | Adds a required `FeedInterface` method while the Stripe implementor stays unchanged | Finding at `FeedInterface.php` line 65 |

The regression replays a public WooCommerce change that should not be reported. The control adds `get_entry_count()` to `FeedInterface`. The pinned Stripe class implements that interface without the new method. PHP would raise a fatal error when it loads that class. The control deliberately introduces a genuine interface break.

Useful guidance should keep the regression quiet and still report the control. Each case is graded against its own expectation. There is no combined group result.

## Registration

The committed `public-php-api-bc/cases.json` does not include these cases. The runner does not discover `cases.template.json` automatically. `prepare.py` creates a new, separate checkout and registers both cases and the group there. That checkout then contains 162 cases.

Grouping does not change the default selection. An unfiltered `eval` in the prepared checkout runs all 162 cases. Always pass `--group internal-helpers-and-extension-contracts` to run only this pair.

| Path | Contents |
| --- | --- |
| `cases.template.json` | Both case templates and the group registration |
| `sources.json` | Pinned public sources |
| `regression/mcp-helper-removal.patch` | The regression's reviewed change |
| `controls/stripe-required-method.patch` | The control's reviewed change |
| `agents-policy.patch` | The `AGENTS.md` policy overlay |
| `prepare.py` | Local preparation |

## Public sources

`sources.json` pins these public sources:

- WooCommerce before and after commits for the historical MCP change.
- [`WordPress/mcp-adapter`](https://github.com/WordPress/mcp-adapter) versions 0.3.0 and 0.4.1.
- One file from [`woocommerce/woocommerce-gateway-stripe`](https://github.com/woocommerce/woocommerce-gateway-stripe), with its commit and SHA-256.

`regression/mcp-helper-removal.patch` preserves the complete original five-file WooCommerce change. Preparation checks it byte for byte against the pinned commits. `controls/stripe-required-method.patch` changes only the historical `FeedInterface`.

## Prerequisites

- Python 3 and Git.
- A WooCommerce checkout on a branch containing this group. Its committed `HEAD` supplies the review configuration and configured prompts.
- A local Git repository containing both pinned WooCommerce commits. A full WooCommerce clone can serve as both repositories.
- A local `mcp-adapter` clone containing both pinned adapter commits.
- The pinned Stripe file exported to disk.
- A CLI checkout with native patch replay, historical `base_commit`, suite `groups` and `--group`.

`prepare.py` does not download sources, install dependencies or run a model. It never overwrites an existing output and does not change its input repositories.

Check each pinned commit with `git -C /path/to/repo cat-file -e <commit>^{commit}`. If a commit is missing, fetch it by hash, for example `git -C /path/to/mcp-adapter fetch origin <commit>`.

## Prepare

Run these commands from the WooCommerce repository root. The output path must not exist and must be outside every input repository.

```sh
git -C /path/to/woocommerce-gateway-stripe show \
  c0b9a631f0563f0f9ea1b6fa216f6723372311a8:includes/agentic-commerce/class-wc-stripe-agentic-commerce-csv-feed.php \
  > /tmp/stripe-feed.php

python3 .ai/review/evals/public-php-api-bc/groups/internal-helpers-and-extension-contracts/prepare.py \
  --policy-repo /path/to/woocommerce \
  --source-repo /path/to/woocommerce-with-historical-objects \
  --adapter-repo /path/to/mcp-adapter \
  --stripe-file /tmp/stripe-feed.php \
  --output /tmp/wc-prepared-review
```

Preparation stops if the Stripe file hash or the MCP patch does not match its pin. It prints a summary with the policy commit, source commit, `AGENTS.md` overlay SHA-256 and case IDs.

Preparation does the following:

- Creates a sparse checkout of the policy repository's committed `HEAD`.
- Builds a local source commit from the historical WooCommerce base.
- Applies `agents-policy.patch` to the historical `AGENTS.md`, keeping unrelated historical content.
- Appends a neutral source-location note, identical before and after the reviewed change.
- Adds both adapter source trees, the full Stripe file and a provenance manifest as reference sources.
- Registers both cases and the group in the output's working tree, with the generated `base_commit`.

Preparation stops if a case or group ID is already registered. Keep the input repositories available because the prepared checkout borrows their Git objects.

Native replay overlays the prepared checkout's configured policy files on the historical source. Other instructions remain at the historical version. The runner excludes the registered suite directory from reviewer snapshots. Expectations, patches and group labels stay there and are not shown to the reviewer.

## Check and run

The CLI version string does not identify the required features. Check that `a8c-review eval --help` lists `--group`. Earlier CLI checkouts reject the prepared `cases.json`.

Validate both registrations without model calls:

```sh
a8c-review eval --path /tmp/wc-prepared-review --guideline public-php-api-bc \
  --group internal-helpers-and-extension-contracts --plan
```

The plan should report 2 cases and 0 errors. Both cases show the group ID and their `regression` or `control` role.

To run the pair, remove `--plan` and set an output directory outside the repository:

```sh
a8c-review eval --path /tmp/wc-prepared-review --guideline public-php-api-bc \
  --group internal-helpers-and-extension-contracts --output /tmp/internal-helpers-results
```

This calls the configured review engines and needs their normal authentication. `results.json` records each case's group ID and role. Running one case with `--case` prints a warning naming the other. Report both results together.

The control passes only when a finding's primary file and line match `FeedInterface.php` line 65. A finding that cites that line only in `introduced_by` fails, even when its reasoning is correct. Read the findings before treating a score as evidence.

## Scope

The prepared dependencies are versioned source references. Preparation does not install or run a complete WordPress or Stripe integration.

The source-location note helps reviewers find the snapshots. It does not prove that a reviewer read every file. The Stripe control is a known example already named in the review guideline.

`eval` runs the native reviewer without a judge. These checks do not establish deployment equivalence or coverage of every compatibility change.
