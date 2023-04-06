# Release Steps

The release pull request has been created! This checklist is a guide to follow for the remainder of the release process. You can check off each item in this list once completed.

-   [ ] Checkout the release branch locally.

## Initial Preparation

-   [ ] Close the milestone of the release you are going to ship. That will prevent newly approved PRs to be automatically assigned to that milestone.
-   [ ] Create a milestone for the next version.
-   [ ] Manually add the changelog entries of all affected PRs to `readme.txt`. (Technically, this should be an automated process, but it seems to broke recently. Please change this entry back, once the automated process works again.)
-   [ ] Ensure you pull your changes from the remote, since GitHub Actions will have added new commits to the branch.
    -   [ ] Check the version and date in the changelog section within `readme.txt`, e.g. `= {{version}} - YYYY-MM-DD =`
    -   [ ] Check the changelog matches the one in the pull request description above.
-   [ ] Run `npm run change-versions` to update the version numbers in several files. Write the version number you are releasing: {{version}}.
-   [ ] Update compatibility sections (if applicable).
    -   [ ] Update _Requires at least_, _Tested up to_, and _Requires PHP_ sections at the top of `readme.txt`. Note, this should also be the latest WordPress version available at time of release.
    -   [ ] Update _Requires at least_, _Requires PHP_, _WC requires at least_, and _WC tested up to_ at the top of `woocommerce-gutenberg-products-block.php`. Note, this should include requiring the latest WP version at the time of release. For _WC requires at least_, use L1 (we publicly communicate L0 but technically support L1 to provide some space for folks to update). So this means if the current version of WooCommerce core is 5.8.0, then you'll want to put 5.7.0 here.
    -   [ ] If necessary, update the value of `$minimum_wp_version` at the top of the `woocommerce-gutenberg-products-block.php` file to the latest available version of WordPress.
    -   [ ] Check the minimum WP version supported by **WooCommerce Core** (you can find it in [its readme.txt - line `Requires at least`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/readme.txt#L4)). If necessary, update it in `phpcs.xml`. It would be this line: `<config name="minimum_supported_wp_version" value="5.6" />`.
-   [ ] Push above changes to the release branch.

## Create the Testing Notes

-   [ ] Run `npm ci`
-   [ ] Run `npm run package-plugin:deploy`. This will create a zip of the current branch build locally.
    -   Note: The zip file is functionally equivalent to what gets released except the version bump.
-   [ ] Create the testing notes for the release.
    -   [ ] For each pull request that belongs to the current release, grab the `User Facing Testing` notes from the PR's description. Be sure that the `Do not include in the Testing Notes is not flagged` checkbox is unchecked.
    -   [ ] Add the notes to `docs/internal-developers/testing/releases`
    -   [ ] Update the `docs/internal-developers/testing/releases/README.md` file index.
-   [ ] Copy a link to the release zip you created earlier into the testing notes. To generate the link you can upload the zip as an attachment in a GitHub comment and then just copy the path (without publishing the comment).
-   [ ] Commit and push the testing docs to the release branch.
-   [ ] Smoke test built release zip using the testing instructions you created:
    -   Test in a clean environment, e.g. Jurassic.Ninja site.
    -   Test existing WooCommerce Blocks content works correctly after update (no block validation errors).
    -   Test to confirm blocks are available and work correctly in oldest supported WordPress version (e.g. 5.3).
    -   Confidence check - check blocks are available and function.
    -   Test to confirm new features/fixes are working correctly.
    -   Test any UI changes in mobile and desktop views.
    -   Smoke test – test a cross section of core functionality.
-   [ ] Ask the porters of Rubik and Kirigami to test the built zip as well and to approve the PR if everything looks good.

Each porter is responsible for testing the PRs that fall under the focus of their own team. Shared functionality should be tested by both porters. This means that the Rubik porter will mostly test checkout blocks and Store API endpoints, while the Kirigami porter will test the product related blocks and Store API endpoints.

-   If all PRs are testing as expected, continue with the release.
-   If one or more PRs are not testing as expected: ping the PR authors and the porter of the relevant team and ask them if the change is a release blocker or not (you can also ping the team lead if any of them is not available). In general, if it's not a regression or there is no product/marketing reason why that PR is a blocker, all other PRs should default to not being blockers.
    -   If there are blockers: stop the release and ask the PR author to fix them. If the PR author is AFK, ping the porter of their team.
    -   If some PRs are not testing as expected but they are not blockers: revert the relevant commits, remove the changes from testing steps and changelog, open an issue (or reopen the original one) and proceed with the release.
    -   If minor issues are discovered during the testing, each team is responsible for logging them in Github.

## Ensure hub is set up and you're authenticated

-   [ ] Make sure you've got `hub` installed (`brew install hub`)
-   [ ] Make sure `hub api user` returns JSON with information about your GitHub user account, if it doesn't:
    -   [ ] Create a [GitHub access token](https://github.com/settings/tokens) with the `repo` permission.
    -   [ ] Set the environment variables: `GITHUB_USERNAME` with your GitHub Username, and `GITHUB_TOKEN` with the token you just generated. (You may want to add these to `.bashrc` or the equivalent)
    -   [ ] Run `hub api user` again and ensure JSON with information about your GitHub user account is returned.

## Push the button - Deploy

-   [ ] Execute `npm run deploy`
    -   The script will ask you to enter the version number to tag. Please enter the version we're releasing right now. Do not publish any dev tags as a release.
    -   **ALERT**: This script will ask you if this release will be deployed to WordPress.org. You should answer yes for this release even if it is a pre-release.
    -   A GitHub release will automatically be created and this will trigger a workflow that automatically deploys the plugin to WordPress.org.

## If this release is deployed to WordPress.org

-   [ ] An email confirmation is required before the new version will be released, so check your email in order to confirm the release.
-   [ ] Edit the [GitHub release](https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases) and copy changelog into the release notes. Ensure there is a release with the correct version, the one you entered above.
-   [ ] The `#woo-blocks-repo` slack instance will be notified about the progress with the WordPress.org deploy. Watch for that. If anything goes wrong, an error will be reported and you can followup via the GitHub actions tab and the log for that workflow.
-   [ ] After the wp.org workflow completes, confirm the following
    -   [ ] Confirm svn tag is correct, e.g. [{{version}}](https://plugins.svn.wordpress.org/woo-gutenberg-products-block/tags/{{version}}/)
    -   [ ] Changelog, Version, and Last Updated on [WP.org plugin page](https://wordpress.org/plugins/woo-gutenberg-products-block/) is correct.
    -   [ ] Confirm [WooCommerce.com plugin page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/) is updated. Note: this can take several hours, feel free to check it the following day.
    -   [ ] Download zip and smoke test.
    -   [ ] Test updating plugin from previous version.

## After Workflow completes

-   [ ] Port to `trunk` the changes to the changelog, testing steps and required versions that you did in the previous steps. You can do so copy-and-pasting the changes in a new commit directly to `trunk`, or cherry-picking the commits that introduced those changes.
-   [ ] Run `npm run change-versions` to update the version in `trunk` to the next version of the plugin and include the `dev` suffix. For example, if you released 2.5.0, you should update the version in `trunk` to 2.6.0-dev.
-   [ ] Update the schedules p2 with the shipped date for the release (PdToLP-K-p2).
-   [ ] Edit the GitHub milestone of the release you just shipped and add the current date as the due date (this is used to track ship date as well).

## Pull request in WooCommerce Core for Package update

This only needs to be done if this release is the last release of the feature plugin before code freeze in the WooCommerce core cycle. If this condition doesn't exist you can skip this section.

-   [ ] Remind whoever is porter this week to audit our codebase to ensure this [experimental interface document](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/blocks/feature-flags-and-experimental-interfaces.md) is up to date. See Pca54o-rM-p2 for more details.
-   [ ] Create a pull request for updating the package in the [WooCommerce Core Repository](https://github.com/woocommerce/woocommerce/) that [bumps the package version](https://github.com/woocommerce/woocommerce/blob/747cb6b7184ba9fdc875ab104da5839cfda8b4be/plugins/woocommerce/composer.json) for the Woo Blocks package to the version you are releasing. Reviewing and merging the PR is your team's responsibility.

    -   The content for the pull release can follow [this example](https://github.com/woocommerce/woocommerce/pull/32627).

        -   [ ] Increase the version of `woocommerce/woocommerce-blocks` in the `plugins/woocommerce/composer.json` file
        -   [ ] Run `composer update woocommerce/woocommerce-blocks` and make sure `composer-lock.json` was updated
        -   [ ] Run `pnpm --filter=woocommerce changelog add` to create a new changelog file similar to this one [plugins/woocommerce/changelog/update-woocommerce-blocks-7.4.1](https://github.com/woocommerce/woocommerce/blob/5040a10d01896bcf40fd0ac538f2b7bc584ffe0a/plugins/woocommerce/changelog/update-woocommerce-blocks-7.4.1). The file will be auto-generated with your answers. For the _Significance_ entry we’ll always use `minor` for WC Blocks major releases:

            ```md
            Significance: minor
            Type: update

            Update WooCommerce Blocks to 7.4.0
            ```

    -   The PR description can follow [this example](https://github.com/woocommerce/woocommerce/pull/32627).
        -   It lists all the WooCommerce Blocks versions that are being included since the last version that you edited in `plugins/woocommerce/composer.json`. Each version should have a link for the `Release PR`, `Testing instructions` and `Release post` (if available).
        -   The changelog should be aggregated from all the releases included in the package bump and grouped per type: `Enhancements`, `Bug Fixes`, `Various` etc. This changelog will be used in the release notes for the WooCommerce release. That's why it should only list the PRs that have WooCoomerce Core in the WooCommerce Visibility section of their description. Don't include changes available in the feature plugin or development builds.


-   [ ] Build WC core from that branch with `pnpm run --filter='woocommerce' build ` (you might need to [install the dependencies first](https://github.com/woocommerce/woocommerce#prerequisites)) and:

    -   [ ] Make sure the correct version of WC Blocks is being loaded. This can be done testing at least one of the testing steps from the release.
    -   [ ] Complete the [Smoke testing checklist](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/internal-developers/testing/smoke-testing.md).

-   [ ] Verify and make any additional edits to the pull request description for things like: Changelog to be included with WooCommerce core, additional communication that might be needed elsewhere, additional marketing communication notes that may be needed, etc.
    -   [ ] Assign the corresponding WC version milestone to the PR
    -   [ ] After the checklist is complete and the testing is done, select the porter of your team to review the PR. Once approved, make sure you merge the PR.

-   [ ] Make sure you join the `#woo-core-releases` Slack channel to represent Woo Blocks for the release of WooCommerce core this version is included in.
-   [ ] Search the release thread of the WooCommerce core version in WooCommerce P2 (example: p6q8Tx-2gl-p2).
    -   [ ] Subscribe to it, so you are aware of any news/changes.
    -   [ ] Make sure you are listed as the _Blocks Package_ lead or add yourself if you aren't.

## Publish posts

-   [ ] Post release announcement on [WooCommerce Developer Blog](https://developer.woocommerce.com/category/release-post/woocommerce-blocks-release-notes/).
    -   Ping porters from each team to know which changelog entries need to be highlighted. Ask them to write a short text and optionally provide a screenshot. They can use previous posts for inspiration, we usually try to highlight new features or API changes.
    -   Ensure the release notes are included in the post verbatim.
    -   Don't forget to use category `WooCommerce Blocks Release Notes` for the post.
    -   If any of the PRs in this release is labelled with `needs dev-note`, include it in the post.
-   [ ] Add highlights to the WC core release post (do this even if the release you are doing is not merged into WC core):
    -   Check which WC core version will include the WC Blocks release you just did (reference: PdToLP-K-p2).
    -   Go to its Release Thread and search the _Feature Highlights_ comment (example: p6q8Tx-2gl-p2).
    -   Edit the linked draft post and add all highlights from the release you just did.
    -   Leave a comment under the _Feature Highlights_ comment in the release thread mentioning that you updated the draft with the features included in WC Blocks X.Y.
-   [ ] Announce the release internally (`#woo-announcements` slack).
-   [ ] Update user-facing documentation as needed. When the plugin is released, ensure user-facing documentation is kept up to date with new blocks and compatibility information. The dev team should update documents in collaboration with support team and WooCommerce docs guild. In particular, please review and update as needed:
    -   Are there any new blocks in this release? Ensure they have adequate user documentation.
    -   Ensure any major improvements or changes are documented.
-   [ ] Update minimum supported versions (WordPress, WooCommerce Core) and other requirements where necessary, including:
    -   [WCCOM product page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/)
    -   [WooCommerce blocks main documentation page](https://docs.woocommerce.com/document/woocommerce-blocks/)
-   [ ] Go through the description of the release pull request and edit it to update all the sections and checklist instructions there.
-   [ ] Close this PR.
