---
description: Scan new CI runs for wp-env start retries and failures, then refresh the stats report
allowed-tools: Bash(.claude/scripts/wp-env-start-stats.sh:*), Read, Edit
argument-hint: "[--report-only] [--since YYYY-MM-DD] [--max-days N] [--reset]"
---

# wp-env start stats

Update the wp-env start failure statistics.

## Run this

```sh
.claude/scripts/wp-env-start-stats.sh $ARGUMENTS
```

Collection resumes from the day cursor in `.claude/ci-monitoring/state.json`, so a normal
invocation only fetches runs that appeared since last time. The first run backfills from
2026-07-15 — the merge of PR #66491, which added the retry loop and the `reason=`
annotations this report is built on. Nothing before that date is measurable.

Backfilling ~4 weeks means downloading a few thousand run log archives and takes 20–40
minutes. Run it in the background and pass `--max-days N` if you want it in chunks.

## Then report back

Read `.claude/ci-monitoring/wp-env-start-failures.md` and tell the user, in a few lines:

- How many new runs were scanned and what the cursor moved to.
- The current recovery rate, and whether it moved against the previous week and month.
- Any cause that is newly present or clearly growing.
- Anything classified as `unclassified` — that is a signature the script does not know
  yet. Open the failing job's log, find the error on the final attempt, and propose a
  pattern for `classify()` in the script. Do not guess the cause from the job name.

Do not paste the whole report back; link to the file and give the headline numbers.

## How it decides

Each job log carries the retry loop's own annotations:

```text
##[warning]wp-env-start-retry reason=<subsystem> attempt=N/3
##[error]wp-env start failed after 3 attempts (last reason=<subsystem>)
```

A job that emitted a retry warning and no failure annotation was **recovered**; one that
emitted the failure annotation was **lost**. Jobs that started cleanly are not counted, so
the denominator is jobs that hit a problem at all — not every job in CI.

The `reason=` tag is what CI guessed at the time. The `Cause` column is what the script
reads back out of the final attempt's log, which is more specific and sometimes disagrees
— most `reason=unknown` failures are a branch's own broken code, not infrastructure.

## Gotchas worth knowing before you change anything

- **Match only the retry loop's own output.** GitHub echoes the step's bash source into
  the log, and that source contains every classifier pattern verbatim. Asset-build output
  and a Docker daemon debug dump also sit inside the same step — a minified JS bundle
  containing `TooManyRequests` once read as Docker Hub rate limiting. The script anchors
  on the `##[warning]` / `##[error]` prefixes and slices to the final attempt for this
  reason.
- **Causes are stored at collection time.** Editing `classify()` only affects runs scanned
  afterwards; use `--reset` to re-derive everything.
- Data lives in `.claude/ci-monitoring/`: `events.tsv` (one row per job that retried),
  `scanned.txt` (run ids already processed, the dedupe set), `state.json` (day cursor).
  The report is regenerated from `events.tsv` every time, so it is safe to delete.
