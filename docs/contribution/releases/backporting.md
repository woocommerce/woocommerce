---
post_title: Cherry-picking in WooCommerce
sidebar_label: Cherry-picking
sidebar_position: 3
---

# Cherry-picking in WooCommerce

Cherry-picking is the process of applying changes between `trunk` and a release branch (in either direction). This page is the canonical reference for the cherry-pick mechanics used by both pre-release stabilization (beta, RC) and patch releases.

For the patch-release-specific process (when a patch is warranted, who creates the tracking issue, and how it ships), see the [Point Releases guide](/docs/contribution/releases/point-releases).

## Release Branch Lifecycle

When a release branch is created, it is copied from `trunk` at the time of feature freeze. After creation:

- The release branch no longer receives new feature updates.
- Only critical changes are allowed.
- Because we do not merge release branches back into `trunk`, any fix landed on a release branch must also be applied to `trunk` (and, when applicable, to the next frozen release branch).

### What "frozen release" means

A frozen release branch is the most recent `release/x.y` cut from `trunk` for the next upcoming version. While the previous release is still in maintenance, this branch is the one feature work has already moved on to. The `cherry pick to frozen release` label forward-ports a fix from the current maintenance branch to this branch so the same fix also ships in the next major version. Frozen releases only accept critical bug fixes, the same bar as any other release branch.

## Qualifying Changes for Cherry-picking

Changes qualify for cherry-picking only if they are:

- **Bug fixes** that affect the release.
- **Performance improvements** that impact WooCommerce functionality.
- **Time-sensitive features** that impact business goals.
- **Contractually required features** for WooCommerce.

## Cherry-pick Workflows

### Standard Workflow: `trunk` to Release Branch

**When to use:** Most cherry-pick scenarios, including the preferred path for patch release fixes.

1. **Target `trunk`** as the base branch.
2. **Set the milestone** to the release series (e.g. `9.8.0`). WooCommerce milestones use the `.0` form for the whole `X.Y.x` series, so a fix targeting `X.Y.1` still uses milestone `X.Y.0`.
3. **Get the PR reviewed and merged** into `trunk`.
4. A cherry-pick PR against the release branch is opened automatically.
5. **The author** (or whoever merged the original PR) reviews, tests against the release branch, and merges the cherry-pick PR. The cherry-pick PR shares the same milestone as the original fix and must land before the release is published.

For urgent fixes near release deadlines, reach out to the release lead in the `#woo-core-releases` Slack channel.

### Alternative Workflow: Release Branch to `trunk`

**When to use:** The fix doesn't apply cleanly to `trunk`, or the branches have diverged enough that landing on `release/x.y` first is simpler.

1. **Target the release branch** (e.g. `release/9.8`) as the base branch.
2. **Apply labels** for any branches that need the forward-port:
    - `cherry pick to trunk` to forward-port to `trunk`.
    - `cherry pick to frozen release` to forward-port to the next frozen release branch (see [frozen release definition above](#what-frozen-release-means)).
3. **Include a changelog entry** and set the milestone to the release series (`X.Y.0`).
4. **Get the PR reviewed and merged** into the release branch.
5. The cherry-pick automation opens follow-up PRs against `trunk` and/or the frozen branch based on which labels are present.
6. **The author** reviews and merges the follow-up PRs promptly. They share the same milestone as the original fix and must land before the release is published.
