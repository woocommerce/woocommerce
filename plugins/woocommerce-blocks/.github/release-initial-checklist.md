The release pull request has been created! This checklist is a guide to follow for the remainder of the release process. You can check off each item in this list once completed.

* [ ] Checkout the release branch locally.

## Initial Preparation
* [ ] Add the changelog to `readme.txt`
  * [ ] Add the version and date to the changelog section within `readme.txt`, e.g. `= {{version}} - YYYY-MM-DD =`
  * [ ] Copy the changelog from the pull request description above into this new section
* [ ] Update compatibility sections (if applicable). __Note:__ Do not change the stable tag or plugin version; this is automated.
  * [ ] Update _Requires at least_, _Tested up to_, and _Requires PHP_ sections at the top of `readme.txt`. Note, this should also be the latest WordPress version available at time of release.
  * [ ] Update _Requires at least_, _Requires PHP_, _WC requires at least_, and _WC tested up to_ at the top of `woocommerce-gutenberg-products-block.php`. Note, this should include requiring the latest WP and WC versions at the time of the plugin release.
  * [ ] If necessary, update the value of `$minimum_wp_version` at the top of the `woocommerce-gutenberg-products-block.php` file to the latest available version of WordPress.
  * [ ] If necessary, update the `phpcs.xml` file to reference the minimum WP version supported by **WooCommerce Core**. It would be this line: `<config name="minimum_supported_wp_version" value="5.6" />`.
* [ ] Push above changes to the release branch.

## Write Testing Notes

When creating testing notes, please write them from the perspective of a "user" (merchant) familiar with WooCommerce. So you don't have to spell out exact steps for common setup scenarios (eg. "Create a product"), but do be specific about the thing being tested. Include screenshots demonstrating expectations where that will be helpful.

Additionally, make sure to differentiate between things in the testing notes that only apply to the feature plugin and things that apply when included in WooCommerce core as there may be variations there.

* [ ] Run `npm ci`
* [ ] Run `npm run package-plugin:deploy`. This will create a zip of the current branch build locally.
  *  Note: The zip file is functionally equivalent to what gets released except the version bump.
* [ ] Create testing notes for the release. You can usually go through the pull requests linked in the changelog and grab testing notes from each pull.
  * [ ] Add the notes to `docs/testing/releases`
  * [ ] Update the `docs/testing/releases/README.md` file index.
* [ ] Copy a link to the release zip you created earlier into the testing notes. To generate the link you can upload the zip as an attachment in a GitHub comment and then just copy the path (without publishing the comment).
* [ ] Commit and push the testing docs to the release branch.
* [ ] Smoke test built release zip using the testing instructions you created:
  * At least one other person should test the built zip - ask a teammate to help out.
  * Test in a clean environment, e.g. Jurassic.Ninja site.
  * Test existing WooCommerce Blocks content works correctly after update (no block validation errors).
  * Test to confirm blocks are available and work correctly in oldest supported WordPress version (e.g. 5.3).
  * Confidence check - check blocks are available and function.
  * Test to confirm new features/fixes are working correctly.
  * Test any UI changes in mobile and desktop views.
  * Smoke test – test a cross section of core functionality.

## Update Pull Request description and get approvals

* [ ] Go through the description of the release pull request and edit it to update all the sections and checklist instructions there.
* [ ] Ask a team member to review the changes in the release pull request and for anyone who has done testing that they approve the pull request.

## Ensure hub is set up and you're authenticated
* [ ] Make sure you've got `hub` installed (`brew install hub`)
* [ ] Make sure `hub api user` returns JSON with information about your GitHub user account, if it doesn't:
  * [ ] Create a [GitHub access token](https://github.com/settings/tokens) with the `repo` permission.
  * [ ] Set the environment variables: `GITHUB_USERNAME` with your GitHub Username, and `GITHUB_TOKEN` with the token you just generated. (You may want to add these to `.bashrc` or the equivalent)
  * [ ] Run `hub api user` again and ensure JSON with information about your GitHub user account is returned.

## Push the button - Deploy!

* [ ] Execute `npm run deploy`
  * The script will ask you to enter the version number to tag. Please enter the version we're releasing right now. Do not publish any dev tags as a release.
  * Note: the script automatically updates version numbers on Github (commits on your behalf).
  * **ALERT**: This script will ask you if this release will be deployed to WordPress.org. You should answer yes for this release even if it is a pre-release.
  * A GitHub release will automatically be created and this will trigger a workflow that automatically deploys the plugin to WordPress.org.
* [ ] Edit the [GitHub release](https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases) and copy changelog into the release notes. Ensure there is a release with the correct version, the one you entered above.
* [ ] The `#team-rubik` slack instance will be notified about the progress with the WordPress.org deploy. Watch for that. If anything goes wrong, an error will be reported and you can followup via the GitHub actions tab and the log for that workflow.

## After Workflow completes

* [ ] After the wp.org workflow completes, confirm the following
  * [ ] Changelog, Version, and Last Updated on [WP.org plugin page](https://wordpress.org/plugins/woo-gutenberg-products-block/) is correct.
  * [ ] Confirm svn tag is correct, e.g. [{{version}}](https://plugins.svn.wordpress.org/woo-gutenberg-products-block/tags/{{version}}/)
  * [ ] Confirm [WooCommerce.com plugin page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/) is updated.
  * [ ] Download zip and smoke test.
  * [ ] Test updating plugin from previous version.
* [ ] Merge this pull request back into `trunk`. This may have merge conflicts needing resolved if there are any cherry-picked commits in the release branch.
* [ ] Update version on the `trunk` branch to be for the next version of the plugin and include the `dev` suffix (e.g. something like [`2.6-dev`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/commit/e27f053e7be0bf7c1d376f5bdb9d9999190ce158)) for the next version. Be sure to update the version number in the following files:
    * [ ] `package-lock.json`
    * [ ] `package.json`
    * [ ] `readme.txt`
    * [ ] `src/Package.php`
    * [ ] `woocommerce-gutenberg-products-block.php`.
* [ ] Update the schedules p2 with the shipped date for the release (Pca54o-1N-p2).
* [ ] Clean up the release milestone and Zenhub.
  * [ ] Edit the [GitHub milestone](https://github.com/woocommerce/woocommerce-gutenberg-products-block/milestones) and add the current date as the due date (this is used to track ship date as well).
  * [ ] Close the milestone.
  * [ ] Remove any unfinished issues from the Zenhub epics completed by this release and then close the epics.

## Publish posts

* [ ] Post release announcement on [WooCommerce Developer Blog](https://woocommerce.wordpress.com/category/blocks/). Use previous posts for inspiration. If the release contains new features, or API changes, explain what's new so Woo devs/builders/merchants can get excited about it. This post can take time to get right - get feedback from the team, and don't rush it :)
  - Ensure the release notes are included in the post verbatim.
  - Don't forget to use category `Blocks` for the post.
* [ ] Announce the release internally (`#woo-announcements` slack).
* [ ] Update user-facing documentation as needed. When the plugin is released, ensure user-facing documentation is kept up to date with new blocks and compatibility information. The dev team should update documents in collaboration with support team and WooCommerce docs guild. In particular, please review and update as needed:
  - Are there any new blocks in this release? Ensure they have adequate user documentation.
  - Ensure any major improvements or changes are documented.
  - Update minimum supported versions (WordPress, WooCommerce Core) and other requirements where necessary, including:
    - [WCCOM product page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/)
    - [WooCommerce blocks main documentation page](https://docs.woocommerce.com/document/woocommerce-blocks/)

## Pull request in WooCommerce Core for Package update

This only needs to be done if this release is the last release of the feature plugin before code freeze in the WooCommerce core cycle. If this condition doesn't exist you can skip this section.

* [ ] Remind whoever is porter this week to audit our codebase to ensure this [experimental interface document](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/blocks/feature-flags-and-experimental-interfaces.md) is up to date. See Pca54o-rM-p2 for more details.
* [ ] Create a pull request for updating the package in the [WooCommerce Core Repository](https://github.com/woocommerce/woocommerce/) that [bumps the package version](https://github.com/woocommerce/woocommerce/blob/master/composer.json) for the blocks package to the version being pulled in.
  * The content for the pull release can follow [this example](https://github.com/woocommerce/woocommerce/pull/27676). Essentially you link to all the important things that have already been prepared. Note, you need to make sure you link to all the related documents for the feature plugin releases since the last package version bump in Woo Core.
      * Please add a changelog to the content which is aggregated from all the releases included in the package bump. The changelog should only list things surfaced to users of the package in WooCommerce core (i.e. excluding things only available in the feature plugin or development builds). This changelog will be used in the release notes for the WooCommerce release. **Note: This currently is not shown in the linked example.**
  * Run through the testing checklist to ensure everything works in that branch for that package bump. **Note:** Testing should include ensuring any features/new blocks that are supposed to be behind feature gating for the core merge of this package update are working as expected.
  * Testing should include completing the [Smoke testing checklist](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/testing/smoke-testing.md). It's up to you to verify that those tests have been done.
  * Verify and make any additional edits to the pull request description for things like: Changelog to be included with WooCommerce core, additional communication that might be needed elsewhere, additional marketing communication notes that may be needed etc.
  * After the checklist is complete and the testing is done, it will be up to the WooCommerce core team to approve and merge the pull request.
* [ ] Make sure you join the `#woo-core-releases` Slack channel to represent Woo Blocks for the release of WooCommerce core this version is included in.


