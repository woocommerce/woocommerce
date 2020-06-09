# Releases

This document outlines the process of releasing new versions of the blocks plugin.

## Prerequisites - what you need to release WooCommerce Blocks

-   You should be set up for development - for more info see [this doc](../contributors/getting-started.md).
-   Install & set up [GitHub hub](https://hub.github.com) tools.
-   Configure a GitHub token for release scripts to use.
    -   https://github.com/settings/tokens
    -   Select the following permissions:
        -   `admin:org`
        -   `public_repo`
        -   `read:user`
    -   Ensure your token is available to scripts, e.g. `export GH_API_TOKEN={YOUR-TOKEN}` or similar.
-   Get WPORG plugin svn commit access - ask a team member.

_Outcome_: **You are equipped to ship a release!**

## Release process

### Lead-up to release

#### Ensure release development is complete

-   Github milestones are used for all releases. We do releases every two weeks from the latest release milestone - [example](https://github.com/woocommerce/woocommerce-gutenberg-products-block/milestone/43)
-   If a patch release is needed, then the milestone for the patch release is created and a decision will be made whether it just includes `cherry-picked` changes, or all the changes currently slated from the current release milestone. If the latter, then all the issues and pulls in the current release milestone are moved to the patch release, and the the release is built from the patch release branch rebased on master. In **most cases**, patch releases will involve cherry-picking.
-   Ensure all issues/PRs intended for this release are merged, closed and linked to release.
-   All PRs should have changelog entry, or `skip-changelog` tag.
-   Check with the team to confirm any outstanding or in progress work.

Note: changelog should be formatted like this in PR description. Note the preceding `>` - this is required by changelog script.

```md
### Changelog

> bug: Fix bug in Safari and other Webkit browsers that was causing the All Products block to show 0 results when resetting the sort value.
```

_Outcome_: **Team is aware of release and in agreement about what fixes & features are included.**

#### Ensure release branch includes all relevant fixes

-   Make release branch (if needed).
    -   For _major_ and _minor_ releases, create branch: `release/X.X`.
    -   For _patch_ releases, the branch should already exist.

#### Create a release pull request

Using the [release pull request template](../../.github/release_pull_request_template.md), create a pull request for the release branch you just created. This pull request will have changes merged to master but might not be a straight merge if it contains cherry-picked commits. The pull request also contains the checklist to go over as a part of the release along with being a place to contain all planning/communication around the release. The checklist should be completed and the pull request has an approved review from at least one team member before you do the Github deploy or release the plugin to WordPress.org.

### Patch releases against latest master

If it's determined a patch release will include the latest master:

- Ensure your local checkout is updated to the tip of the release branch.
- Rebase the release branch for the patch release against master.
- Move all closed issues and pulls from the current release milestone into the patch release milestone.
- Push the release branch to origin (so changes are in GitHub repo).

### Patch releases with cherry-picking.

This is for releases where just fixes specific to the branch are released and not the latest changes in master.

- Ensure your local checkout is updated to the tip of the release branch.
- Cherry pick relevant PRs into the release branch:
-   If PR is already labelled `status: cherry-picked 🍒` then continue to next PR.
-   Ideally, use GitHub Hub to cherry pick the PR - `hub cherry-pick {PR-COMMIT-URL}`.
-   If there are serious conflicts or extensive differences between `master` and release branch, you may need to take more care:
    -   Manually cherry pick individual commits using git - `git cherry-pick {COMMIT-HASH}`.
    -   Or in some cases, manually craft a new PR with appropriate changes, targeting release branch.
-   Push the release branch to origin (so changes are in GitHub repo).
-   Label the PR: `status: cherry-picked 🍒`.

### Minor/Major releases

- Ensure your local checkout is updated to the tip of the release branch.


_Outcome_: **Release branch has all relevant changes merged & pushed and there is a corresponding release pull request created for the release.**

### Prepare release

#### Ensure release branch readme is up to date

-   Run changelog script `$ npm run changelog` to get changelog txt for readme. Changelog content will be output to screen by script.
    -   The above script will automatically generate changelog entries from a milestone (you will be asked about the milestone name in the script).
-   Add changelog section for release, e.g. [`= 2.5.11 - 2020-01-20 =`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/commit/74a41881bfa456a2167a52aaeb4871352255e328).
-   Copy-paste the changelog content into `readme.txt`.
-   Make any other changes to readme as needed - e.g. support versions changing, new blocks.
-   Push readme changes to release branch on origin repo.
    -   Note: you can push your readme changes directly to branch – no need for a PR & review process.
-   Create testing notes for the release (you might be able to copy some from the pulls included in the release). Add the notes to [`docs/testing/releases`](../testing/releases/) (and update the [README.md index](../testing/releases/README.md) there)

_Outcome_: **Release branch has `readme.txt` is updated with release details.**

#### Build zip & smoke test

-   Ensure you are on the tip of the release branch, e.g. `git pull origin release/2.5`
-   Update dependencies – `$ npm ci`.
-   Run a production build - `$ npm run build`.
-   Run package script to get a zip to test `$ npm run package-plugin`.
-   Smoke test built release zip:
    -   At least one other person should test the built zip - ask a teammate to help out.
    -   Test in a clean environment, e.g. Jurassic.Ninja site.
    -   Test existing WooCommerce Blocks content works correctly after update (no block validation errors).
    -   Test to confirm blocks are available and work correctly in oldest supported WordPress version (e.g. 5.0).
    -   Confidence check - check blocks are available and function.
    -   Test to confirm new features/fixes are working correctly.
    -   Smoke test – test a cross section of core functionality.
    -   Tests performed should be recorded and listed in the release pull request.
-   Ask a team member to review the changes in the release pull request and for anyone who has done testing that they approve the pull request.

_Outcome_: **Confident that source code is ready for release: intended fixes are working correctly, no release blockers or build issues.**

### Release!

#### Tag release on GitHub

-   Prepare tagged release on github `$ npm run deploy`.
    -   Note: the script automatically updates version numbers (commits on your behalf).
-   Edit release, add changelog info to Github release notes.
-   Check release repo tag is correct - checkout, smoke test/confidence check.

_Outcomes_: **Version numbers updated in source code & developers can test tagged release.**

#### Release to WPORG

-   Run `$ npm run release`. This script clones a copy of the source code to your home folder, and outputs an `svn` command to push release up to WPORG.
-   Push release to WPORG using `svn`.
    -   Run generated svn command to commit to WPORG svn repo.
        -   The command should look like this: `cd /Users/{YOU}/blocks-deployment/woo-gutenberg-products-block-svn && svn ci -m "Release 2.5.11, see readme.txt for changelog."`
    -   Commit should complete successfully with a message like `Committed revision 2231217.`.
-   Confirm that the WPORG release is updated and correct:
    -   Changelog, `Version` & `Last updated` on [WPORG plugin page](https://wordpress.org/plugins/woo-gutenberg-products-block/).
    -   Confirm svn tag is correct, e.g. [2.5.11](https://plugins.svn.wordpress.org/woo-gutenberg-products-block/tags/2.5.11/).
    -   Confirm [WooCommerce.com plugin page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/) is updated.
    -   Download zip and smoke test.
    -   Test updating plugin from previous version.

_Outcome_: **Customers can install/update via WPORG; WPORG plugin page is up to date**.

### After release

#### Update `master` with release changes

-   Merge the release branch back into master (without the branch being up to date with master). This may have merge conflicts needing resolved if there are cherry-picked commits in the release branch.
-   Do not delete the branch (release branches are kept open for potential patch releases for that version)
-   For _major_ & _minor_ releases, update version on master with dev suffix, e.g. [`2.6-dev`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/commit/e27f053e7be0bf7c1d376f5bdb9d9999190ce158).

#### Clean up release milestone / Zenhub

-   Edit the milestone and add the current date as the due date (this basically is used for easy reference of when the milestone was completed).
-   Close the milestone.
-   If you didn't release a patch release, create a milestone for the next minor release.
-   Close any epics that are completed as of this release and remove any unfinished issues in that from the epic.

#### Create pull request for updating the package in WooCommerce core.

If the tagged release should be updated in WooCommerce core, do this immediately following our release.

- Create the pull request in the [WooCommerce Core Repository](https://github.com/woocommerce/woocommerce/) that [bumps the package version](https://github.com/woocommerce/woocommerce/blob/master/composer.json) for the blocks package to the version being pulled in.
- Copy the release pull request notes for that tag (and merge any notes from previous tags if you're bumping up from non consecutive versions) into the pull request description.
- Run through the testing checklist to ensure everything works in that branch for that package bump. **Note:** Testing should include ensuring any features/new blocks that are supposed to be behind feature gating for the core merge of this package update are working as expected.
- Verify and make any additional edits to the pull request description for things like: Changelog to be included with WooCommerce core, additional communication that might be needed elsewhere, additional marketing communication notes that may be needed etc.
- After the checklist is complete and the testing is done, it will be up to the WooCommerce core team to approve and merge the pull request.

_Outcome:_ The package is updated in WooCommerce core frequently and successfully merged to WooCommerce master as a stable release.

## Appendix: Versions

We have _major_, _minor_ and _patch_ releases.

For example:

-   version == 2.5.11
    -   2 == _major_ version; has breaking changes or major new features
    -   5 == _minor_ version; has new features
    -   11 == _patch_, aka point / revision / fix; has bug fixes and improvements to existing features
-   2.0 is a _major_ release
-   2.5 is a _minor_ release
-   2.5.11 is a _patch_ release

There are some differences to our release process for each kind of release - these are detailed in the steps above.

## Appendix: updating a specific file on WPORG

Sometimes, we need to update a single file in WordPress.org without wanting to do a full release, for example, updating the `readme.txt` versions or descriptions. In order to do that, refer to the _[Editing Existing Files](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#editing-existing-files)_ section of the Subversion guide in developer.wordpress.org or follow these steps:

1. Checkout the plugin repo:

```
$ svn co "http://plugins.svn.wordpress.org/woo-gutenberg-products-block/"
$ cd woo-gutenberg-products-block
```

2. Modify the files you want to change in `trunk` or `tags/x.y.z`.

3. Check your changes with:

```
$ svn stat
$ svn diff
```

4. Commit the changes to the server:

```
$ svn ci -m "Updated readme.txt description"
```
