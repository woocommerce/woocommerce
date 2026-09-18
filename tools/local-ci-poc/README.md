# Local CI receipts — proof of concept

Demonstrates the one mechanism in the local-first CI proposal that isn't obvious:
**a commit status can be attached to a commit GitHub has never seen, before the
branch that would trigger CI is pushed.**

That ordering is what removes the race. Publishing a result after pushing the
branch is too late — the workflow has already started.

## Run it

```sh
php tools/local-ci-poc/poc.php                    # publish receipts for HEAD
php tools/local-ci-poc/poc.php --push             # ... and push the branch straight after
php tools/local-ci-poc/poc.php --only=number      # ... only these projects
php tools/local-ci-poc/poc.php --jobs=8           # ... this many at a time
```

Without `--push` the receipt is published and the push is left to you. That leaves
a window: commit anything more before pushing and the pushed SHA has no receipt,
so CI runs everything. `--push` closes it by pushing while the receipt still
describes HEAD. It is opt-in because publishing a receipt has no visible effect,
while pushing a branch starts CI and notifies reviewers.

Runs against the real repository. Creates one ref and one commit status, then
removes the ref. Safe to run repeatedly.

Requires the [GitHub CLI](https://cli.github.com), authenticated with `gh auth
login`. It is the only supported source of credentials, and the script stops with
instructions if it is missing or logged out. Reading tokens from the environment
or the git credential store as well would let the script authenticate as one
identity while `gh` reports another, and the receipt's creator is the whole basis
for trusting it.

It refuses to publish anything it cannot honestly describe: the working tree must
be clean (the check runs against the tree, but the receipt names HEAD), the branch
must already contain the tip of trunk (CI tests the merge, not the commit), HEAD
must not move while the check runs, and a rejected `POST /statuses` exits non-zero
rather than printing the code and carrying on.

## What it substitutes

It asks `ci-jobs` — the same planner CI uses — which jobs this diff would produce,
keeps the JavaScript unit jobs, and runs each one with the command the planner
gives. A change under `packages/js` typically plans 23 of them.

Substitution is **per job**. Each job that passes gets its own receipt named after
it; each that fails gets none and runs in CI as usual. One local failure therefore
costs one job, not the whole run.

Two constraints are worth knowing before reading the numbers:

- **CI gives each job a runner; a laptop shares one machine.** The checks run
  concurrently here — half the cores, capped at eight, overridable with
  `--jobs=N` — which on a 16-core machine took eight packages from 67s to 27s.
  That still is not 23 runners: `@woocommerce/admin-library` and
  `@woocommerce/components` take minutes on their own, so substituting all 23
  frees 23 CI slots but is not free locally. `--only=<substring>` exists for that.
- **Not every job is honestly runnable locally.** CI builds each project's
  dependencies before testing (`build-type: dependencies`); this script does not.
  `@woocommerce/experimental` and `@woocommerce/customer-effort-score` fail
  locally for that reason alone. They get no receipt, so CI runs them — the
  failure mode is wasted local time, never a wrongly skipped job.

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

- **Matrix subtraction.** The job is still scheduled and still checks out the
  repository; a receipt skips its install and its test run, which is where the
  time goes, but not the job itself. Removing it from the matrix needs the planner
  change, and the merge guard must learn the planned set first, or a missing job is
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

## How the files are arranged

`poc.php` is the narrative — the ten steps in order, and nothing else. Everything
it calls sits in `lib/`, one concern per file:

| File | What it owns | Reusable elsewhere |
| --- | --- | --- |
| `Shell.php` | Running commands | Yes |
| `Output.php` | Terminal output and colour | Yes |
| `Git.php` | Reading and writing the local repository | Yes |
| `GitHubApi.php` | REST calls, and the one way a token is obtained | Yes |
| `Receipts.php` | What a receipt is called, published and read | Yes |
| `CheckRunner.php` | Running checks concurrently | Yes |
| `TemporaryRef.php` | The ref that makes a commit addressable | Yes |
| `Cleanup.php` | Stopping everything this tool started | Yes |
| `Options.php` | Command line arguments | Yes |
| `JobPlanner.php` | **Which jobs CI would run** | **No — replace this** |

Only `JobPlanner` knows anything about this repository: it shells out to
`pnpm utils ci-jobs` and filters on WooCommerce's job naming. To use this
elsewhere, replace that one class with something that knows how the other
repository decides what CI runs, keeping the same return shape
(`name`, `projectName`, `command`). `Receipts::context_for()` is the contract the
workflow must agree with, so those two change together or not at all.

## Note on cleanup

Cleanup is armed at startup, before anything is started, and covers the shutdown
handler plus `SIGINT`/`SIGTERM`/`SIGHUP`.

Two things made that necessary rather than tidy. An early version registered its
handlers only when it created the temporary ref, which is most of the way through
a run — so an interrupt during the checks, by far the longest phase, was
unguarded. And terminating the process this tool spawns is not enough: that
process is a package manager, which runs a test runner, which forks its own
workers. Signalling only the child left 48 test processes running. Cleanup now
walks the tree and kills children before parents, so a parent cannot notice one
die and start a replacement.
