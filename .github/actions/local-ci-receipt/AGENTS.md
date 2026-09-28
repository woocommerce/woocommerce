# Live test runbook

Run this for any change to `receipt.js`, `action.yml`, the `ci.yml` receipt
step or its `if:` guards, or `.github/local-ci.json`. It is the action's only
test: it proves the consumer end to end in real CI without touching trunk.
Budget: ~30 minutes, mostly waiting. Everything created here is deleted at
the end.

## Preconditions

- The change is committed on a feature branch (call it `FEATURE`).
- `gh` is signed in as someone whose login is in `allowlist.json` of the
  tool repository, and `gh local-ci auth` has been run once on this machine.
- `gh local-ci version` works (the installed build must be current: in the
  tool clone, `go build -o gh-local-ci . && gh extension install .`).
- The repository variable `LOCAL_CI_RECEIPTS_DISABLED` is `0` (check with
  `gh variable list`). If it is `1`, stop and ask; someone turned it off.

## 1. Scratch base branch (stands in for trunk)

```sh
git checkout -b test/local-ci-base FEATURE
```

Edit `.github/local-ci.json`: set `"baseBranch": "test/local-ci-base"` and
make sure `"enabled": true`. Commit (`--no-verify` is fine) and push:

```sh
git commit -am "Scratch base for the live receipt test (not for merge)" --no-verify
git push -u origin test/local-ci-base
```

Why: the consumer reads the config from the PR's base branch and refuses a PR
that does not target `baseBranch`. The scratch base carries the change under
test *and* a config that names itself.

## 2. Child branch with receipts

```sh
git checkout -b test/local-ci-child
printf '\n// live receipt test marker\n' >> packages/js/number/src/index.ts
git commit -am "Touch @woocommerce/number for the live receipt test" --no-verify
gh local-ci plan          # expect: merge base = the scratch base tip; 4 JavaScript unit jobs
gh local-ci run --push    # runs them, publishes 4 receipts, pushes the branch
```

`run` must end with `All 4 job(s) passed` and `pushed test/local-ci-child`.
If it stops at authorisation or the allowlist, fix that before going on.

## 3. Draft PR against the scratch base

```sh
gh pr create --draft --base test/local-ci-base --head test/local-ci-child \
  --title "Scratch: live test of the local CI receipt consumer (do not merge)" \
  --body "Throwaway. Base branch carries the consumer under test with enabled: true; head has receipts. Both branches will be deleted."
```

## 4. Positive path

Wait for the `ci` workflow run on the head SHA (`gh run list --workflow ci.yml
--branch test/local-ci-child`). For each `JavaScript - … [unit]` job:

- conclusion `success`, duration about 10–15 s;
- steps `Install Monorepo` and `Run tests (unit)` are `skipped`;
- the job log contains `substituted=true reason=receipt … by <login> (has
  <permission> permission)`.

Read a job's log with
`gh api repos/woocommerce/woocommerce/actions/jobs/<job id>/logs | grep substituted=`.
Every other job of the run (e2e, performance) runs in full; that is
expected.

If any JavaScript job ran in full, the `reason=` line says why. Fix, commit
on `FEATURE`, cherry-pick onto **both** scratch branches (the head config must
stay byte-identical to the base config), run `gh local-ci run --push` on the
child again (new head → new receipts), and re-check.

## 5. Kill switch

Set the repository variable to `1`, then trigger a **new** run (a re-run
keeps the old variable value):

```sh
gh variable set LOCAL_CI_RECEIPTS_DISABLED --body 1
git commit --allow-empty -m "Trigger a run with the kill switch set" --no-verify
git push
```

Expected on every JavaScript job: `substituted=false reason=kill switch
LOCAL_CI_RECEIPTS_DISABLED=1`, install and tests run. Then set it back:

```sh
gh variable set LOCAL_CI_RECEIPTS_DISABLED --body 0
```

## 6. Config change

Edit the `$comment` in `.github/local-ci.json` on the child (any one-word
change), commit, push. Expected on every JavaScript job:
`substituted=false reason=.github/local-ci.json differs between the pull
request and the base branch; config changes always get full CI`.

## 7. Record and clean up

Add a row per run to the evidence document in the tool repository
(`docs/evidence-<date>.md`): run URL, what was set, the decision line, the
per-job durations. Then:

```sh
gh pr close <number> --delete-branch --comment "Live test complete; results recorded."
git push origin --delete test/local-ci-base
git checkout FEATURE && git branch -D test/local-ci-child test/local-ci-base
git ls-remote origin 'refs/local-ci/*'     # must print nothing
```

Confirm `LOCAL_CI_RECEIPTS_DISABLED` is `0`.

## Reading the result

- A job that ran in full with `reason=no receipt named …` means the tool
  published under a different name or config hash than CI expected: compare
  the receipt's name and `external_id` on the commit
  (`gh api repos/woocommerce/woocommerce/commits/<sha>/check-runs?app_id=4830646`)
  with what `receipt.js` computes.
- `author … has unknown repository permission and association CONTRIBUTOR`
  means the permission lookup failed; check the token's permissions on the
  job.
- `lookup failed, running normally: …` is an API or parse error; the message
  says which.
