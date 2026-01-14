---
post_title: Release Troubleshooting
sidebar_label: Troubleshooting
sidebar_position: 7
---

# Release Troubleshooting & Recovery

This page provides guidance for troubleshooting and recovering from issues that may arise during the WooCommerce release process. It covers common scenarios, recommended actions, and best practices to help ensure releases are handled smoothly and any problems are resolved efficiently.


## Scenarios / FAQ

### A workflow failed while building the release

1. **Open the workflow run details** in GitHub (under the **Actions** tab) to see exactly where and why the failure occurred. Most of the time, the workflow will display a clear error message.
2. **Read the error message carefully.** Sometimes the problem is as simple as a missing workflow configuration or skipped step.
3. **If you're unsure what the error means or how to proceed,** don't hesitate to ask for help in the release Slack channel. It's better to get a second opinion than to guess.

⚠️ _Do not re-run any workflows until you understand the cause of the failure._ Re-running without fixing the root issue can make things more complicated.

### CI is failing on a release-related PR

During the release process, you may encounter CI test failures on release-related PRs. These failures sometimes occur because test fixes were merged to trunk but not backported to the release branch before it was cut.

1. **Identify the cause**: Check if the failing tests pass on trunk. If they do, the fix likely needs to be backported.
2. **Backport test fixes**: If possible, [backport](/docs/contribution/releases/backporting) the relevant test fixes from trunk to the release branch, then re-run the CI workflow.
3. **Handle complex cases**: If backporting isn't possible due to dependencies or the cause isn't clear, document what you've found and ask for help in the release Slack channel. The "Heart of Gold - Flux" team can assist with resolving CI issues that block release work.

### Something looks wrong in the final release ZIP. Can I start over? {#can-i-start-over-id}

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


### A serious bug was detected during internal checks / monitoring

If you find a serious bug during internal checks or monitoring **before** the release is marked stable on WordPress.org:

- Pause the release process immediately.
- Coordinate with the relevant engineering team(s) to develop a fix. The fix should be shipped in a subsequent patch release.
- Do not publish the draft GitHub release for this version, but also, **do not delete** the existing draft release or tag.
- For more details on what to do with skipped versions, see [the section below](#version-skipped-id).


### A version was skipped due to a bug. {#version-skipped-id}

If a bug forces you to skip marking a version as stable on WordPress.org:

- Notify the relevant engineering team(s) so they're aware and can provide an ETA for a fix.
- Loop in **Dev Advocacy** so they can help with public communications.
- If the bug is found on Monday and a fix won't be ready for Tuesday, work with Dev Advocacy to announce a delay. Read more [on delays below](#release-delay).

On the Release mechanics side:

- Merge any auto-generated PRs that should be merged, as if the release had been marked stable.
- Do not delete any draft GitHub releases or tags for the problematic version.
- After a fixed release is deployed and marked as stable:
    - Publish all GitHub releases for any skipped versions, in order.
    - Only mark the actual valid release as "latest release".

### A critical bug surfaced after the release was marked stable on WordPress.org

If a severe regression or bug is discovered (e.g., checkout failure or unrecoverable data loss):

1. Immediately notify the relevant engineering team(s).
2. Prepare to do a [Point Release](/docs/contribution/releases/point-releases).
3. Temporarily move the stable tag on WordPress.org back to the previous known-good version:
   - Identify the correct previous version and note its exact number.
   - Use the [`Release: Update stable tag`](https://github.com/woocommerce/woocommerce/actions/workflows/release-update-stable-tag.yml) workflow, making sure to check the _Revert_ option to allow downgrading.
   - Merge any auto-generated PRs right away.

### The release needs to be delayed. What should we do? {#release-delay}

1. Create an internal Slack thread to communicate with the engineering teams as well as Dev Advocacy. This also provides an opportunity for teams to share any additional context and verify or challenge schedule changes.
2. Ask Dev Advocacy to communicate the delay publicly.
3. If there's a clear ETA on the patch release with a fix, [update the release calendar](https://developer.woocommerce.com/release-calendar/) with the new dates.

Remember to not plan the patch release [too close to the weekend](#release-delay-weekend-id).

### The release was delayed. Can we still release after Tuesday? {#release-delay-weekend-id}

In general, avoid releasing after Tuesday, especially close to a weekend.

Even if a patch is ready and seems to fix the problem, it's hard to be sure there aren't other hidden issues and a rushed release late in the week means most of the team won't be available to monitor or respond to problems.

As a rule of thumb, when in doubt, consider to delay the release by a week for confidence.
