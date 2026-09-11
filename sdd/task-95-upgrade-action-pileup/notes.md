# Stop upgrade inbox action accumulation

## Problem

Task: Stop the woocommerce_run_on_woocommerce_admin_updated pile-up during upgrade. Closes <https://github.com/woocommerce/woocommerce/issues/48414>.

Reproduced on the provisioned wp-env site at localhost:8796, WooCommerce 11.2.0-dev and Action Scheduler 4.1.0, before production changes. Seeded 1,000 overdue unrelated actions and one overdue target action. Ten `woocommerce_updated` events with the target pending left one pending copy. Ran the target through `ActionScheduler_QueueRunner::process_action()` in one PHP process, holding its callback while a second PHP process fired ten update events. Pending target copies increased from zero to ten, one per event, while one target was running. Every lookup returned `as_next=true`, `wc_next=null`. The runner completed normally and left ten pending copies.

The guard is present in tag 8.9.3 and commit 4c9bcbc30e (2023-05-09, PR #38159). It fails because Action Scheduler checks running actions before pending actions, so a running match masks even an existing pending backlog. WC_Action_Queue::get_next converts only numeric results to WC_DateTime, discarding boolean true. Pending NullSchedule actions share this blind spot. Before initialization AS returns false, but scheduling also returns zero, so that condition alone does not explain accumulation.

This establishes a reproducible mechanism, not proof of the original merchant's full upgrade loop or why their update events repeated. PR #49243 fixes a separate premature-shutdown cleanup problem.

## Approach

Fix the engine's existence decision using queue operations that recognize pending and running work, including async work, without changing WC_Queue_Interface or WC_Action_Queue::get_next's WC_DateTime|null contract. Prefer the existing search API over adding a required interface method or bypassing custom queues. Keep schedule_single and hook/args/group unchanged. Do not invent a date for boolean results or broaden get_next to boolean. Do not perform a repository-wide caller migration or backlog deletion. The existing check-then-insert concurrency race between independent producers is outside this reproduced running-action issue.

Blast radius: 13 production get_next call sites across 10 files; AnalyticsImports calls format/getTimestamp on the result. Queue replacement is supported via woocommerce_queue_class. Preserve these contracts and document the scoped consumer change and remaining manual tests in the draft PR.

## Tasks

- [x] Add failing scheduling regressions in `plugins/woocommerce/tests/php/src/Admin/RemoteInboxNotifications/RemoteInboxNotificationsEngineTest.php`: initialize the engine with isolated hooks, create real Action Scheduler records, fire repeated `woocommerce_updated` events while a matching action is running (both alone and with pending copies), and assert no additional pending work; cover pending async `NullSchedule` work too. Acceptance: the running and async cases fail against the current guard for the reproduced reason; fixtures and hooks are restored. Dependencies: none.
- [x] Replace the engine's timestamp-based existence decision in `plugins/woocommerce/src/Admin/RemoteInboxNotifications/RemoteInboxNotificationsEngine.php`: query the existing queue `search()` API for pending and running actions using separate scalar statuses, exact hook/empty args/group, `per_page => 1`, and `ids`; return immediately on either match, otherwise keep the existing `schedule_single()` call. Explain briefly why a date lookup misses running/async actions. Acceptance: regression tests pass; no interface, `get_next()`, callback signature, or scheduling payload changes; no AS-only helper bypasses replacement queues. Dependencies: first task.
- [x] Complete boundary coverage in `plugins/woocommerce/tests/php/src/Admin/RemoteInboxNotifications/RemoteInboxNotificationsEngineTest.php`: verify repeated events without active work schedule exactly one copy, ordinary pending work blocks duplicates, completed/failed/canceled work permits a fresh copy, and unrelated hooks/args/groups do not suppress the target. Exercise a replacement queue through its existing `search()` and `schedule_single()` API with scalar status filters. Acceptance: focused container PHPUnit passes and tests verify observable scheduling behavior. Dependencies: second task.
- [x] Validate and record results in `sdd/task-95-upgrade-action-pileup/notes.md`: replay the original two-process wp-env reproduction with the same unrelated backlog and held callback; verify pending count stays zero while running, existing pending copies do not increase, and one later event schedules a new copy after completion. Run focused engine and existing inbox/queue PHPUnit coverage inside wp-env, both required PHP lint commands, and PHPStan on changed PHP files. Acceptance: before/after evidence is recorded, all applicable checks pass without baseline additions, and reproduction fixtures are cleaned up. Dependencies: third task.
- [x] Add `plugins/woocommerce/changelog/fix-upgrade-inbox-action-pileup` and finalize review evidence in `sdd/task-95-upgrade-action-pileup/notes.md`: record preserved external queue/date contracts, bounded query cost, unchanged check-then-insert race, original merchant reproduction limits, and remaining actual-upgrade/high-volume/multisite/custom-queue manual testing. Acceptance: SDD review clears findings and draft PR material includes the task quote, issue closure, historical guard explanation, measured reproduction, scoped change, and validation limits. Dependencies: fourth task.

## Implementation Notes

### Validation profile

- PHPUnit: `pnpm test:php:env -- --filter '<focused classes>'` inside plugins/woocommerce, then relevant queue and inbox coverage.
- Lint: `pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes` and `lint:changes:branch`.
- Types: `composer exec -- phpstan analyse <modified PHP files> --memory-limit=2G` inside plugins/woocommerce; no baseline additions.
- QA: replay the two-process reproduction before and after; cover pending async and initialization behavior.
- Review: SDD reviewer with WooCommerce code-review and performance guidance. No repo-level subagents found.
- Browser available through CUA; no UI changes expected, so WP-CLI is the relevant QA surface.
- Repo skills: backend-dev, performance, dev-cycle, local-env, markdown, git-commit, git-draft-pr.

### Investigation

- Used the provisioned wp-env site without changing its port overrides.
- Real Action Scheduler store and runner used, no mocked statuses or queue results in the reproduction. The callback was deliberately held open to make the concurrency window deterministic.

### Iteration 1

#### Implementation

- Added the isolated engine scheduling test class and ran it before changing production code. The container run failed all three regression cases: running alone accumulated 10 pending actions instead of zero; running with three pending copies accumulated 13 instead of three; a pending NullSchedule action accumulated additional scheduled copies. The real stored running/async records returned `as_next_scheduled_action() === true` and `get_next() === null` as expected.
- Replaced only the engine's existence lookup with scalar pending and running searches using exact hook, empty arguments, group, one ID per query, and early return on a match. `schedule_single()` and all shared queue/date contracts remain unchanged. Searches use the existing replacement-queue API and never load full actions in production.
- Added boundary coverage for ordinary pending work, empty queues, completed/failed/canceled work, and independent hook/argument/group mismatches. A stateful test double of the existing queue interface controls whether scheduling is allowed for empty, pending and running states. Test hooks and database state use the parent test case's restoration, and replacement queue state is restored in `finally`.
- Focused validation passed inside the provisioned container: `pnpm test:php:env -- --filter RemoteInboxNotificationsEngineTest`, PHPUnit 9.6.35 on PHP 8.1.34, 14 tests and 269 assertions. The parent run owns live replay, broader validation and final delivery.

#### Live QA

- Replayed the exact two-process fixture after the fix: 1,000 overdue unrelated actions; ten pending-only updates left one target; real runner callback held open while ten independent-process updates left zero pending targets throughout. The runner completed normally. Ten later events created exactly one fresh pending target.
- Pending async fixture: before the fix, one NullSchedule action grew to eleven pending actions after ten events; after the fix it remained one. Regression tests cover the async failure without assuming every event must create another action, since equal scheduled-date ordering can expose a dated copy after one is inserted.
- Forced only the Action Scheduler initialized flag false temporarily: initialized=0, as_next=false, schedule_result=0, pending=0. Restored the flag immediately. This is a controlled API-state probe, not an actual failed-upgrade reproduction.
- Confirmed the same running-first/boolean implementation in Action Scheduler tag 3.7.4, the reporter's version. get_next's numeric conversion was intentional in ebb8ced803dd (2020) to let new webhook states queue while an earlier delivery is running.
- Fixtures were removed using their exact hook/group IDs; no old pending jobs were repaired or deleted as part of the product fix. PHPUnit resets this disposable site's plugin activation; WooCommerce was reactivated before live replay.

#### Validation limitations

- The staged diff lint passes with no errors or warnings. Before commit, branch lint cannot retrieve the newly added test from HEAD and emits NoCodeFound; it was rerun after commit and passed with no changed-code errors or warnings. Full-file PHPCS finds only the existing missing strict_types declaration on the unchanged first line of the engine; adding it could change unrelated runtime semantics, so it is left unchanged.
- Production PHPStan passes. An attempted source-and-test invocation reports only unresolved WC_Unit_Test_Case and inherited test methods because the repository's PHPStan configuration covers source/includes, not the test framework. No baseline or configuration was changed to hide those errors. Tests are validated through container PHPUnit and PHPCS.

#### Validator results

- Linter: PASS on staged PHP diff and final committed branch diff, including the new test. Required `lint:php:changes` also exited zero (no unstaged files). JavaScript branch lint correctly reports no changed files.
- Types: PASS for production PHP, no errors. Test-inclusive PHPStan limitations are documented above.
- Tests: PASS, 198 tests and 512 assertions across the requested focused filter (169/473), existing inbox coverage (10/15), and existing webhook coverage (19/24). Entire WooCommerce suite and multisite were not run.
- QA: PASS for the controlled two-process and async reproductions. After all tests, WooCommerce was reactivated and the site was verified at localhost:8796 with zero reproduction backlog and zero active target actions.
- Reviewer: PASS. The full code review pipeline returned APPROVE with zero findings from six specialists; the independent decision critic returned STAND. Reviews covered code correctness, queue contracts, concurrency, performance, PHP tests, and WooCommerce regressions. The changelog was also checked. No implementation revision was required.

### Final Summary

#### Key Decisions

- Reproduced the running-action blind spot before changing production code. The existing guard predates the report; boolean `true` from Action Scheduler becomes `null` in the queue's date lookup, and running work masks pending copies.
- Changed only the engine's existence decision to bounded pending/running searches through the existing queue API. Preserved `WC_Queue_Interface`, `get_next()` return types, replacement-queue support, and scheduling payloads. Existing duplicate actions and the independent check-then-insert race remain outside scope.

#### Deviations from Spec

- No implementation deviations. Test-inclusive PHPStan could not resolve the repository's test framework; production PHPStan passed, and container PHPUnit plus changed-code PHPCS validated the tests without baseline changes.

#### Status

- Completed 5 of 5 tasks in 1 implementation-loop iteration. Exit reason: success.
- The 14 new tests passed with 269 assertions after demonstrating the expected failures. Combined targeted coverage passed: 198 tests, 512 assertions. Staged and post-commit branch lint passed; production PHPStan passed.
- Full review verdict: APPROVE, six specialists, zero findings; independent decision critic: STAND.

#### Evidence

- With 1,000 overdue unrelated actions and a real running target, ten update events increased pending targets from zero to ten before the fix and left zero after it. Later events scheduled exactly one new target after completion.
- Ten events increased the pending async fixture from one to eleven before the fix and left one after it. The controlled uninitialized-state probe scheduled nothing. Reproduction fixtures were removed and site activation restored.
- Evidence is textual in this log and prepared PR material. Local working artifacts belong in ignored `artifacts/task-95/evidence`; no screenshots or GIFs were needed.

#### Remaining manual validation

- An actual historical upgrade with the reporter's repeated-update trigger, Action Scheduler High Volume with two threads, multisite, and real third-party replacement queues. The controlled reproduction establishes the mechanism, not the original merchant's complete loop. The entire WooCommerce suite was not run.
