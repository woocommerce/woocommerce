---
post_title: WooCommerce Release Backporting
menu_title: WooCommerce Release Backporting
tags: reference
---

Backporting is the process of applying a change from `trunk` to an open release branch.  When a release branch is created, it is copied from the `trunk` branch at the time of code freeze.  Changes are applied to `trunk` and then backported to the release branch as needed.

## Requesting a backport

If you have a pull request that should be backported to a previous release, you can request a backport by adding the `Backport to core` label to the pull request.

Note that adding this label does not guarantee that the change will be backported.  The change must be qualified for backporting.

## Qualifying changes

Changes are qualified for backporting if they are:

- A bug fix.
- A change that impacts the performance of WooCommerce.
- A new feature that is time sensitive and impacts WooCommerce's business goals.
- A new feature that is contractually required by WooCommerce.

## Manually backporting pull requests

1. Check out the release branch `git checkout release/x.y`.
2. Cherry-pick each PR (in chronological order) using `git cherry-pick [SHA]`.
3. After cherry-picking all PRs, push to the release branch using `git push`.
4. Remove the `Backport to core` label and update the milestone to the current release for all cherry-picked PRs.

The SHA for a pull request can be found in the pull request activity once the PR has been merged.
