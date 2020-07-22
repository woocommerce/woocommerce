# Handling Releases

This document (and the [related PR template](../../.github/release_pull_request_template.md)) outlines the process of releasing new versions of the blocks plugin.

## Prerequisites - what you need to release WooCommerce Blocks

-   You should be set up for development - for more info see [this doc](../contributors/getting-started.md).
-   Install & set up [GitHub hub](https://hub.github.com) tools.
-   You will need `svn` installed for pushing to WPORG.
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
-   If a patch release is needed, then the milestone for the patch release is created and a decision will be made whether it just includes `cherry-picked` changes, or all the changes currently slated from the current release milestone. If the latter, then all the issues and pulls in the current release milestone are moved to the patch release, and the the release is built from the patch release branch rebased on `main`. In **most cases**, patch releases will involve cherry-picking.
-   Ensure all issues/PRs intended for this release are merged, closed and linked to release.
-   All PRs should have changelog entry, or `skip-changelog` tag.
-   Check with the team to confirm any outstanding or in progress work.
-   Review recent [dependency updates](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pulls?q=is%3Apr+is%3Aclosed+author%3Aapp%2Frenovate) to be included in this release, and ensure they have been adequately tested.

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

_Outcome_: __There is a branch on origin repo for the release.__ (Note - it may not have all changes on it yet - that's next.)

#### Create a release pull request

Using the [release pull request template](../../.github/release_pull_request_template.md), create a pull request for the release branch. This contains a checklist to go over, and serves as a place to contain all planning/communication around the release. The release process can span a few days, so this PR is an essential way for the team to keep track.

You'll work through the checklist over the rest of the process below. The PR will remain open and unmerged until the release is live - you'll merge it much later (see `After release` below).

##### Patch releases against latest main branch

If it's determined a patch release will include the latest main branch:

- Ensure your local checkout is updated to the tip of the release branch.
- Rebase the release branch for the patch release against `main`.
- Move all closed issues and pulls from the current release milestone into the patch release milestone.
- Push the release branch to origin (so changes are in GitHub repo).

##### Patch releases with cherry-picking.

This is for releases where just fixes specific to the branch are released and not the latest changes in `main`.

- Ensure your local checkout is updated to the tip of the release branch.
- Cherry pick relevant PRs into the release branch:
-   If PR is already labelled `status: cherry-picked 🍒` then continue to next PR.
-   Ideally, use GitHub Hub to cherry pick the PR - `hub cherry-pick {PR-COMMIT-URL}`.
-   If there are serious conflicts or extensive differences between `main` and release branch, you may need to take more care:
    -   Manually cherry pick individual commits using git - `git cherry-pick {COMMIT-HASH}`.
    -   Or in some cases, manually craft a new PR with appropriate changes, targeting release branch.
-   Push the release branch to origin (so changes are in GitHub repo).
-   Label the PR: `status: cherry-picked 🍒`.

##### Minor/Major releases

- Ensure your local checkout is updated to the tip of the release branch.

_Outcome_: **Release branch has all relevant changes merged & pushed and there is a corresponding release pull request created for the release.**

### Prepare release

#### Ensure release branch readme is up to date

-   Run changelog script `$ npm run changelog` to get changelog txt for readme. Changelog content will be output to screen by script.
    -   The above script will automatically generate changelog entries from a milestone (you will be asked about the milestone name in the script).
-   Add changelog section for release, e.g. [`= 2.5.11 - 2020-01-20 =`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/commit/74a41881bfa456a2167a52aaeb4871352255e328).
-   Copy-paste the changelog content into `readme.txt`.
-   Make any other changes to metadata as needed:
    -   `readme.txt` - support versions changing, add any new blocks.
    -   `woocommerce-gutenberg-products-block.php` – requirements/tested up to versions etc.
    -   Note: no need to edit plugin version number - this happens automatically later (`npm run deploy`).
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

### Complete pull request checklist

Now's your last chance to tick off everything in the PR checklist! These are all important to consider. If something is not applicable, add info to the description. Otherwise provide info and relevant details for each item. Any questions - ping your team in slack :)

Note - the PR is not merged here either - read on :)

### Release!

#### Tag release on GitHub (and optionally deploy to WPORG)

-   Prepare tagged release on github `$ npm run deploy`.
    -   Note: the script automatically updates version numbers (commits on your behalf).
    -   **ALERT**: This script will ask you if this release will be deployed to WordPress.org. If you answer yes, then a GitHub release is created and this will trigger a GitHub workflow we have setup to deploy the contents of the release tag to GitHub. If you answer no, then a tag only is created and nothing will be deployed to GitHub. Note: Pre-releases GitHub releases ARE deployed to WordPress.org but the deploy script will not update the stable version in readme.txt (so pre-releases are only available in the svn tags).

**If GitHub release was created:**

-   Edit release, add changelog info to Github release notes.
-   Check GitHub actions tab and verify wp.org deploy workflow was successful.
-   After wp.org deploy GitHub workflow completes, confirm that the WPORG release is updated and correct:
    -   Changelog, `Version` & `Last updated` on [WPORG plugin page](https://wordpress.org/plugins/woo-gutenberg-products-block/).
    -   Confirm svn tag is correct, e.g. [2.5.11](https://plugins.svn.wordpress.org/woo-gutenberg-products-block/tags/2.5.11/).
    -   Confirm [WooCommerce.com plugin page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/) is updated.
    -   Download zip and smoke test.
    -   Test updating plugin from previous version.

_Outcome_: **Customers can install/update via WPORG; WPORG plugin page is up to date**.

**If GitHub tag only was created:**

- Checkout the tag and smoke test to verify everything works as expected.

### After release

#### Update `main` branch with release changes

-   Merge the release branch back into `main` (without the branch being up to date with `main`). This may have merge conflicts needing resolved if there are cherry-picked commits in the release branch.
-   Restore the branch if it is deleted. Release branches are kept open for potential patch releases for that version.
-   For _major_ & _minor_ releases, update version on `main` with dev suffix, e.g. [`2.6-dev`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/commit/e27f053e7be0bf7c1d376f5bdb9d9999190ce158).

_Outcome:_ __Main branch contains all changes and metadata tweaks for the release.__

_Outcome:_ __Release branch is available for subsequent patch/bug fix releases.__

_Outcome:_ __Dev version number on new builds of `main` branch.__

#### Clean up release milestone / Zenhub

-   Edit the (GitHub) milestone and add the current date as the due date (this basically is used for easy reference of when the milestone was completed).
-   Close the milestone.
-   If you didn't release a patch release, create a milestone for the next minor release.
-   Close any epics that are completed as of this release and remove any unfinished issues in that from the epic.

_Outcome:_ __The release milestone is archived and is an accurate record of what was included.__

_Outcome:_ __There is a new release milestone ready for the next iteration.__

#### Create pull request for updating the package in WooCommerce core.

All releases (except RCs, betas etc) should be included in WooCommerce core. We do this by adding a PR on WooCommerce Core repo immediately after our release is completed.

- Create the pull request in the [WooCommerce Core Repository](https://github.com/woocommerce/woocommerce/) that [bumps the package version](https://github.com/woocommerce/woocommerce/blob/master/composer.json) for the blocks package to the version being pulled in.
- Copy the release pull request notes for that tag (and merge any notes from previous tags if you're bumping up from non consecutive versions) into the pull request description.
- Run through the testing checklist to ensure everything works in that branch for that package bump. **Note:** Testing should include ensuring any features/new blocks that are supposed to be behind feature gating for the core merge of this package update are working as expected.
- Verify and make any additional edits to the pull request description for things like: Changelog to be included with WooCommerce core, additional communication that might be needed elsewhere, additional marketing communication notes that may be needed etc.
- After the checklist is complete and the testing is done, it will be up to the WooCommerce core team to approve and merge the pull request.

_Outcome:_ __The package is updated in WooCommerce core frequently and successfully merged to WooCommerce main branch as a stable release.__

#### Post release announcement on [WooCommerce Developer Blog](https://woocommerce.wordpress.com/category/blocks/)

Use previous posts for inspiration. If the release contains new features, or API changes, explain what's new so Woo devs/builders/merchants can get excited about it. This post can take time to get right - get feedback from the team, and don't rush it :)

- Ensure the release notes are included in the post verbatim.
- Don't forget to use category `Blocks` for the post.

_Outcome:_ __There's a public release announcement, with clear info about what's new, roughly within a day of actual release.__

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
