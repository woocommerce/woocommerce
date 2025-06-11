---
post_title: Backporting in WooCommerce
sidebar_label: Backporting
---

# Backporting in WooCommerce

Backporting is the process of applying a change from `trunk` to an open release branch.  When a release branch is created, it is copied from the `trunk` branch at the time of code freeze.  Changes are applied to `trunk` and then backported to the release branch as needed.

## Requesting backports (Contributors)

### Cherry picking to a frozen release

If you have a pull request that should be backported to a frozen release, you should target `trunk` as your base branch.  You can then request that the change is backported by adding the `cherry pick to frozen release` label to the pull request.  Make sure to add the `Milestone` of the version you're targetting to the PR.

Note that adding this label does not guarantee that the change will be backported.  The change must be qualified for backporting.

> If you're nearing the deadline for the final release, you may want to get in touch with the release lead directly to make them aware of the changes.

### Cherry picking to trunk

On occassion, more urgent changes may occur where we need to target the release branch directly as our base branch.  When this happens, you should add the label `cherry pick to trunk` if this change is also meant to be included in `trunk`.

## Qualifying changes

Changes are qualified for backporting if they are:

- A bug fix.
- A change that impacts the performance of WooCommerce.
- A new feature that is time sensitive and impacts WooCommerce's business goals.
- A new feature that is contractually required by WooCommerce.

## Manually backporting pull requests (Release Lead)

### Cherry picking to a frozen release

Before cutting a new RC, you should manually backport any PRs with the respective labels.

1. Check out the release branch `git checkout release/x.y`.
2. Find all the [PRs labeled to be cherry picked](https://github.com/woocommerce/woocommerce/pulls?q=is%3Apr+label%3A%22cherry+pick+to+frozen+release%22) to the release branch.  Filter by the current release milestone (`X.Y.0`) to limit to PRs relevant to this release.
3. Cherry-pick each PR (in chronological order) using `git cherry-pick [SHA]`.
4. After cherry-picking all PRs, push to the release branch using `git push`.
5. Remove the `cherry pick to frozen release` label and update the milestone to the current release for all cherry-picked PRs.

The SHA for a pull request can be found in the pull request activity once the PR has been merged.
