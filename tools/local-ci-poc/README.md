# Local CI receipts — proof of concept

Demonstrates the one mechanism in the local-first CI proposal that isn't obvious:
**a commit status can be attached to a commit GitHub has never seen, before the
branch that would trigger CI is pushed.**

That ordering is what removes the race. Publishing a result after pushing the
branch is too late — the workflow has already started.

## Run it

```sh
php tools/local-ci-poc/poc.php
```

Runs against the real repository. Creates one ref and one commit status, then
removes the ref. Safe to run repeatedly. Needs `gh` or any GitHub token
reachable from `GH_TOKEN`, `GITHUB_TOKEN`, or the git credential store.

## What it proves

```text
4 · Publish the commit to a ref that triggers nothing
  before: GET /commits/47c51f84… → HTTP 422        ← GitHub has never seen it
  ✓ pushed to refs/local-ci/47c51f84…
  after:  GET /commits/47c51f84… → HTTP 200        ← now it has

5 · Confirm no workflow was triggered
  ✓ 0 workflow runs for this SHA

6 · Publish the receipt
    POST /statuses → HTTP 201                      ← receipt on an unpushed commit

7 · Read it back, as CI would
    local-ci/v1/poc  success  creator=MrJnrman     ← creator is what CI validates
```

Four findings, each verified by running this:

1. **GitHub accepts a custom top-level ref namespace.** `refs/local-ci/<sha>` is
   outside `refs/heads/*` and `refs/tags/*`, so Actions cannot trigger on it, and
   it stays out of the branch list and out of everyone's `git fetch`. Better than
   the `refs/heads/local-ci/**` the proposal originally assumed.

2. **The SHA becomes known without a branch.** 422 → 200 across the push. This is
   the whole trick.

3. **The receipt records its creator.** `creator=MrJnrman` comes from GitHub, not
   from anything the client asserts, which is what makes team validation possible.

4. **The temporary push must use `--no-verify`.** Otherwise the repo's own
   `pre-push` hook runs against it — and on `trunk` its protected-branch guard
   cancels the push outright.

## What it does not prove

- **Matrix subtraction.** `poc-local-ci.yml` skips the work inside a job, but the
  job is still scheduled. Removing it from the matrix needs the planner change, and
  the merge guard must learn the planned set first, or a missing job is
  indistinguishable from a substituted one.
- **Trust.** No team membership is checked. The receipt here was posted by a script
  that did run the check, but nothing structurally required it to — anyone who can
  post a status to this repo can suppress the job.
- **Fidelity.** The check ran against the working tree, not against the merge of
  this branch with trunk, which is what CI actually tests.

## Note on cleanup

An earlier run was interrupted mid-flight and leaked its temporary ref. The script
now removes it from a shutdown handler and on `SIGINT`/`SIGTERM`. Any real
implementation needs the same, or abandoned refs accumulate on the remote.
