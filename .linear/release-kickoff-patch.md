# [{release_main_version}] Release `{release_version}`

These are the instructions for releasing `{release_version}`, scheduled for `{release_date}`.

----

Perform all the steps below in order. When running _any_ GitHub workflow, ensure you do it from the `trunk` branch (the default) and input the release version or branch as indicated.

Keep the _[Release Troubleshooting & Recovery](https://developer.woocommerce.com/docs/contribution/releases/troubleshooting/)_ guide handy, in case you encounter any issues.

----

### 1. Go/no-go

For scheduled stable releases, hold this 24-48 hours before the release date, with the Product DRI (named on the parent tracking issue). Bring in QualityOps and the Atomic contact when staging or monitoring left open questions. See the [readiness guide](https://developer.woocommerce.com/docs/contribution/releases/readiness/) for details.

- [ ] The readiness review is complete and its verdicts still hold.
- [ ] No new blocking findings since the readiness review (check `#woo-core-releases` threads and the [newest issues]({repository_url}/issues?q=is%3Aissue%20state%3Aopen%20sort%3Acreated-desc)).
- [ ] Decision recorded as a comment on this issue - **go**, **no-go**, or **go with conditions** - with the names behind it.

For scheduled releases, the readiness review is the one in the RC sub-issue. Point releases have no RC: run the [readiness criteria](https://developer.woocommerce.com/docs/contribution/releases/readiness/) over the changes being shipped as part of this go/no-go. For unscheduled point releases shipping an urgent fix, a quick go/no-go with the Product DRI in `#woo-core-releases` is enough - record the outcome here all the same.


### 2. Pre-build checks

- [ ] Confirm [GitHub services](https://www.githubstatus.com/) are operational.
- [ ] Verify no open [issues]({repository_url}/issues?q=is:open+is:issue+milestone:{release_milestone}) or [pull requests]({repository_url}/pulls?q=is:open+is:pr+milestone:{release_milestone}) exist against the `{release_milestone}` milestone. Ping authors as needed to merge or close.
- [ ] Ensure that there aren't any pull requests [with label "cherry pick failed"]({repository_url}/pulls?q=is:pr+label:%22cherry+pick+failed%22) that apply to this release that haven't been actioned.
- [ ] Confirm the `Stable tag` value [in the readme.txt on the release branch]({repository_url}/blob/{release_branch}/plugins/woocommerce/readme.txt#L7) matches the one [on WordPress.org's `trunk`](https://plugins.trac.wordpress.org/browser/woocommerce/trunk/readme.txt#L7).


### 3. Build the release package

- [ ] Run workflow **[Release: Bump version number]({repository_url}/actions/workflows/release-bump-version.yml)**: enter `{release_main_version}` as _Release branch_ and `stable` as _Type of version bump to perform_.
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

- [ ] Run workflow **[Release: Update stable tag]({repository_url}/actions/workflows/release-update-stable-tag.yml)**: enter `{release_version}` as _Version_ and make sure to check 'I confirm that I want to update the stable tag (this will update the SVN and GitHub stable tags).'
- [ ] Publish the `{release_version}` [release draft]({repository_url}/releases) that was previously created, as well as any other `{release_main_version}` drafts that might exist from previous attempts. **Ensure** that "Set as the latest release" is checked for `{release_version}`.


### 7. Post-release tasks

- [ ] Wait at least 1 hour for all automations to complete and make sure to merge any follow-up [PRs]({repository_url}/pulls?q=is:open+is:pr+milestone:{release_milestone}) under the `{release_milestone}` milestone.
- [ ] Continue monitoring for bugs related to the release for at least 3 days. See the [release monitoring guide](https://developer.woocommerce.com/docs/contribution/releases/monitoring/) for more details.
