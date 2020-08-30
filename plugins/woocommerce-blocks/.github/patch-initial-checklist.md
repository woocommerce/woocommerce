The release pull request has been created! This checklist is a guide to follow for the remainder of the patch release process. You can check off each item in this list once completed.

## Checklist

* [ ] Checkout the release branch locally.
* [ ] Create a GitHub milestone for the next release if necessary (set the start date as today's date). In some cases this may not be necessary for a patch release if there is already a minor release milestone present.
* [ ] If there are any remaining open issues in the milestone for {{version}}, do a final check with the team to verify none of those *need* to go into the release. After verifying, go through and move any open issues/pulls into the next milestone (except for any that have the `type: blocker` label). After this any `type: blocker` labelled issues must be complete and merged into the release branch (cherry-pick if necessary) before continuing.
* [ ] If this patch release is the latest release. Copy the changelog from the pull request description into the `readme.txt` file in the changelog section. Create a new section here for this release, eg.g. `= {{version}} - 2020-01-20 =`.
* [ ] If this patch release is the latest release. Make any other changes to plugin metadata as necessary (no version changes needed, that's handled by script later in the process).
  * [ ] `readme.txt` - support versions changing, reference new blocks if necessary.
  * [ ] `woocommerce-gutenberg-products-block.php` - requirements/tested up to versions etc.
* [ ] Push above changes to the release branch.
* [ ] Create testing notes for the release. You can usually go through the pull requests linked in the changelog and grab testing notes from each pull. Add the notes to `docs/testing/releases` and update the `docs/testing/releases/README.md` index.
* [ ] Run `npm ci`
* [ ] Run `composer install --no-dev`
* [ ] Run `npm run package-plugin:deploy`. This will create a zip of the current branch build locally.
* [ ] Copy a link to the release zip into the testing notes you created earlier. To generate the link you can upload the zip as an attachment in a GitHub comment and then just copy the path (without publishing the comment).
* [ ] Commit and push the testing docs to the release branch.
* [ ] Smoke test built release zip using the testing instructions you created:
  * At least one other person should test the built zip - ask a teammate to help out.
  * Test in a clean environment, e.g. Jurassic.Ninja site.
  * Test existing WooCommerce Blocks content works correctly after update (no block validation errors).
  * Test to confirm blocks are available and work correctly in oldest supported WordPress version (e.g. 5.0).
  * Confidence check - check blocks are available and function.
  * Test to confirm new features/fixes are working correctly.
  * Smoke test – test a cross section of core functionality.
* [ ] Go through the description of the release pull request and edit it to update all the sections and checklist instructions there.
* [ ] Ask a team member to review the changes in the release pull request and for anyone who has done testing that they approve the pull request.
* [ ] Execute `npm run deploy`
  * Note: the script automatically updates version numbers (commits on your behalf).
  * **ALERT**: This script will ask you if this release will be deployed to WordPress.org. You should only answer yes for this release **if it's the latest release and you want to deploy to WordPress.org**. Otherwise, answer no. If you answer yes, you will get asked additional verification by the `npm run deploy` script about deploying a patch release to WordPress.org.
* [ ] If this is released to WordPress.org, edit the GitHub release and copy changelog into the release notes.
* [ ] If this is deployed to WordPress.org, the `#team-rubik` slack instance will be notified about the progress with the WordPress.org deploy. Watch for that. If anything goes wrong, an error will be reported and you can followup via the GitHub actions tab and the log for that workflow.
* [ ] If this is deployed to WordPress.org, after the wp.org workflow completes, confirm the following
  * [ ] Changelog, Version, and Last Updated on [WP.org plugin page](https://wordpress.org/plugins/woo-gutenberg-products-block/) is correct.
  * [ ] Confirm svn tag is correct, e.g. [{{version}}](https://plugins.svn.wordpress.org/woo-gutenberg-products-block/tags/{{version}}/)
  * [ ] Confirm [WooCommerce.com plugin page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/) is updated.
  * [ ] Download zip and smoke test.
  * [ ] Test updating plugin from previous version.
* [ ] Merge this branch back into the base branch. If the base branch was a release branch, and this release was deployed to WordPress.org, merge the release branch into main. If the base branch was `main`, and this release was deployed to WordPress.org, then merge the branch into `main`. Otherwise do NOT merge to `main` if the release was not deployed to WordPress.org, just close the pull and delete the branch.
* [ ] Update the schedules p2 with the shipped date for the release (Pca54o-1N-p2).
* [ ] Clean up the release milestone.
  * [ ] Edit the GitHub milestone and add the current date as the due date (this is used to track ship date as well).
  * [ ] Close the milestone.
* [ ] Create a pull request for updating the package in WooCommerce core (based off of the WooCommerce core release branch this is deployed for).
  - Create the pull request in the [WooCommerce Core Repository](https://github.com/woocommerce/woocommerce/) that [bumps the package version](https://github.com/woocommerce/woocommerce/blob/master/composer.json) for the blocks package to the version being pulled in.
  - The content for the pull release can follow [this example](https://github.com/woocommerce/woocommerce/pull/27177). Essentially you link to all the important things that have already been prepared. Note, you need to make sure you link to all the related documents for the feature plugin releases since the last package version bump in Woo Core.
  - Run through the testing checklist to ensure everything works in that branch for that package bump. **Note:** Testing should include ensuring any features/new blocks that are supposed to be behind feature gating for the core merge of this package update are working as expected.
  - Verify and make any additional edits to the pull request description for things like: Changelog to be included with WooCommerce core, additional communication that might be needed elsewhere, additional marketing communication notes that may be needed etc.
  - After the checklist is complete and the testing is done, it will be up to the WooCommerce core team to approve and merge the pull request.
* [ ] Post release announcement on [WooCommerce Developer Blog](https://woocommerce.wordpress.com/category/blocks/). Use previous posts for inspiration. If the release contains new features, or API changes, explain what's new so Woo devs/builders/merchants can get excited about it. This post can take time to get right - get feedback from the team, and don't rush it :)
  - Ensure the release notes are included in the post verbatim.
  - Don't forget to use category `Blocks` for the post.
* [ ] Update user-facing documentation as needed. When the plugin is released, ensure user-facing documentation is kept up to date with new blocks and compatibility information.
  - Update minimum supported versions (WordPress, WooCommerce Core) and other requirements where necessary, including:
    - [WCCOM product page](https://woocommerce.com/products/woocommerce-gutenberg-products-block/)
    - [WooCommerce blocks main documentation page](https://docs.woocommerce.com/document/woocommerce-blocks/)

