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

It refuses to publish anything it cannot honestly describe: the working tree must
be clean (the check runs against the tree, but the receipt names HEAD), the branch
must already contain the tip of trunk (CI tests the merge, not the commit), HEAD
must not move while the check runs, and a rejected `POST /statuses` exits non-zero
rather than printing the code and carrying on.

## What it proves

```text
2 · Check this commit is publishable
  ✓ working tree is clean — what gets tested is what HEAD contains
  ✓ up to date with trunk — HEAD is the tree CI would merge and test

5 · Publish the commit to a ref that triggers nothing
  before: GET /commits/408984930a0… → HTTP 422     ← GitHub has never seen it
  ✓ pushed to refs/local-ci/408984930a0…
  after:  GET /commits/408984930a0… → HTTP 200     ← now it has

6 · Confirm no workflow was triggered
  ✓ 0 workflow runs for this SHA

7 · Publish the receipt
    POST /statuses → HTTP 201                      ← receipt on an unpushed commit
  ✓ receipt published

8 · Read it back, as CI would
    local-ci/v1/@woocommerce/number::JavaScript  success  creator=MrJnrman
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
- **Fidelity after publishing.** Step 2 refuses to publish unless the working tree
  is clean and the branch already contains the tip of trunk, which is what makes
  HEAD's tree identical to the merge CI tests. Nothing invalidates the receipt if
  trunk moves *afterwards*, so a receipt can go stale between publishing and merge.
  The design's answer is that CI reruns the checks at that point.

## Forks cannot produce receipts

A commit status has to live on the repository the pull request targets, and a fork
contributor has no write access there — so `POST /statuses` fails and no receipt is
published. The workflow then finds nothing and runs the checks normally, which is
the correct outcome rather than a failure.

This is a boundary, not a bug: the mechanism only applies to branches on this
repository, which matches the trusted-org-member model the design assumes. Fork
contributions always get full CI.

## Note on cleanup

An earlier run was interrupted mid-flight and leaked its temporary ref. The script
now removes it from a shutdown handler and on `SIGINT`/`SIGTERM`. Any real
implementation needs the same, or abandoned refs accumulate on the remote.
