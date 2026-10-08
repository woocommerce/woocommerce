# Rename the payment instrument reference

## Problem

Task: "Make a refactor in the engine WOOSUBS-2071. Backwards Compat is not a concern."

The Subscriptions Engine's `InstrumentRef` represents a payment instrument, but its name does not identify that purpose. The issue requests `PaymentInstrumentRef` throughout the engine. The package lives in this WooCommerce core checkout under `packages/php/woocommerce-subscriptions-engine/`; the preceding contract-creation change was merged in PR #69447.

## Approach

Rename `src/Core/ValueObject/InstrumentRef.php` and its class to `PaymentInstrumentRef`. Update imports, signatures, construction, and docblocks in `src/Core/Entity/Contract.php`, `src/Api/Contracts.php`, and `tests/unit/Core/Entity/ContractTest.php`. Preserve all values and behavior. This pre-release rename deliberately removes the old symbol without a compatibility alias, as authorized by the task. Search the whole checkout for remaining references.

Run existing engine tests and PHP static checks, using the workspace's provisioned core wp-env for container tests. Do not modify wp-env overrides. Add an engine package changelog, commit on `fw/task-131-payment-instrument-ref`, push that branch, and create one draft PR. No other external writes are authorized.

## Tasks

- [x] Rename the payment instrument reference: rename `packages/php/woocommerce-subscriptions-engine/src/Core/ValueObject/InstrumentRef.php` to `packages/php/woocommerce-subscriptions-engine/src/Core/ValueObject/PaymentInstrumentRef.php`; update the class, imports, signatures, construction and docblocks in that file, `packages/php/woocommerce-subscriptions-engine/src/Core/Entity/Contract.php`, `packages/php/woocommerce-subscriptions-engine/src/Api/Contracts.php`, and `packages/php/woocommerce-subscriptions-engine/tests/unit/Core/Entity/ContractTest.php`. Acceptance: values and behavior remain unchanged, the old class has no alias, and a checkout-wide exact-symbol search finds no remaining code references to `InstrumentRef`. Dependencies: none.
- [x] Add the engine changelog at `packages/php/woocommerce-subscriptions-engine/changelog/dev-rename-payment-instrument-ref` through the package changelog command. Acceptance: a `Type: dev` entry uses a concise `Comment:` describing the engine rename and absence of merchant-facing change, with no public changelog body. Dependencies: task 1.
- [x] Validate the rename and record results in `sdd/WOOSUBS-2071/notes.md`. Acceptance: existing engine unit tests cover the renamed contract round-trip; relevant container tests run through the provisioned core wp-env with `pnpm test:php:env`; engine PHPCS and PHPStan pass; core changed-file and branch PHP lint checks pass without warnings; notes pass markdownlint; no wp-env override files change. Dependencies: tasks 1 and 2.

## Implementation Notes

The user authorizes autonomous decisions and overrides interactive workflow confirmations. This is the lightweight SDD task workflow; this file contains the plan and iteration log. No separate plan or specification is needed.

### Validation Profile

- Linter: engine `pnpm lint:lang:php`; core `pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes` and `lint:changes:branch origin/trunk`.
- Type check: engine `pnpm phpstan:php7` and `pnpm phpstan:php8` (package configuration).
- Tests: core `pnpm test:php:env`; engine unit and integration PHPUnit configurations in the provisioned core CLI container, using a temporary package copy without changing mounts or overrides.
- Markdown: locally installed markdownlint CLI.
- Reviewer: SDD spec-compliance reviewer with WooCommerce review and performance skills. No repository-specific subagents found.
- Repo skills: WooCommerce backend development, performance, dev cycle, local environment, markdown, code review, commit and draft PR.
- QA: browser tools available, but skipped because no UI changes exist.

### Iteration 1

#### Implementation

- Renamed the value object file and class to `PaymentInstrumentRef`, with matching imports, type signatures, construction, and docblocks in the contract entity, public facade, and existing round-trip test. Values and runtime behavior are unchanged; no compatibility alias was added.
- A checkout-wide PHP exact-symbol search found no remaining `InstrumentRef` references.
- Added the engine's comment-only development changelog through the package changelog command. Noninteractive comment-only entries require an explicit empty `-e ''` argument; the command completed successfully with vendor `E_STRICT` deprecation notices.
- Validation remains pending for the parent implementation loop. No wp-env overrides were edited, and there were no implementation deviations.

#### Validation

- Tests: engine unit suite passed (278 tests, 589 assertions); integration suite passed (431 tests, 2,142 assertions, one existing skip for deferred max-cycle expiry). Both used PHP 8.1.34 in the provisioned core CLI container. The engine package was copied temporarily into that container because the core configuration mounts only the plugin directory.
- Core suite: `pnpm test:php:env` reported an early failure and stopped advancing at approximately 300 of 16,805 tests. Stopped that run and reproduced the first failure with `pnpm test:php:env --stop-on-failure`: 40 tests, 129 assertions, one failure and three skips. `WC_Tests_Notes_Run_Db_Update::test_soft_deleted_note_reappears_on_new_update` expects `update-db_run` but receives `update-db_see-progress` at line 201. Core code is identical to the branch base, and its test bootstrap and Composer autoloader do not load the engine. This unrelated core failure is recorded as a validation limitation; no core fixes are included.
- Linter: engine PHPCS passed all 84 files; core changed-file and branch lint passed with no changed core files. Notes passed markdownlint using a locally cached CLI.
- Type check: PHPStan passed all 81 files for both PHP 7.4 and PHP 8.4 configurations, with no errors. Missing local dependencies were installed before analysis; no baseline changes were needed.
- Reviewer: PASS, no findings in the requested rename. The PR must state the intentionally removed old symbol and changed getter/setter type. AI review does not replace independent human review.
- QA: skipped, no UI changes. No wp-env overrides changed.

### Final Summary

#### Key Decisions

- Renamed the value object and all callers to `PaymentInstrumentRef`, preserving behavior and deliberately omitting a compatibility alias as authorized.
- Used the existing engine tests and added a comment-only development changelog.

#### Deviations from Spec

- No implementation deviations. Full core validation is limited by the unrelated database-update note failure described above; core code and its engine-free bootstrap are unaffected by this rename.

#### Status

- All 3 tasks complete in 1 iteration. Exit reason: success for the scoped task, with the recorded core validation limitation.
- Engine unit and integration suites, PHPCS, both PHPStan configurations, core diff lint and markdownlint passed. Spec review passed with no findings.

#### Evidence

- Validation results are recorded above. No browser evidence was needed for this PHP-only rename.

#### Follow-up

- No technical debt or TODOs introduced. Independent human review remains required before merge for the intentional public-contract compatibility change.
