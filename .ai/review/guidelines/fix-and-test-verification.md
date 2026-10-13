# Does the fix work and can the test fail

**Source:**

- #61788: the fix defined its constant but still produced no products.
- Earlier review findings: a dead branch keyed off an impossible value, and the tests mocked it; a flag set but never read; vacuous or flaky tests: unpinned dates, non-unique faker names, impossible inputs.

**What broke:** Fixes merged that did not change the output for the input that triggered the bug. Their tests passed either way, because they mocked the broken part or used input production never sends.

**Pattern to catch:** - The PR claims a fix, but the triggering input never reaches the new code.

- Tests pass on impossible input, would still pass with the fix reverted, or depend on the clock or random data.

**How to confirm:** Trace the triggering input from the PR description to user-visible output. Revert the fix in your head and ask whether each new test still passes. Check the test reaches the same branch production does.

**Don't flag when:** Refactor-only PRs with no claimed behavior change. Test-only PRs that cover existing behavior and do fail on mutation.
