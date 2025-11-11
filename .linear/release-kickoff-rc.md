# WooCommerce {RELEASE_VERSION}-rc.{RC_PATCH_VERSION}

This issue tracks the progress of a single WooCommerce core plugin release iteration that covers the publishing of a single patch version. Patch version in this context refers to `z` in a version following `x.y.z` where `z` can be any of `0-99`, `0-rc.[0-99]`, or `0-beta.[0-99]`.

## Version being released {RELEASE_VERSION}-RC.{RC_PATCH_VERSION}

The following details are copied from the official [Building and Publishing guide](https://developer.woocommerce.com/docs/contribution/releases/building-and-publishing/). Please check it to make sure these instructions haven't become out of date.

## Pre-Checks

- [ ] **Verify no open [Pull Requests](https://github.com/woocommerce/woocommerce/pulls?q=is%3Aopen+is%3Apr) or [Issues](https://github.com/woocommerce/woocommerce/issues)** for the milestone matching the release being published.
    - All pull requests tied to the release milestone must be closed, including [backported pull requests](https://github.com/woocommerce/woocommerce/pulls?q=is%3Apr+label%3A%22type%3A+cherry-pick%22) that may need to be merged into other release branches or trunk.
- [ ] **Check for unresolved ["cherry pick failed" Pull Requests](https://github.com/woocommerce/woocommerce/pulls?q=is:pr+label:%22cherry+pick+failed%22).**
    - Ensure any such PRs are either expected or manually resolved via another PR.
- [ ] **Confirm the Stable tag in** `readme.txt` **matches [trunk on WordPress.org](https://plugins.trac.wordpress.org/browser/woocommerce/trunk/readme.txt#L7).**
    - The value should match the current stable version, not the version being built.
- [ ] **Ensure [GitHub services](https://www.githubstatus.com/) are fully operational**

## Build WooCommerce

1. **Run the ["Release: Bump version number" workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-bump-version.yml).**
   - Run from `trunk`.
   - Choose the type of version you're releasing (`beta`, `rc`, or `stable`).
   - Enter as *Release branch* the branch you are about to release from (e.g. `release/10.0`).
   - Review and merge the PR created.

2. **Run the ["Release: Compile changelog" workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-compile-changelog.yml).**
   - Run from `trunk` and enter the major version number and the intended release date.
   - **Review and merge the two PRs created** (one for trunk, one for the release branch).
   - **Ensure the changelog date is correct.**

3. **Build the release ZIP file.**
   - Build the release ZIP file using the ["Release: Build ZIP file" workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-build-zip-file.yml).
   - Run from `trunk` and enter the release branch as argument.
   - The workflow will create a [draft release tag](https://github.com/woocommerce/woocommerce/releases) with an attached `woocommerce.zip` file.

## Publish the Release

### Step 1: Upload Release to WordPress.org

- [ ] **Run the ["Release: Upload release to WordPress.org" workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-upload-to-wporg.yml)** from `trunk` using the release tag.
- [ ] **This creates a new [SVN tag](https://plugins.svn.wordpress.org/woocommerce/tags/) and, if the release is newer than trunk, overwrites trunk.**

### Step 2: Approve the Release

- [ ] **Visit [WordPress.org plugin releases](https://wordpress.org/plugins/developers/releases/) and approve the release.**
- [ ] **Wait a few minutes for WordPress.org to build the new version.**

### Step 3: Verify Release Availability

- [ ] **Confirm the new release appears at:**
    - <https://plugins.svn.wordpress.org/woocommerce/tags/>
    - The "Previous versions" dropdown on the [Advanced Options screen](https://wordpress.org/plugins/woocommerce/advanced/).

## Release to the Staging Environment (Stable and RC releases)

- [ ] **Condition:** Only perform this step for stable and RC releases (`-rc.x` or `.x`).
- [ ] **Action:** Follow the [guide to deploy to the staging environment](https://wp.me/PCYsg-18BQ).

## Update the Release Tags

### Step 1: Publish GitHub Release Tag

- [ ] **Action:** [Publish the previously created GitHub draft release tag](https://github.com/woocommerce/woocommerce/releases).
- [ ] **When setting release status:**
    - check "Set as a pre-release."

## Post Release Monitoring

For all RC and stable releases, the release lead should continue to monitor for any bugs directly related to the latest version. Monitoring should continue for 3 days after a major release and 1 day for a point release.

See the [WooCommerce Release Monitoring Guide](https://developer.woocommerce.com/docs/contribution/releases/monitoring/) for more details.
