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
  and the API reads (`lookup()`).
- Contract: `.github/local-ci.json` (read from the base branch).
- Kill switch: repository variable `LOCAL_CI_RECEIPTS_DISABLED=1`, effective
  on the next *new* run (re-runs keep old variables).

## Testing

This action is tested live, against real GitHub, not with unit tests: the
only test that sees what GitHub actually sends is a real pull request, which
is how the `author_association` problem was found. The **live test is
required** for any change to `receipt.js`, `action.yml`, the `ci.yml` step or
its two `if:` guards, or `.github/local-ci.json`. `pr-check-local-actions.yml`
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
