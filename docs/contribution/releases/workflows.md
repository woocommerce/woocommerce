---
post_title: Release Workflows
sidebar_label: Workflows
sidebar_position: 9
---

# Release Workflows

The WooCommerce release process is supported by a set of GitHub Actions workflows that automate repetitive tasks, enforce process guardrails, and send notifications. This page provides a reference of all release-related workflows, organized by how they are triggered.

All workflows are defined in the [`.github/workflows/`](https://github.com/woocommerce/woocommerce/tree/trunk/.github/workflows) directory.

## Scheduled workflows

These workflows run automatically on a daily schedule.

| Workflow | Schedule | What it does | When it acts |
| -------- | -------- | ------------ | ------------ |
| [Release: Assignment](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-assignment.yml) | Daily at 18:00 UTC | Checks the [release calendar](https://developer.woocommerce.com/release-calendar/), assigns the release lead, and creates the parent tracking issue with sub-issues for each release in the cycle. Calls *Create Tracking Issue* for each sub-issue. | ~8 weeks before feature freeze. |
| [Release: Enforce Feature Freeze](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-code-freeze.yml) | Daily at 18:00 UTC | Checks the [release calendar](https://developer.woocommerce.com/release-calendar/) and creates the release branch from `trunk`, bumps `trunk` to the next dev version, publishes a dev release, cleans up old milestones, and sends Slack notifications. Calls *Bump version number* and *Build ZIP file*. | On the feature freeze date. |
| [Release: Feature highlight notification](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-feature-highlights-notification.yml) | Daily at 09:00 UTC | Checks the [release calendar](https://developer.woocommerce.com/release-calendar/) and sends a Slack reminder to teams about the upcoming feature freeze deadline. | ~1 week before feature freeze. |
| [Release: Open Issue Warning](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-open-issue-warning.yml) | Daily at 18:00 UTC | Checks the [release calendar](https://developer.woocommerce.com/release-calendar/) and looks for open items in release milestones, sending a Slack notification listing them with assignees. | Within 72 hours of a release date. |
| [Nightly builds](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/nightly-builds.yml) | Daily at 00:00 UTC | Builds a nightly ZIP from `trunk` and uploads it to a GitHub release. Runs every day, independent of the release calendar. | Every day. |

## Event-driven workflows

These workflows are triggered automatically by GitHub events such as pull requests being merged, releases being published, or issues being labeled.

### Cherry-pick and backport

| Workflow | Trigger | What it does |
| -------- | ------- | ------------ |
| [Cherry-pick Milestoned PRs to Release Branches](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/cherry-pick-milestoned-prs.yml) | PR closed or milestoned on `trunk` | Cherry-picks from `trunk` to the `release/x.y` branch matching the PR milestone. Uses [`shared-cherry-pick`](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/shared-cherry-pick.yml) for the core logic. |
| [Cherry-pick to Frozen Release](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/cherry-pick-to-frozen.yml) | PR merged on a `release/x.y` branch with the `cherry pick to frozen release` label | Cherry-picks from a `release/x.y` branch to the next (frozen) `release/x.y` branch. Uses [`shared-cherry-pick`](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/shared-cherry-pick.yml) for the core logic. |
| [Cherry Pick to Trunk](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/cherry-pick-to-trunk.yml) | PR merged on a `release/x.y` branch with the `cherry pick to trunk` label | Cherry-picks from a `release/x.y` branch back to `trunk`. Uses [`shared-cherry-pick`](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/shared-cherry-pick.yml) for the core logic. |
| [Block merge if cherry-pick conflicts exist](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-cherry-pick-pr-check-conflicts.yml) | PR events on `cherry-pick-*` branches | Fails CI when the `cherry pick has conflicts` label is present, preventing the PR from being merged until conflicts are resolved and the label is removed. |

### Milestoning

| Workflow | Trigger | What it does |
| -------- | ------- | ------------ |
| [Auto-Add Milestone to Release PRs](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/auto-milestone-release-prs.yml) | PR opened or reopened on a `release/x.y` branch | Assigns the matching milestone to PRs targeting a release branch. |
| [Auto-assign milestone on merge](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/pr-auto-milestone-on-merge.yml) | PR closed on `trunk` | Assigns a milestone to PRs merged to `trunk` that don't already have one. |

### Release events and validation

| Workflow | Trigger | What it does |
| -------- | ------- | ------------ |
| [Release: Release events proxy](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-release-events-proxy.yml) | Release published or prereleased | Delegates to [`release-new-release-published`](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-new-release-published.yml) to run post-release actions: sends Slack notifications, updates the global changelog for stable releases, and calls *Generate Number of Commits and Contributors* for beta releases. |
| [Release checks run](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/tests-on-release.yml) | Release published or edited | Runs the CI test suite against published releases. Also runs nightly on a schedule. |
| ~~[Release: CFE and PRR issue validation](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-cfe-prr-issue-validation.yml)~~ | Issue labeled with `code freeze exception`, `point release request`, `Approved`, or `Rejected` | ~~Validates Code Freeze Exception and Point Release Request issues, applies labels and milestones to the associated PR, and sends Slack notifications.~~ Being deprecated. |

## Manual workflows

These workflows are triggered by the release lead during the release process. They are the workflows referenced in the [building and publishing guide](/docs/contribution/releases/building-and-publishing) and in the release tracking issues.

### Building and publishing

| Workflow | What it does |
| -------- | ------------ |
| [Release: Bump version number](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-bump-version.yml) | Updates version numbers across plugin files and creates a PR against the release branch. |
| [Release: Compile changelog](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-compile-changelog.yml) | Compiles changelog entries and creates PRs against both `trunk` and the release branch. |
| [Release: Build ZIP file](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-build-zip-file.yml) | Builds the release ZIP and optionally creates a draft GitHub release. |
| [Release: Upload release to WordPress.org](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-upload-to-wporg.yml) | Uploads the release ZIP to WordPress.org SVN. |
| [Release: Update stable tag](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-update-stable-tag.yml) | Updates the stable tag on WordPress.org and creates PRs to sync it in the repository. |

### Tracking and analysis

| Workflow | What it does |
| -------- | ------------ |
| [Release: Create Tracking Issue](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-create-tracking-issue.yml) | Creates a Linear tracking issue for a specific release version using the templates in `.linear/`. |
| [Release: Generate Number of Commits and Contributors](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-commits-and-contributors.yml) | Generates release statistics (commit count, contributors list) and sends a Slack notification. |
| [Release: analyze trends (CFEs and PRRs)](https://github.com/woocommerce/woocommerce/blob/trunk/.github/workflows/release-trends-analysis.yml) | Creates GitHub issues requesting AI analysis of Code Freeze Exceptions and Point Release Requests for a milestone. |
