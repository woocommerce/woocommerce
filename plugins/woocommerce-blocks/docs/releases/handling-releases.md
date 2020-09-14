# Handling Releases

The WooCommerce Blocks project has a number of automations that aid with making releases. The following are the steps you should take for making a release:

* Create a GitHub milestone for the next release.
* Double-check any merged pulls since the last release that should be included in the release notes are assigned to the milestone for this release. **Note:** You don't need to include renovate pull requests in the milestone.
* If there are any remaining open issues in the milestone for the release, do a final check with the team to verify none of those *need* to go into the release. After verifying, go through and move any open issues/pulls into the next milestone (except for any that have the `type: blocker` label). After this any `type: blocker` labelled issues must be complete and merged into the release branch (cherry-pick if necessary) before continuing.
* Use the [GitHub UI](https://docs.github.com/en/github/collaborating-with-issues-and-pull-requests/creating-and-deleting-branches-within-your-repository#creating-a-branch) to create a release branch for the version being released. The format for the branch name should be `release/x.x` where `x.x` is the version being released. If you are doing a patch release, first, make sure you are viewing the correct release branch before creating the new patch branch. This ensures that the new branch is based off of the release branch. So for example if you were doing a patch release for `3.4.1`. You'd select `release/3.4` from the GitHub branch selector and then create a `release/3.4.1` branch.
* A GitHub workflow is kicked off that will do the following:
  * Create throw-away initial commits (GitHub requires a diff between the branch and it's base in order for pull requests to be created).
  * Notify in the team slack channel that the workflow has started.
  * Create a pull request using either the template for the [release pull request](../../.github/release-pull-request.md) or [patch release pull request](../../.github/patch-release-pull-request.md) if this is for a patch version.
  * Create a comment in the new pull request with a checklist for doing the release. The checklist is also from a [template](../../.github/release-initial-checklist.md) ([patch release checklist template](../../.github/patch-initial-checklist.md)).
  * Notify in the team slack channel that the workflow is completed.
* Work through the checklist in the pull request.

## Release Pull Requests (and templates)

To aid with the quality of our releases and also serve as a primary communication channel for releases that end up pulled into WooCommerce core via package dependency, every release begins with a _release pull request_. This pull request has a standard description that gets filled out with details about the release and a checklist (in a comment on the pull request) to use for doing the release.

There are two sets of templates that are used for creating the release pull request.

- Major/Minor versions use the [release pull request](../../.github/release-pull-request.md) and [release checklist](../../.github/release-initial-checklist.md) templates.
- Patch versions use the [patch release pull request](../../.github/patch-release-pull-request.md) and [checklist](../../.github/patch-initial-checklist.md) templates.

Any changes to to the release process can be updated in those templates. The GitHub automation parses the templates through [handlebars](https://handlebarsjs.com/), so handlebars syntax can be used. The following variables are available in the templates:

- `{{version}}`: This will be replaced with the normalized version derived from the release branch. So for instance if the branch was `release/2.5` then the version value would be `2.5.0`.
- `{{changelog}}`: This will be replaced with the changelog for the release (derived from the milestone).
- `{{devNoteItems}}`: This contains a list of any pull requests requiring devnotes for the release.


## Appendix: Versions

Woo Blocks follows the [same versioning process as WordPress](https://make.wordpress.org/core/handbook/about/release-cycle/version-numbering/) but with the following differences:

- Woo Blocks follows a scheduled release process (currently released every two weeks).
- We might still refer to `3.2.1` as a "patch" release instead of a "minor" release. But functionally, it's the same concept as a WordPress "minor" release.

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
