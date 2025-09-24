---
post_title: Building and Publishing a Release
sidebar_label: Building and Publishing
sidebar_position: 1
---

# Building and Publishing a Release

:::note

If you encounter any issues during the release process, refer to the [Release Troubleshooting & Recovery guide](/docs/contribution/releases/troubleshooting) for step-by-step solutions to common problems and recovery procedures.

:::

## Prerequisites

- You must have a WordPress.org account with committer access to the WooCommerce plugin to approve the commit.

## Pre-Checks

1. **Verify no open [Pull Requests](https://github.com/woocommerce/woocommerce/pulls?q=is%3Aopen+is%3Apr) or [Issues](https://github.com/woocommerce/woocommerce/issues)** for the milestone matching the release being published.
   - All pull requests tied to the release milestone must be closed, including [backported pull requests](/docs/contribution/releases/backporting) that may need to be merged into other release branches or trunk.
2. **Check for [unresolved "cherry pick failed" Pull Requests](https://github.com/woocommerce/woocommerce/pulls?q=is:pr+label:%22cherry+pick+failed%22).**
   - Ensure any such PRs are either expected or manually resolved via another PR.
3. **Confirm the Stable tag in `readme.txt` matches [trunk on WordPress.org](https://plugins.trac.wordpress.org/browser/woocommerce/trunk/readme.txt#L7).**
   - The value should match the current stable version, not the version being built.
4. **Ensure [GitHub services](https://www.githubstatus.com/) are fully operational**

## Building WooCommerce

1. **Run the ["Release: Bump version number" workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-bump-version.yml).**
   - Run from `trunk`.
   - Choose the type of version you're releasing (`beta`, `rc`, or `stable`).
   - Enter as _Release branch_ the branch you are about to release from (e.g. `release/10.0`).
   - Review and merge the PR created.
2. **Run the [“Release: Compile changelog” workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-compile-changelog.yml).**
   - Run from `trunk` and enter the major version number and the intended release date.
   - Review and merge the two PRs created (one for trunk, one for the release branch).
   - Ensure the changelog date is correct.
3. **Build the release ZIP file using the [“Release: Build ZIP file” workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-build-zip-file.yml).**
   - Run from `trunk` and enter the release branch as argument.
   - Set "Create GitHub release" to `true`.
   - The workflow will create a [draft release tag](https://github.com/woocommerce/woocommerce/releases) with an attached `woocommerce.zip` file.

## Publishing the Release

### Step 1: Upload Release to WordPress.org

- Run the ["Release: Upload release to WordPress.org" workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-upload-to-wporg.yml) from `trunk` using the release tag.
- This creates a new SVN tag and, if the release is newer than trunk, overwrites trunk.

### Step 2: Approve the Release

- Visit [WordPress.org plugin releases](https://wordpress.org/plugins/developers/releases/) and approve the release.
- Wait a few minutes for WordPress.org to build the new version.

### Step 3: Verify Release Availability

- Confirm the new release appears at:
    - [https://plugins.svn.wordpress.org/woocommerce/tags/](https://plugins.svn.wordpress.org/woocommerce/tags/)
    - The "Previous versions" dropdown on the [Advanced Options screen](https://wordpress.org/plugins/woocommerce/advanced/).

### Step 4: Test and Validate the Release

- **Condition:** Only perform this step for stable and RC releases (`-rc.x` or `.x`).
- **Action:** Conduct thorough testing and validation of the release to ensure stability and functionality. Carefully monitor for any issues that could critically impact sites running this version.

### Step 5: Update Stable Tag

- **Condition:** Only perform this step if:
    - The release is a stable release (`.x`), **and**
    - No major issues were found during testing and validation (Step 4).
- **Action:** Run the ["Release: Update stable tag" workflow](https://github.com/woocommerce/woocommerce/actions/workflows/release-update-stable-tag.yml) from `trunk`, set the version, and select the option to update the stable tag as part of the workflow input.
    - Review and merge the pull requests for both the release branch and trunk.

### Step 6: Publish GitHub Release Tag

- **Action:** Publish the [previously created GitHub draft release tag](https://github.com/woocommerce/woocommerce/releases).
- **When setting release status:**
    - If releasing a dev, beta, or RC, check "Set as a pre-release."
    - If the version was marked as stable in Step 5, check "Set as the latest release."
    - If the version was **not** marked as stable in Step 5, do **not** set as the latest release.

## Decision Table

| Step   | Condition to Run                                                               | Action if Condition Not Met         |
|--------|--------------------------------------------------------------------------------|-------------------------------------|
| Step 4 | Not a Stable **or** Release Candidate (RC)                                     | Skip Step 4                        |
| Step 5 | Not a Stable release **and** no major issues in Step 4                         | Skip Step 5                        |
| Step 6 | Always publish tag; mark as "latest" only if version was marked as stable in 5 | Do not mark as "latest" if not stable in 5 |
