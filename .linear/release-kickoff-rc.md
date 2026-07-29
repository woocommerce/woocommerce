# [{release_main_version}] Release `{release_version}`

These are the instructions for releasing `{release_version}`, scheduled for `{release_date}`.

----

Perform all the steps below in order. When running _any_ GitHub workflow, ensure you do it from the `trunk` branch (the default) and input the release version or branch as indicated.

Keep the _[Release Troubleshooting & Recovery](https://developer.woocommerce.com/docs/contribution/releases/troubleshooting/)_ guide handy, in case you encounter any issues.

----

### 1. Release readiness review

Go through this with the Product DRI (named on the parent tracking issue) before starting the build. The goal is a deliberate "this RC is ready to go out" call, with the evidence in one place. See the [readiness guide](https://developer.woocommerce.com/docs/contribution/releases/readiness/) for details on each item.

- [ ] Review the QIT compatibility regression sweep report for this prerelease. Every introduced issue has a verdict: blocking or not.
- [ ] Every open finding against this release (bug reports, testing threads, monitoring alerts) has a linked issue and a verdict: release-blocking / fix in a point release / not a bug.
- [ ] The rollback path for this release is known: who reverts, how, and what revert means for this version (see the [troubleshooting guide](https://developer.woocommerce.com/docs/contribution/releases/troubleshooting/)).
- [ ] Comms are ready: the changelog is in shape and there's a known-issues list if the verdicts above left anything open.

If an item can't be checked, raise it in `#woo-core-releases` before continuing - delaying an RC is cheaper than reverting a stable.


### 2. Pre-build checks

- [ ] Confirm [GitHub services](https://www.githubstatus.com/) are operational.
- [ ] Verify no open [issues]({repository_url}/issues?q=is:open+is:issue+milestone:{release_milestone}) or [pull requests]({repository_url}/pulls?q=is:open+is:pr+milestone:{release_milestone}) exist against the `{release_milestone}` milestone. Ping authors as needed to merge or close.
- [ ] Ensure that there aren't any pull requests [with label "cherry pick failed"]({repository_url}/pulls?q=is:pr+label:%22cherry+pick+failed%22) that apply to this release that haven't been actioned.
- [ ] Confirm the `Stable tag` value [in the readme.txt on the release branch]({repository_url}/blob/{release_branch}/plugins/woocommerce/readme.txt#L7) matches the one [on WordPress.org's `trunk`](https://plugins.trac.wordpress.org/browser/woocommerce/trunk/readme.txt#L7).


### 3. Build the release package

- [ ] Run workflow **[Release: Bump version number]({repository_url}/actions/workflows/release-bump-version.yml)**: enter `{release_main_version}` as _Release branch_ and `{release_type}` as _Type of version bump to perform_.
- [ ] Review and merge the PR that was generated against the release branch. Check for remaining open [issues]({repository_url}/issues?q=is:open+is:issue+milestone:{release_milestone}) or [pull requests]({repository_url}/pulls?q=is:open+is:pr+milestone:{release_milestone}) in the `{release_milestone}` milestone.
- [ ] Run workflow **[Release: Compile changelog]({repository_url}/actions/workflows/release-compile-changelog.yml)**: enter `{release_main_version}` as _Version_ and leave _Release date_ empty, except when building the package ahead of schedule.
- [ ] Review and merge the PRs that were generated: one against `trunk` and another one against the release branch. Both are linked in the workflow run.
- [ ] Run workflow **[Release: Build ZIP file]({repository_url}/actions/workflows/release-build-zip-file.yml)** to build the asset and create the GitHub release: enter `{release_main_version}` as _Release branch_ and check _Create GitHub release_.
- [ ] Confirm that a draft `{release_version}` release [was created in the repository]({repository_url}/releases) with an attached `woocommerce.zip` asset.


### 4. Upload the release to WordPress.org

- [ ] Run workflow **[Release: Upload release to WordPress.org]({repository_url}/actions/workflows/release-upload-to-wporg.yml)**: enter `{release_version}` as _Release tag to upload_ and make sure to check 'I confirm that I want to upload a release to WordPress.org.'
- [ ] Confirm that SVN tag `{release_version}` [exists on WordPress.org SVN](https://plugins.svn.wordpress.org/woocommerce/tags/{release_version}).
- [ ] Log [into WordPress.org](https://wordpress.org/plugins/developers/releases/) using the credentials from the `WordPress.org "WooCommerce" user account` secret in the secret store and approve the release.
- [ ] After a few minutes, confirm that [`{release_version}` is available for download](https://downloads.wordpress.org/plugin/woocommerce.{release_version}.zip).


### 5. Deploy to the staging environment

- [ ] Follow the [guide to deploy to the staging environment](https://wp.me/PCYsg-18BQ) and monitor for at least {release_monitoring_time} hours after deploy.
- [ ] Share the `#atomic` staging thread in `#woo-core-releases` for visibility. If monitoring surfaces an issue, create a dedicated `#woo-core-releases` thread for that issue.

**If a critical issue was detected while monitoring...**

- [ ] Request a revert in the staging environment.
- [ ] Pause the release process and **do not continue with any steps on this issue**. Follow the procedure in the [troubleshooting guide](https://developer.woocommerce.com/docs/contribution/releases/troubleshooting/#deploy-serious-bug) instead.


### 6. Publish the release

- [ ] Publish the `{release_version}` [release draft]({repository_url}/releases) that was previously created, as well as any other `{release_main_version}` drafts that might exist from previous attempts. **Do not** check "Set as the latest release".
