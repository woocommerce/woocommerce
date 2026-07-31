---
post_title: Release Troubleshooting
sidebar_label: Troubleshooting
sidebar_position: 7
---

# Release Troubleshooting & Recovery

This page provides guidance for troubleshooting and recovering from issues that may arise during the WooCommerce release process. It covers common scenarios, recommended actions, and best practices to help ensure releases are handled smoothly and any problems are resolved efficiently.

:::tip

`@woo-core-release` in `#woo-core-releases` is the point of contact for release-process questions in general, not just the scenarios below. Use it for escalations, second opinions, or anything this doc doesn't answer.

:::

## Scenarios / FAQ

### A workflow failed while building the release

1. **Open the workflow run details** in GitHub (under the **Actions** tab) to see exactly where and why the failure occurred. Most of the time, the workflow will display a clear error message.
2. **Read the error message carefully.** Sometimes the problem is as simple as a missing workflow configuration or skipped step.
3. **If you're unsure what the error means or how to proceed,** don't hesitate to ask for help in the release Slack channel. It's better to get a second opinion than to guess.

⚠️ _Do not re-run any workflows until you understand the cause of the failure._ Re-running without fixing the root issue can make things more complicated.

### The Code Freeze workflow failed

Two known failure modes are false positives rather than real blockers:

- **The "open automation PRs" check fails.** This check flags _any_ open PR authored by the release automation, not just PRs that could affect the feature freeze. If the open PRs are unrelated to the freeze, the check can be temporarily disabled and the workflow re-run.
- **"Webhook request failed: Duplicate post detected (409)".** This means a Call for Testing post for the same version already exists (usually left over from earlier testing), so the workflow couldn't create a new one. Remove or account for the stale post and re-run.

In both cases, verify that the parts of the workflow that did run (branch creation, version bumps) completed before re-running.

### A workflow failed or timed out while creating a PR or pushing changes

Workflows that perform Git operations (such as `Release: Bump version number` or `Release: Build ZIP file`) can occasionally fail with a timeout while creating a PR or pushing to the repository. This is caused by a GitHub security check that compares workflow files against their `trunk` versions during the operation: when the changeset between the release branch and `trunk` is large, the check can time out.

1. **Re-run the workflow.** The timeout is intermittent and not related to the release contents, so a re-run often succeeds.
2. **If the failure persists,** sync the workflow files (`.github/workflows/`) from `trunk` to the release branch to reduce the diff, then re-run.

### The changelog workflow fails with "Your local changes would be overwritten"

The `Release: Compile changelog` workflow checks out both `trunk` and the release branch. The file `tools/monorepo-utils/dist/index.js` is a build artifact that is committed to the repository, and on the rare occasions it's updated on `trunk`, the two branches diverge and the checkout fails with an error similar to _"Your local changes to the following files would be overwritten"_.

To unblock, backport the updated `tools/monorepo-utils/dist/index.js` from `trunk` to the release branch and re-run the workflow.

### CI is failing on a release-related PR

During the release process, you may encounter CI test failures on release-related PRs. These failures sometimes occur because test fixes were merged to trunk but not backported to the release branch before it was cut.

1. **Check [GitHub's status page](https://www.githubstatus.com/) first**: PRs stuck on _"Waiting for status to be reported"_ or entire batches of failing jobs are often caused by a GitHub Actions incident, not by the release.
2. **Identify the cause**: Check if the failing tests pass on trunk. If they do, the fix likely needs to be backported.
3. **Backport test fixes**: If possible, [backport](/docs/contribution/releases/backporting) the relevant test fixes from trunk to the release branch, then re-run the CI workflow.
4. **Re-run before assuming a regression**: If nothing relevant changed since the last green run, the failure is likely flakiness. If a test keeps flaking on the release branch, file an issue for the owning team and don't let it block the release.
5. **Handle complex cases**: If backporting isn't possible due to dependencies or the cause isn't clear, document what you've found and ask for help in the release Slack channel.

Note that a failing check that is not required and is clearly unrelated to the change (for example, a comparison job that references code only present on trunk) does not have to block merging a release-related PR. When in doubt, ask in the release Slack channel before merging over a failure.

### The "Build ZIP file" workflow refuses to build because of open PRs

The blocker check in this workflow looks for open PRs whose **base branch is the release branch** — milestone membership is irrelevant, so removing a milestone from a PR won't unblock the build. Every open PR targeting `release/X.Y` has to be addressed:

- Merge the ones that belong in the release.
- Close stale or redundant ones — including auto-generated cherry-pick/backport PRs whose changes are already present on the release branch. Leave a short comment explaining why, and delete their branches.

### Something looks wrong in the final release ZIP. Can I start over?

If, after downloading and unzipping the generated artifact, something seems off (e.g., missing files, incorrect changelog, or version mismatch), this usually means:

- A required workflow didn't run or failed (for example, the changelog step was skipped).
- An auto-generated PR from a workflow wasn't merged into the release branch before building the ZIP.

**Before you try to build the version again:**

1. Delete any GitHub draft release or tag for the incorrect release:
   - Go to **Code > Releases** and delete the draft release.
   - In **Code > Tags**, delete the tag for the incorrect version. _If you skip this, the final release may point to the wrong commit in history._
2. Check the status of the `release/X.Y` branch (either in the GitHub UI or locally after pulling the latest changes).
3. Figure out which step failed. For example, if the plugin header version is correct but the changelog is missing, only the changelog step needs to be re-run.
4. Review any [auto-generated PRs](https://github.com/woocommerce/woocommerce/pulls?q=is%3Aopen+is%3Apr+author%3Aapp%2Fgithub-actions+label%3ARelease): if there are open PRs that weren't merged and are no longer needed, close them and delete their branches.

**Once you know which step failed,** re-run only that step as described in the [Building & Publishing guide](/docs/contribution/releases/building-and-publishing). Make sure to run skipped workflows in the correct order and double-check all configuration (version number, release type, etc.) before proceeding.

### The "Upload release to WordPress.org" workflow failed or timed out

Uploads to WordPress.org SVN are the most failure-prone step of the release, especially for the first beta of a cycle, which carries the largest changeset since the previous SVN tag. Later releases in the cycle have much smaller diffs and rarely hit these issues.

**Before re-running anything, check what actually landed on the SVN side.** SVN commits are atomic, and a run that "failed" on the GitHub side may still have committed successfully, because timeouts sometimes happen while the server is sending its response. Verify whether the [SVN tag](https://plugins.trac.wordpress.org/browser/woocommerce/tags) for the version exists and whether [SVN `trunk`](https://plugins.trac.wordpress.org/browser/woocommerce/trunk) was updated:

- **If the tag exists and looks correct,** the upload succeeded regardless of the workflow status: continue with the release process. Re-running the workflow against an existing tag will fail.
- **If SVN `trunk` was updated but the tag is missing,** the tag can be recreated with a manual copy, since the workflow commits to `trunk` first and then copies it to the tag:

  ```bash
  svn copy https://plugins.svn.wordpress.org/woocommerce/trunk https://plugins.svn.wordpress.org/woocommerce/tags/X.Y.Z -m "Tag X.Y.Z"
  ```

- **If nothing landed,** re-run the workflow. A failed run resends everything, so there is no risk of a partial upload.

Common errors and what they mean:

| Error | Cause | Action |
| ----- | ----- | ------ |
| `504 Gateway Time-out` during "Committing transaction" | The commit may have succeeded; the timeout occurred on the server response | Verify the tag on SVN before doing anything else |
| `E000104` / `500 Internal Server Error` | Server-side issue on WordPress.org | Retry; if it persists, wait and try again later |
| `E175013: Access to '/!svn/me' forbidden` | Invalid or outdated SVN credentials | Update the SVN credentials secret in the repository and re-run |
| `E200009: a peg revision is not allowed here` | A file name in the build contains an `@` character, which SVN interprets as a peg revision | Escape the `@` in the workflow's SVN operations |

**"The released ZIP differs from SVN trunk" reports.** SVN commits are atomic, so a partially uploaded release is not a plausible explanation. The usual cause is that the comparison was made against SVN `trunk`, which is updated on every upload — including prereleases — and can therefore hold a _newer_ version than the latest point release. The source of truth for any given release is its SVN **tag**, not `trunk`.

### A serious bug was detected during internal checks / monitoring

For RC and stable releases, deploying to our staging environment and monitoring for errors is required before the release is made publicly available. If a serious bug is detected during this monitoring period, follow these steps:

1. **Request a revert** of the deploy in the staging environment.
2. **Pause the release process** immediately and do not continue with any remaining steps in the tracking issue.
3. **Update the tracking issue** to reflect that the release is blocked, including details about the bug.
4. **Do not publish** any of the draft GitHub releases that were created, but also **do not delete** them. They will be published later along with the version that passes validation.
5. **Coordinate with the relevant engineering team(s)** to develop a fix.
6. **Involve Developer Advocacy** if the release schedule needs to be adjusted or communicated publicly ([read more on delays below](#the-release-needs-to-be-delayed-what-should-we-do)).

#### How to proceed once the bug fix is merged into the release branch?

1. **Create a new tracking issue** for the new version (e.g., `-rc.2` if the bug was detected during `-rc.1`, or `x.y.2` if detected while monitoring `x.y.1`) by running the [`Release: Create Tracking Issue`](https://github.com/woocommerce/woocommerce/actions/workflows/release-create-tracking-issue.yml) workflow. Do not reuse the existing tracking issue. The new issue is automatically nested under the `[X.Y] Release tracking` parent in Linear.
2. **Follow the release procedure as normal** for the new version.
3. **Publish all draft releases** for the affected version series. Even if the prior version wasn't made publicly available, it must be published along with the valid version. Each version will have its own changelog section.

### A critical bug surfaced after the release was marked stable on WordPress.org

If a severe regression or bug is discovered (e.g., checkout failure or unrecoverable data loss):

1. Immediately notify the relevant engineering team(s).
2. If the severity warrants it (e.g., checkout failure, data loss, or other critical impact affecting many stores), temporarily move the stable tag on WordPress.org back to the previous known-good version, so new installs and updates stop landing on the broken version while the fix is being prepared:
   - Identify the correct previous version and note its exact number.
   - Use the [`Release: Update stable tag`](https://github.com/woocommerce/woocommerce/actions/workflows/release-update-stable-tag.yml) workflow, making sure to check the _Revert_ option to allow downgrading.
   - Merge any auto-generated PRs right away.
3. Follow the [Point Releases guide](/docs/contribution/releases/point-releases) to create a tracking issue, prepare the fix, and ship the patch.

⚠️ _After reverting the stable tag, make sure the upcoming point release doesn't silently overwrite it._ Update the `Stable tag` in the release branch's `readme.txt` to the reverted version as well, so building the patch release doesn't move the tag forward again before the fix is confirmed. The stable tag is then bumped intentionally as part of the point release's own publishing steps.

### The release is out, but sites don't see the update yet

This is usually not a problem:

- WordPress checks for plugin updates roughly every 12 hours by default, so it can take a while for a newly published release to be offered on any given site.
- Only the `Stable tag` in the `readme.txt` on WordPress.org's SVN `trunk` controls what the updater offers. The `readme.txt` bundled inside a released ZIP always shows the previous version as stable: that's expected, since published releases are frozen and can't be edited.

### The release needs to be delayed. What should we do?

1. Create an internal Slack thread to communicate with the engineering teams as well as Dev Advocacy. This also provides an opportunity for teams to share any additional context and verify or challenge schedule changes.
2. Ask Dev Advocacy to communicate the delay publicly.
3. If there's a clear ETA on the patch release with a fix, [update the release calendar](https://developer.woocommerce.com/release-calendar/) with the new dates.

Remember to not plan the patch release [too close to the weekend](#the-release-was-delayed-can-we-still-release-after-tuesday).

### The release was delayed. Can we still release after Tuesday?

In general, avoid releasing after Tuesday, especially close to a weekend.

Even if a patch is ready and seems to fix the problem, it's hard to be sure there aren't other hidden issues and a rushed release late in the week means most of the team won't be available to monitor or respond to problems.

As a rule of thumb, when in doubt, consider to delay the release by a week for confidence.
