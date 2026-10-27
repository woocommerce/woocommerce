# local-ci-receipt

Decides, at the start of each `project-test-jobs` matrix job on a pull
request, whether a receipt published by [`gh local-ci`](https://github.com/woocommerce/gh-local-ci)
lets the job skip `Install Monorepo` and `Run tests`. A receipt is a check
run created on the head commit by the Woo Local CI Checks GitHub App.

The job is skipped only when the newest receipt of its name comes from that
App, passed, was made under the config trunk has, and was published by the
PR author, whose write permission on the repository is confirmed through
the API. Anything else runs the job as always and logs the reason. Receipts apply to `pull_request` events only; merge queue
runs and pushes to trunk run everything.

- `action.yml` — composite action; `receipt.js` — the decision (`decide()`)
  and the API reads (`lookup()`), and the comparison (`parityReport()`,
  `report()`).
- Contract: `.github/local-ci.json` (read from the base branch). Its
  `sampleRate` and `{workers}` need gh local-ci v0.4.0 or later.
- Kill switch: repository variable `LOCAL_CI_RECEIPTS_DISABLED=1`, effective
  on the next *new* run (re-runs keep old variables).

## Parity

A rule's `sampleRate` in `.github/local-ci.json` is the percentage of jobs
with a valid, passing receipt that run in CI anyway. The draw is
deterministic: a job is sampled when
`sha256(head sha + job name)`, read as a 32-bit big-endian number, mod 100
is below the rate. A re-run samples the same jobs, and each push draws
again. To recompute a draw by hand:

```sh
node -e 'const [s, j] = process.argv.slice(1); console.log(require("crypto").createHash("sha256").update(s + j).digest().readUInt32BE(0) % 100)' <full head sha> '<job name>'
```

A job whose newest receipt failed also runs in CI, as it always did. For
both, the step `Compare with the local CI receipt` runs after the tests and
compares CI's result with the receipt's. It writes a job summary section
and one annotation, which the run page lists with every other job's:

- `result=agree`: a notice.
- `result=mismatch local=success ci=failure`: an error. The receipt would
  have let a failing job through.
- `result=mismatch local=failure ci=success`: a warning.
- `result=inconclusive`: a notice. CI's tests were cancelled or never ran.

Each annotation starts with
`local-ci-parity result=… local=… ci=… base-moved=yes|no kind=sample|failed-receipt`,
so the results can be searched. The comparison never changes the job's
result: CI's own test run already decides it.

### When CI and a receipt disagree

1. Check `base-moved` first. CI tests the merge commit; the receipt tested
   the head against an older base. A change on trunk can explain the
   difference.
2. Compare the receipt's log (the check run's output) with the CI log.
3. Run the job again locally with `gh local-ci run --fresh`, then with
   `--docker`. A failure only outside Docker points at the local toolchain.
4. Open an issue on [gh-local-ci](https://github.com/woocommerce/gh-local-ci)
   with the run URL.

If mismatches repeat, set `LOCAL_CI_RECEIPTS_DISABLED=1` until the cause is
known.

## Testing

This action is tested live, against real GitHub, not with unit tests: the
only test that sees what GitHub actually sends is a real pull request, which
is how the `author_association` problem was found. The **live test is
required** for any change to `receipt.js`, `action.yml`, the `ci.yml` step,
its two `if:` guards or the comparison step, or `.github/local-ci.json`. `pr-check-local-actions.yml`
still validates `.github/local-ci.json` on every change to it.

The live test uses a scratch branch as a stand-in for trunk, so nothing
touches trunk. It takes about 30 minutes, mostly waiting for CI, and needs
someone whose login is in the tool's `allowlist.json` with `gh local-ci auth`
done. The full runbook, written so an agent can carry it out, is in
`AGENTS.md`; the result of the first run is recorded in the tool repository
(`docs/evidence-2026-09-28.md`).

What it proves, in one PR:

| Check | Expected in the job log |
|---|---|
| Positive path | `substituted=true reason=receipt … by <login> (has <permission> permission)`; `Install Monorepo` and `Run tests (unit)` skipped; job ≈ 10 s |
| Kill switch (new run with the variable set) | `substituted=false reason=kill switch LOCAL_CI_RECEIPTS_DISABLED=1` |
| Config changed in the PR | `substituted=false reason=.github/local-ci.json differs …` |
| Receipts absent | `substituted=false reason=no receipt named …` |
| Parity sample (`sampleRate: 100`) | `substituted=false reason=parity sample: bucket … < sampleRate 100 …`; tests run; a `local-ci-parity result=agree` notice |
| Local pass, CI fail | a `local-ci-parity result=mismatch local=success ci=failure` error; job red |
