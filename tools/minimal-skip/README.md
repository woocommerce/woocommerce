# Minimal skip

Run one test on your machine, leave a note on the commit saying it passed, and
CI skips it.

```sh
./tools/minimal-skip/skip.sh
git push
```

`Minimal skip: @woocommerce/number` then skips its test step. Push without
running the script and it runs the test as normal.

## Why it works

A commit status has to be attached to a commit GitHub knows about, and the point
is to publish *before* the branch is pushed — after is too late, because CI has
already started. So the script pushes the commit to `refs/local-skip/<sha>`
first. That is outside `refs/heads/*` and `refs/tags/*`, so no workflow can
trigger on it, but the commit becomes addressable and a status will attach. The
ref is deleted afterwards; the status stays.

## What this does not do

**It does not check who published the receipt.** Anyone who can write a status to
this repository can skip this test without running it. That check is the whole
difference between a demonstration and something safe to turn on, and it is not
here.

Nor does it decide *which* jobs are eligible, notice that the branch has fallen
behind trunk, or remove the job from the matrix — the job is still scheduled, it
just does no work. This exists to show the mechanism in as few lines as possible.
