# Performance refactors that change results

**Source:**

- #68272 (candidate-first purchasable-variation scan) reverted by #69056.
- #65595 (skip meta existence checks on create) reverted by #66325.
- #55843 (related products caching) reverted by #59642.
- #65957: status counters disabled in #67058.
- #66607 (payment token query) reverted by #67104.
- #66810, #66853: edge cases fixed after perf audits.

**What broke:** PRs framed as speed-ups changed what came back or skipped side effects for some catalogs or extensions. Five were reverted.

**Pattern to catch:** A PR titled or described as performance that changes evaluation order, skips a check, adds a cache or a lookup-table read, or truncates or limits a set.

**How to confirm:** List old and new results for edge inputs: disabled, private, or out-of-stock children; extension-filtered data; empty lookup tables. Check side effects such as meta creation and hooks still happen.

**Don't flag when:** Pure batching (priming) with identical results. This entry is for correctness; cost alone belongs to the performance reviewer.
