The release pull request has been created! This checklist is a guide to follow for the remainder of the patch release process. You can check off each item in this list once completed.

* [ ] Checkout the release branch locally.

## Initial Preparation

* [ ] Add the changelog to `readme.txt`
  * [ ] Add the version and date to the changelog section within `readme.txt`, e.g. `= {{version}} - YYYY-MM-DD =`
  * [ ] Copy the changelog from the pull request description above into this new section
* [ ] Update compatibility sections (if applicable). __Note:__ Do not change the stable tag or plugin version; this is automated.
* [ ] Push above changes to the release branch.

## Write Testing Notes

When creating testing notes, please write them from the perspective of a "user" (merchant) familiar with WooCommerce. So you don't have to spell out exact steps for common setup scenarios (eg. "Create a product"), but do be specific about the thing being tested. Include screenshots demonstrating expectations where that will be helpful.

Additionally, make sure to differentiate between things in the testing notes that only apply to the feature plugin and things that apply when included in WooCommerce core as there may be variations there.

* [ ] Run `npm ci`
* [ ] Run `npm run package-plugin:deploy`. This will create a zip of the current branch build locally.
* [ ] Create testing notes for the release. You can usually go through the pull requests linked in the changelog and grab testing notes from each pull.
  * [ ] Add the notes to `docs/testing/releases`
  * [ ] Update the `docs/testing/releases/README.md` file index.
* [ ] Copy a link to the release zip you created earlier into the testing notes. To generate the link you can upload the zip as an attachment in a GitHub comment and then just copy the path (without publishing the comment).
* [ ] Commit and push the testing docs to the release branch.
* [ ] Smoke test built release zip using the testing instructions you created:
  * At least one other person should test the built zip - ping the current Rubik porter to be this person.
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
* [ ] Execute `npm run deploy`
  * Note: the script automatically updates version numbers (commits on your behalf).
  * **ALERT**: This script will ask you if this release will be deployed to WordPress.org. You should only answer yes for this release **if it's the latest release and you want to deploy to WordPress.org**. Otherwise, answer no. If you answer yes, you will get asked additional verification by the `npm run deploy` script about deploying a patch release to WordPress.org.

## If this release is deployed to WordPress.org...

* [ ] An email confirmation is required before the new version will be released. Ask team members to check their email.
* [ ] Edit the [GitHub release](https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases) and copy changelog into the release notes.
* [ ] The `#team-rubik` slack instance will be notified about the progress with the WordPress.org deploy. Watch for that. If anything goes wrong, an error will be reported and you can followup via the GitHub actions tab and the log for that workflow.
* [ ] After the wp.org workflow completes, confirm the following
  * [ ] Changelog, Version, and Last Updated on [WP.org plugin page](https://wordpress.org/plugins/woo-gutenberg-products-block/) is correct.
  * [ ] Confirm svn tag is correct, e.g. [{{version}}](https://plugins.svn.wordpress.org/woo-gutenberg-products-block/tags/{{version}}/)
  * [ ] Confirm [WooCommerce.com plugin page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/) is updated.
  * [ ] Download zip and smoke test.
  * [ ] Test updating plugin from previous version.

## After Deploy

* [ ] Merge this branch back into the base branch.
  * If the base branch was `trunk`, and this release was deployed to WordPress.org, then merge the branch into `trunk`.
  * If the base branch is the release branch this patch release is for, then merge branch into release branch and then merge the release branch back to `trunk` if the patch release is the latest released version. Otherwise just merge back into the release branch.
* [ ] If you merged the branch to `trunk`, then update version on the `trunk` branch to be for the next version of the plugin and include the `dev` suffix (e.g. something like [`2.6-dev`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/commit/e27f053e7be0bf7c1d376f5bdb9d9999190ce158)) for the next version.
* [ ] Update the schedules p2 with the shipped date for the release (Pca54o-1N-p2).
* [ ] Clean up the release milestone.
  * [ ] Edit the GitHub milestone and add the current date as the due date (this is used to track ship date as well).
  * [ ] Close the milestone.

## Publish Posts

You only need to post public release announcements and update relevant public facing docs if this patch release is deployed to WP.org. Otherwise, you can skip this section.

* [ ] Post release announcement on [WooCommerce Developer Blog](https://developer.woocommerce.com/category/release-post/woocommerce-blocks-release-notes/). Use previous posts for inspiration. If the release contains new features, or API changes, explain what's new so Woo devs/builders/merchants can get excited about it. This post can take time to get right - get feedback from the team, and don't rush it :)
  - Ensure the release notes are included in the post verbatim.
  - Don't forget to use category `WooCommerce Blocks Release Notes` for the post.
* [ ] Announce the release internally (`#woo-announcements` slack).
* [ ] Update user-facing documentation as needed. When the plugin is released, ensure user-facing documentation is kept up to date with new blocks and compatibility information.
  - Update minimum supported versions (WordPress, WooCommerce Core) and other requirements where necessary, including:
    - [WCCOM product page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/)
    - [WooCommerce blocks main documentation page](https://docs.woocommerce.com/document/woocommerce-blocks/)

## Pull request in WooCommerce Core for Package update

This only needs done if the patch release needs to be included in WooCommerce Core.

* [ ] Create a pull request for updating the package in WooCommerce core (based off of the WooCommerce core release branch this is deployed for).
  - Create the pull request in the [WooCommerce Core Repository](https://github.com/woocommerce/woocommerce/) that [bumps the package version](https://github.com/woocommerce/woocommerce/blob/master/composer.json) for the blocks package to the version being pulled in.
  - The content for the pull release can follow [this example](https://github.com/woocommerce/woocommerce/pull/27676). Essentially you link to all the important things that have already been prepared. Note, you need to make sure you link to all the related documents for the feature plugin releases since the last package version bump in Woo Core.
      - Please add a changelog to the content which is aggregated from all the releases included in the package bump. The changelog should only list things surfaced to users of the package in WooCommerce core (i.e. excluding things only available in the feature plugin or development builds). This changelog will be used in the release notes for the WooCommerce release. **Note: This currently is not shown in the linked example.**
  - Run through the testing checklist to ensure everything works in that branch for that package bump. **Note:** Testing should include ensuring any features/new blocks that are supposed to be behind feature gating for the core merge of this package update are working as expected.
  - Testing should include completing the [Smoke testing checklist](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/testing/smoke-testing.md). It's up to you to verify that those tests have been done.
  - Verify and make any additional edits to the pull request description for things like: Changelog to be included with WooCommerce core, additional communication that might be needed elsewhere, additional marketing communication notes that may be needed etc.
  - After the checklist is complete and the testing is done, it will be up to the WooCommerce core team to approve and merge the pull request.
* [ ] Make sure you join the `#woo-core-releases` Slack channel to represent Woo Blocks for the release of WooCommerce core this version is included in.
