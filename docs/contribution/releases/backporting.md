---
post_title: Backporting in WooCommerce
sidebar_label: Backporting
sidebar_position: 3
---

# Backporting in WooCommerce

Backporting is the process of applying changes from `trunk` to a release branch. This ensures critical fixes reach customers in upcoming releases.  Note that these flows apply only to UPCOMING RELEASES  (not patches to already-released versions).

## Release Branch Lifecycle

When a release branch is created, it's copied from `trunk` at the time of feature freeze. After creation:

- The release branch no longer receives new feature updates
- Only critical changes are allowed
- Because we do not merge the release branches back into `trunk`, any fixes in a release branch must also be applied to `trunk`.

## Qualifying Changes for Backporting

Changes qualify for backporting only if they are:

- **Bug fixes** that affect the release
- **Performance improvements** that impact WooCommerce functionality
- **Time-sensitive features** that impact business goals
- **Contractually required features** for WooCommerce

## Backporting Process for Contributors

### Standard Workflow: Trunk to Release Branch

**When to use:** Most backporting scenarios

1. **Target `trunk`** as your base branch
2. **Add milestone** matching your target release (e.g., `9.8.0`)
3. **Get PR reviewed and merged** into `trunk`
4. **Automated workflow** creates a cherry-pick PR for the release branch
5. **The original contributor or merger** reviews and merges the backport PR

> **Note:** For urgent fixes near release deadlines, contact the release lead directly.

### Alternative Workflow: Release Branch to Trunk

**When to use:** Critical fixes that must target the release branch directly

1. **Target the release branch** as your base branch
2. **Add label** `cherry pick to trunk` if the change should also go to `trunk`
3. **Get PR reviewed and merged** into the release branch
4. **Automated workflow** creates a forward-port PR for `trunk`
5. **Merge the trunk PR** as soon as possible to avoid delays

## Important Notes

- Changes must meet backporting qualifications
- Frozen releases only accept critical bug fixes
- All backports require review and testing
- Forward-ports to trunk should be merged promptly as these are tracked with the same milestone as the original PR.
