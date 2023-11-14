# Patch release steps

The release pull request has been created! This checklist is a guide to follow for the remainder of the patch release process. You can check off each item in this list once completed.

-   [ ] Checkout the release branch locally.

## Initial Preparation

-   [ ] Close the milestone of the release you are going to ship. That will prevent newly approved PRs to be automatically assigned to that milestone.
-   [ ] Check that the release automation correctly added the changelog to `readme.txt`
-   [ ] Ensure you pull your changes from the remote, since GitHub Actions will have added new commits to the branch.
    -   [ ] Check the version and date in the changelog section within `readme.txt`, e.g. `= {{version}} - YYYY-MM-DD =`
    -   [ ] Check the changelog matches the one in the pull request description above.
-   [ ] Run `npm run change-versions` to update the version numbers in several files. Write the version number you are releasing: {{version}}.
-   [ ] Update compatibility sections (if applicable).
-   [ ] Cherry-pick into the release branch all fixes that need to be included in this release (assuming they were merged into `trunk`).
-   [ ] Push above changes to the release branch.

## Create the Testing Notes

-   [ ] Run `npm ci`
-   [ ] Run `npm run package-plugin:deploy`. This will create a zip of the current branch build locally.
    -   Note: The zip file is functionally equivalent to what gets released except the version bump.
-   [ ] Create the testing notes for the release.
    -   [ ] For each pull request that belongs to the current release, grab the `User Facing Testing` notes from the PR's description.
        -   If a PR has the `Should be tested by the development team exclusively` checkbox checked, create a new section called 'Testing notes for the development team' and copy the `User Facing Testing` notes from the PR to this section.
        -   If a PR has the `Experimental` checkbox checked, do not include it in the testing instructions.
        -   If a PR has the `Do not include in the Testing Notes` checkbox checked, as the description suggests, do not include it in the release instructions.
    -   [ ] Add the notes to `docs/internal-developers/testing/releases`
    -   [ ] Update the `docs/internal-developers/testing/releases/README.md` file index.
-   [ ] Copy a link to the release zip you created earlier into the testing notes. To generate the link you can upload the zip as an attachment in a GitHub comment and then just copy the path (without publishing the comment).
-   [ ] Commit and push the testing docs to the release branch.

## Smoke testing

Each porter is responsible for testing the PRs that fall under the focus of their own team. Shared functionality should be tested by both porters. This means that the Rubik porter will mostly test checkout blocks and Store API endpoints, while the Kirigami porter will test the product related blocks and Store API endpoints.

-   [ ] Smoke test the built release zip. Refer to the [Smoke testing checklist](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/internal-developers/testing/smoke-testing.md) for critical functionality.
-   [ ] Test in a clean environment. Create a Jurassic.Ninja site, upload your zip, then smoke test it.
-   [ ] Ask the porters of Rubik/Kirigami/Origami to test the built zip as well and to approve the PR if everything looks good. We recommend creating threads of p2 to track which test cases were tested.
-   [ ] Confirm all GitHub checks have passed on this PR prior to approving.

After testing:

-   If all PRs are testing as expected, continue with the release.
-   If one or more PRs are not testing as expected: ping the PR authors and the porter of the relevant team and ask them if the change is a release blocker or not (you can also ping the team lead if any of them is not available). In general, if it's not a regression or there is no product/marketing reason why that PR is a blocker, all other PRs should default to not being blockers.
    -   If there are blockers: stop the release and ask the PR author to fix them. If the PR author is AFK, ping the porter of their team.
    -   If some PRs are not testing as expected but they are not blockers: revert the relevant commits, remove the changes from testing steps and changelog, open an issue (or reopen the original one) and proceed with the release.
    -   If minor issues are discovered during the testing, each team is responsible for logging them in Github.

## Deploy the update

-   [ ] Make sure you've got `hub` installed (`brew install hub`) and make sure `hub api user` returns JSON with information about your GitHub user account. If it doesn't:
    -   Create a [GitHub access token](https://github.com/settings/tokens) with the `repo` permission.
    -   Set the environment variables: `GITHUB_USERNAME` with your GitHub Username, and `GITHUB_TOKEN` with the token you just generated. (You may want to add these to `.bashrc` or the equivalent)
    -   Run `hub api user` again and ensure JSON with information about your GitHub user account is returned.
-   [ ] Execute `npm run deploy`
    -   **ALERT**: This script will ask you if this release will be deployed to WordPress.org. You should only answer yes for this release **if it's the latest release and you want to deploy to WordPress.org**. Otherwise, answer no. If you answer yes, you will get asked additional verification by the `npm run deploy` script about deploying a patch release to WordPress.org.

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

-   [ ] Move the changes to the changelog, testing steps and required versions that you did in the previous steps to `trunk`. You can do so copy-and-pasting the changes in a new commit directly to `trunk`, or cherry-picking the commits that introduced those changes.
-   [ ] Update the schedules p2 with the shipped date for the release (PdToLP-K-p2).
-   [ ] Edit the GitHub milestone of the release you just shipped and add the current date as the due date (this is used to track ship date as well).

## Pull request in WooCommerce Core for Package update

This only needs done if the patch release needs to be included in WooCommerce Core. Reviewing and merging the PR is your team's responsibility, except in the case of `Fix Release Request`. In this case, the WooCommerce release manager reviews and merges the PR.

-   [ ] Create a pull request for updating the package in the [WooCommerce Core Repository](https://github.com/woocommerce/woocommerce/) that [bumps the package version](https://github.com/woocommerce/woocommerce/blob/747cb6b7184ba9fdc875ab104da5839cfda8b4be/plugins/woocommerce/composer.json) for the Woo Blocks package to the version you are releasing. Reviewing and merging the PR is your team's responsibility. [See this example](https://github.com/woocommerce/woocommerce/pull/32627).
    -   [ ] Set the base branch (the branch that your PR will be merged into) to the correct one. It must be: - `trunk` if the WC Blocks version you are releasing is higher than the one in core (you can find the current WC Blocks version in core in `plugins/woocommerce/composer.json`) - `release/x.y` if the WC Blocks version in core is higher than the one you are releasing (`x.y` must be the version of WC core that will include the version of WC Blocks you are releasing)
    -   [ ] Increase the version of `woocommerce/woocommerce-blocks` in the `plugins/woocommerce/composer.json` file
    -   [ ] Inside `plugins/woocommerce/`, run `composer update woocommerce/woocommerce-blocks` and make sure `composer.lock` was updated
    -   [ ] Run `pnpm --filter=woocommerce changelog add` to create a new changelog file similar to this one [plugins/woocommerce/changelog/update-woocommerce-blocks-7.4.1](https://github.com/woocommerce/woocommerce/blob/5040a10d01896bcf40fd0ac538f2b7bc584ffe0a/plugins/woocommerce/changelog/update-woocommerce-blocks-7.4.1). The file will be auto-generated with your answers. For the _Significance_ entry we’ll always use `patch`. For the changelog enter "Update WooCommerce Blocks to X.X.X".
    -   [ ] Verify and make any additional edits to the pull request description for things like: Changelog to be included with WooCommerce core, additional communication that might be needed elsewhere, additional marketing communication notes that may be needed, etc.

### Testing the PR

-   [ ] Build WC core from that branch with `pnpm run --filter='woocommerce' build ` (you might need to [install the dependencies first](https://github.com/woocommerce/woocommerce#prerequisites)) and:
    -   [ ] Make sure the correct version of WC Blocks is being loaded. This can be done testing at least one of the testing steps from the release.
    -   [ ] Complete the [Smoke testing checklist](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/internal-developers/testing/smoke-testing.md).
-   [ ] After the checklist is complete and the testing is done, select the porter of your team to review the PR. Once approved, make sure you merge the PR.

## Publish Posts

You only need to post public release announcements and update relevant public facing docs if this patch release is deployed to WP.org. Otherwise, you can skip this section.

-   [ ] Post release announcement on [WooCommerce Developer Blog](https://developer.woocommerce.com/category/release-post/woocommerce-blocks-release-notes/).
    -   Ping porters from each team to know which changelog entries need to be highlighted. Ask them to write a short text and optionally provide a screenshot. They can use previous posts for inspiration, we usually try to highlight new features or API changes.
    -   Ensure the release notes are included in the post verbatim.
    -   Don't forget to use category `WooCommerce Blocks Release Notes` for the post.
-   [ ] Announce the release internally (`#woo-announcements` slack).
-   [ ] Go through the description of the release pull request and edit it to update all the sections and checklist instructions there.
-   [ ] Merge this PR into the base branch: `release/x.y.0`.
