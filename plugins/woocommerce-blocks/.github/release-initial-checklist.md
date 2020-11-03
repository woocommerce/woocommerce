The release pull request has been created! This checklist is a guide to follow for the remainder of the release process. You can check off each item in this list once completed.

* [ ] Checkout the release branch locally.

## Initial Prep - changelog and meta data changes

* [ ] Copy the changelog from the pull request description into the `readme.txt` file to the top of the changelog section. Create a new section here for this release, eg. `= {{version}} - 2020-01-20 =`.
* [ ] Make any other changes to plugin metadata as necessary (no version changes needed, that's handled by script later in the process).
  * [ ] `readme.txt` - support versions changing, reference new blocks if necessary.
  * [ ] `woocommerce-gutenberg-products-block.php` - requirements/tested up to versions etc.
* [ ] Push above changes to the release branch.

## Testing Notes and Testing

When creating testing notes, please write them from the perspective of a "user" (merchant) familiar with WooCommerce. So you don't have to spell out exact steps for common setup scenarios (eg. "Create a product"), but do be specific about the thing being tested. Include screenshots demonstrating expectations where that will be helpful.

* [ ] Run `npm ci`
* [ ] Run `npm run package-plugin:deploy`. This will create a zip of the current branch build locally.
* [ ] Create testing notes for the release. You can usually go through the pull requests linked in the changelog and grab testing notes from each pull. Add the notes to `docs/testing/releases` and update the `docs/testing/releases/README.md` index.
  * Note, make sure to differentiate between things in the testing notes that only apply to the feature plugin and things that apply when included in WooCommerce core as there may be variations there.
* [ ] Copy a link to the release zip you created earlier into the testing notes. To generate the link you can upload the zip as an attachment in a GitHub comment and then just copy the path (without publishing the comment).
* [ ] Commit and push the testing docs to the release branch.
* [ ] Smoke test built release zip using the testing instructions you created:
  * At least one other person should test the built zip - ask a teammate to help out.
  * Test in a clean environment, e.g. Jurassic.Ninja site.
  * Test existing WooCommerce Blocks content works correctly after update (no block validation errors).
  * Test to confirm blocks are available and work correctly in oldest supported WordPress version (e.g. 5.3).
  * Confidence check - check blocks are available and function.
  * Test to confirm new features/fixes are working correctly.
  * Smoke test – test a cross section of core functionality.

## Update Pull Request description and get approvals

* [ ] Go through the description of the release pull request and edit it to update all the sections and checklist instructions there.
* [ ] Ask a team member to review the changes in the release pull request and for anyone who has done testing that they approve the pull request.

## Push the button - Deploy!

* [ ] Execute `npm run deploy`
  * Note: the script automatically updates version numbers (commits on your behalf).
  * **ALERT**: This script will ask you if this release will be deployed to WordPress.org. You should answer yes for this release even if it is a pre-release. A GitHub release will automatically be created and this will trigger a workflow that automatically deploys the plugin to WordPress.org.
* [ ] Edit the [GitHub release](https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases) and copy changelog into the release notes.
* [ ] The `#team-rubik` slack instance will be notified about the progress with the WordPress.org deploy. Watch for that. If anything goes wrong, an error will be reported and you can followup via the GitHub actions tab and the log for that workflow.

## After Workflow completes

* [ ] After the wp.org workflow completes, confirm the following
  * [ ] Changelog, Version, and Last Updated on [WP.org plugin page](https://wordpress.org/plugins/woo-gutenberg-products-block/) is correct.
  * [ ] Confirm svn tag is correct, e.g. [{{version}}](https://plugins.svn.wordpress.org/woo-gutenberg-products-block/tags/{{version}}/)
  * [ ] Confirm [WooCommerce.com plugin page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/) is updated.
  * [ ] Download zip and smoke test.
  * [ ] Test updating plugin from previous version.
* [ ] Merge this pull request back into `trunk`. This may have merge conflicts needing resolved if there are any cherry-picked commits in the release branch.
* [ ] Update version on the `trunk` branch to be for the next version of the plugin and include the `dev` suffix (e.g. something like [`2.6-dev`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/commit/e27f053e7be0bf7c1d376f5bdb9d9999190ce158)) for the next version.
* [ ] Update the schedules p2 with the shipped date for the release (Pca54o-1N-p2).
* [ ] Clean up the release milestone and Zenhub.
  * [ ] Edit the [GitHub milestone](https://github.com/woocommerce/woocommerce-gutenberg-products-block/milestones) and add the current date as the due date (this is used to track ship date as well).
  * [ ] Close the milestone.
  * [ ] Remove any unfinished issues from the Zenhub epics completed by this release and then close the epics.

## Publish posts

* [ ] Post release announcement on [WooCommerce Developer Blog](https://woocommerce.wordpress.com/category/blocks/). Use previous posts for inspiration. If the release contains new features, or API changes, explain what's new so Woo devs/builders/merchants can get excited about it. This post can take time to get right - get feedback from the team, and don't rush it :)
  - Ensure the release notes are included in the post verbatim.
  - Don't forget to use category `Blocks` for the post.
* [ ] Update user-facing documentation as needed. When the plugin is released, ensure user-facing documentation is kept up to date with new blocks and compatibility information. The dev team should update documents in collaboration with support team and WooCommerce docs guild. In particular, please review and update as needed:
  - Are there any new blocks in this release? Ensure they have adequate user documentation.
  - Ensure any major improvements or changes are documented.
  - Update minimum supported versions (WordPress, WooCommerce Core) and other requirements where necessary, including:
    - [WCCOM product page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/)
    - [WooCommerce blocks main documentation page](https://docs.woocommerce.com/document/woocommerce-blocks/)

## Pull request in WooCommerce Core for Package update

This only needs to be done if this release is the last release of the feature plugin before code freeze in the WooCommerce core cycle. If this condition doesn't exist you can skip this section.

* [ ] Create a pull request for updating the package in the [WooCommerce Core Repository](https://github.com/woocommerce/woocommerce/) that [bumps the package version](https://github.com/woocommerce/woocommerce/blob/master/composer.json) for the blocks package to the version being pulled in.
  * The content for the pull release can follow [this example](https://github.com/woocommerce/woocommerce/pull/27676). Essentially you link to all the important things that have already been prepared. Note, you need to make sure you link to all the related documents for the feature plugin releases since the last package version bump in Woo Core.
  * Run through the testing checklist to ensure everything works in that branch for that package bump. **Note:** Testing should include ensuring any features/new blocks that are supposed to be behind feature gating for the core merge of this package update are working as expected.
  * Testing should include completing the [Smoke testing checklist](../docs/testing/smoke-testing.md). It's up to you to verify that those tests have been done.
  * Verify and make any additional edits to the pull request description for things like: Changelog to be included with WooCommerce core, additional communication that might be needed elsewhere, additional marketing communication notes that may be needed etc.
  * After the checklist is complete and the testing is done, it will be up to the WooCommerce core team to approve and merge the pull request.


