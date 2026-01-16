---
post_title: Building and Publishing a Release
sidebar_label: Building and Publishing
sidebar_position: 1
---

# Building and Publishing a Release

:::important

While this guide can be used as reference, keep in mind that version-specific instructions are provided in the [release tracking issues](https://github.com/woocommerce/woocommerce/issues?q=state%3Aopen%20label%3A%22Release%22%20author%3Aapp%2Flinear%20tracking) created ahead of the release cycle, and should be preferred.

:::

This page provides an overview of the steps required to build any WooCommerce release from the release branch. Review the flowchart to understand the overall process and the decision table. Step-by-step instructions are provided below.

## Overview

![Release Process flow chart](/img/doc_images/release-process.png)

## Instructions

Perform all the steps below in order. When running _any_ GitHub workflow, ensure you do it from the `trunk` branch (the default) and input the release version or branch as indicated.

Keep the _[Release Troubleshooting & Recovery](/docs/contribution/releases/troubleshooting)_ guide handy, in case you encounter any issues.

### Steps

#### 1. Pre-build checks

- [ ] Confirm [GitHub services](https://www.githubstatus.com/) are operational.
- [ ] Verify no open issues or pull requests exist against the [release milestone](https://github.com/woocommerce/woocommerce/milestones/). Ping authors as needed to merge or close.
- [ ] Ensure that there aren't any pull requests [with label "cherry pick failed"](https://github.com/woocommerce/woocommerce/pulls?q=is:pr+label:%22cherry+pick+failed%22) that apply to this release that haven't been actioned.
- [ ] Confirm the `Stable tag` value in the readme.txt on the release branch matches the one [on WordPress.org's `trunk`](https://plugins.trac.wordpress.org/browser/woocommerce/trunk/readme.txt#L7).

#### 2. Build the release package

- [ ] Run workflow **[Release: Bump version number](https://github.com/woocommerce/woocommerce/actions/workflows/release-bump-version.yml)**: enter the release branch as _Release branch_ and choose the release type from the dropdown.
- [ ] Review and merge the PR that was generated against the release branch.
- [ ] Run workflow **[Release: Compile changelog](https://github.com/woocommerce/woocommerce/actions/workflows/release-compile-changelog.yml)**: enter the release main version (`x.y`) as _Version_ and leave _Release date_ empty, except when building the package ahead of schedule.
- [ ] Review and merge the PRs that were generated: one against `trunk` and another one against the release branch. Both should be under the release milestone.
- [ ] Run workflow **[Release: Build ZIP file](https://github.com/woocommerce/woocommerce/actions/workflows/release-build-zip-file.yml)** to build the asset and create the GitHub release: enter the release branch as _Release branch_ and check _Create GitHub release_.
- [ ] Confirm that a draft release [was created in the repository](https://github.com/woocommerce/woocommerce/releases) with an attached `woocommerce.zip` asset.

#### 3. Upload the release to WordPress.org

- [ ] Run workflow **[Release: Upload release to WordPress.org](https://github.com/woocommerce/woocommerce/actions/workflows/release-upload-to-wporg.yml)**: enter the release version (`x.y.z`) as _Release tag to upload_ and make sure to check off the confirmation box.
- [ ] Confirm that SVN tag [exists on WordPress.org SVN](https://plugins.svn.wordpress.org/woocommerce/tags/).
- [ ] [Log into WordPress.org](https://wordpress.org/plugins/developers/releases/) using the credentials from the `WordPress.org "WooCommerce" user account` secret in the secret store and approve the release.
- [ ] After a few minutes, confirm that the release package [is available for download](https://wordpress.org/plugins/woocommerce/advanced/).

#### 4. Deploy to the staging environment

:::caution
This step only applies to `rc` or stable (`x.y.0`  onwards) releases.
:::

- [ ] Follow the [guide to deploy to the staging environment](https://wp.me/PCYsg-18BQ) and monitor for 4 hours (RC) or 2 hours (stable) after deploy.

##### If a critical issue was detected while monitoring

- [ ] Request a revert in the staging environment.
- [ ] Pause the release process and **do not continue with any steps on this issue**. Follow the procedure in the [troubleshooting guide](https://developer.woocommerce.com/docs/contribution/releases/troubleshooting/#deploy-serious-bug) instead.

#### 5. Publish the release

- [ ] **(Only for stable releases)** Run workflow **[Release: Update stable tag](https://github.com/woocommerce/woocommerce/actions/workflows/release-update-stable-tag.yml)**: enter the release version (`x.y.z`) as _Version_ and make sure to check off the confirmation box.
- [ ] Publish the [release draft](https://github.com/woocommerce/woocommerce/releases) that was previously created, as well as any other release drafts that might exist from previous attempts. **Ensure** that "Set as the latest release" is checked **only** for stable releases.

#### 6. Post-release tasks

:::caution
This step only applies to `rc` or stable (`x.y.0`  onwards) releases.
:::

- [ ] Wait at least 1 hour for all automations to complete and make sure to merge any follow-up PRs under the [release milestone](https://github.com/woocommerce/woocommerce/milestones/).
- [ ] Continue monitoring for bugs related to the release for at least 3 days. See the [release monitoring guide](https://developer.woocommerce.com/docs/contribution/releases/monitoring/) for more details.
