---
post_title: Building and Publishing a Release
sidebar_label: Building and Publishing
---

# Building and Publishing a Release

## Prerequisites

* You should have a WordPress.org account with committer access to the WooCommerce plugin, as well as the associated SVN credentials.

## Pre-Checks

1. Verify there are no open Pull Requests or Issues for the milestone matching the release being published.
   * It is important that all pull requests tied to the release milestone have been closed. This includes [backported pull requests](/docs/contribution/releases/backporting) that may need to be merged into other release branches or trunk to avoid regressions.
2. Check that there are no [Pull Requests labeled “cherry pick failed”](https://github.com/woocommerce/woocommerce/pulls?q=is:pr+label:%22cherry+pick+failed%22) for which the failure wasn’t expected or manually resolved via another PR.
3. Ensure the Stable tag: value in the readme.txt on the release branch is exactly the same as on WordPress.org ([link](https://plugins.trac.wordpress.org/browser/woocommerce/trunk/readme.txt#L7)). If it’s not the case, update it by submitting and merging a PR against the release branch.
   * Keep in mind that this should not be the version you’re building but the one before, as the stable tag will be updated later in the process.

## Building WooCommerce

1. Run the [“Release: Compile changelog” workflow”](https://github.com/woocommerce/woocommerce/actions/workflows/release-compile-changelog.yml) to generate the changelog.
   1. Run from the workflow from trunk and enter the major version number.
   2. Once the workflow finishes, it should’ve created 2 new PRs:
      * One against trunk removing processed entries.
      * One against the release branch, both removing processed entries and updating the readme.txt with the updated changelog.
   3. Review and merge the PRs, updating if necessary.
      * Ensure that the date in the changelog is also correct.
2. (Skip for `-rc.1`) Run the version-bump workflow to update the WooCommerce version in relevant files on the release branch ahead of the release:
   * **PENDING WOOPLUG-4238**
   * This command will give you a link to a PR. Review and merge the PR once CI passes.
3. Build the release ZIP file by running the [“Release: Build ZIP file” workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-build-zip-file.yml).
   1. Run the workflow from the release branch and set "Create a draft GitHub release" to `true`.
   2. Once the workflow is complete, the run will create a [draft release tag](https://github.com/woocommerce/woocommerce/releases) with an attached woocommerce.zip file.

## Publishing the Release

1. Run the ["Release: Upload release to WordPress.org" workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-upload-to-wporg.yml) to publish the release on WordPress.org
   * Run the workflow from `trunk` and enter the tag of the release that was created above, e.g. 10.0.1.
   * This step will create a new tag in SVN with the new release version and, if this release is a newer release than the current version in trunk, it will overwrite trunk. For example, if the latest trunk version is 10.1.0 and release 10.0.4 is published, it will not overwrite trunk. 
2. When the workflow is complete, visit [https://wordpress.org/plugins/developers/releases/](https://wordpress.org/plugins/developers/releases/) and approve the release. It will take a few minutes for WordPress.org to build teh new version.
3. Validate that the new release is available:
   * https://plugins.svn.wordpress.org/woocommerce/tags/ should list the version number.
   * The new version should also be available inside the Previous versions dropdown [inside the Advanced Options screen on the WordPress.org plugin page](https://wordpress.org/plugins/woocommerce/advanced/).
4. If this is not an `RC` release, [Deploy the release to our staging environments](#tbd) and monitor for any issues.
5. Publish the previously created GitHub [draft release tag](https://github.com/woocommerce/woocommerce/releases).
   * Check off Set as a pre-release if you’re releasing an RC. Otherwise, check off Set as the latest release.
6. Ping Dev Advocacy in the #woo-core-releases channel in Slack notifying them of the release and asking them to update or publish the release post. Provide links to both the GitHub tag as well as the ZIP from WordPress.org as part of your message.
7. Ping #woo-announcements in Slack with a link to the release post ([example](https://a8c.slack.com/archives/C0741730R/p1750099929478409)).
