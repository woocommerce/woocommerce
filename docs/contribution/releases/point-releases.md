---
post_title: WooCommerce Point Releases
sidebar_label: Point Releases
sidebar_position: 2
---

# Point Releases

:::important
The formal Point Release Request (PRR) issue template ([`new-prr-template.yml`](https://github.com/woocommerce/woocommerce/blob/trunk/.github/ISSUE_TEMPLATE/new-prr-template.yml)) is no longer required for point release fixes; it is preserved only for reference. In practice, coordinating directly with the release lead is sufficient to get fixes included in a point release, and is the most important step. The rest of this document covers what types of fixes qualify for point releases and how to prepare them in the repository.
:::

Point releases are patch releases that address specific issues in an already-shipped WooCommerce version (for example `9.9.0 → 9.9.1`) without adding new functionality. They apply only to versions that are already in customer production environments.

## What qualifies as a point release

Changes appropriate for a point release are:

- **Critical bug fixes** affecting store functionality (checkout, orders, payments, product visibility).
- **Security patches** for urgent vulnerabilities.
- **Severe performance regressions** introduced by the shipped release.
- **Compliance fixes** required for regulatory or legal reasons.
- **Compatibility fixes** for WordPress, theme, or plugin conflicts that are breaking stores.

The following are **not** point release material and should ride the next regular release instead:

- New features or enhancements.
- Non-critical bug fixes.
- Code refactoring or cleanup.
- Documentation updates.

In all cases, point releases must remain backward compatible. No breaking changes are allowed in a patch.

⚠️ Security vulnerability reports must **not** go through this flow. Report them privately via Automattic's HackerOne program: [https://hackerone.com/automattic/](https://hackerone.com/automattic/).

## Evaluating whether to ship a point release

Use your best judgement based on the urgency and severity of the outstanding issue. The release lead and the reporter should weigh:

| Criterion | Guidance |
| --- | --- |
| **Scope of impact** | How many stores are already affected? Larger reach increases urgency. |
| **Error commonality** | Does the problem stem from a widely-used core flow, plugin, or theme? Issues in common components usually merit faster action. |
| **Workarounds** | Is there an easy, documented workaround (a filter, setting toggle, or temporary feature disable) that store owners can apply? Readily available workarounds lower the need for a point release. |
| **Impact severity** | Does the bug block critical commerce functionality (checkout, payments, product visibility)? The more business-critical the failure, the higher the priority. |

Some practical timing notes:

- If the issue is not very urgent, consider waiting 3–4 days to see if additional related issues are reported before proceeding. This consolidates fixes and reduces the number of patch releases.
- For high-severity or critical issues, prioritize releasing as soon as possible.
- For security issues, coordinate with the team that implemented the fix to help determine urgency if it is not clear.
- Consider whether other known issues already being worked on could be included in the same release.

## Process

The work is split between the **fix author** and the **release lead**. These tasks can happen in parallel. The author does not need to wait for the tracking issue before opening a PR, and the release lead can create the tracking issue independently once a point release is on the table.

### For the fix author: prepare the fix

Two paths are acceptable. The first is preferred when the fix applies cleanly to both branches.

- **Preferred:** merge to `trunk` with the release milestone set. Open the PR against `trunk` as normal and set its milestone to the release series (e.g. `X.Y.0`). WooCommerce milestones use the `.0` form for the whole series, so a fix targeting `X.Y.1` still uses milestone `X.Y.0`. The release lead picks it up from there and ports it to the release branch.
- **Alternative:** PR directly against the `release/X.Y` branch. Use this when the fix doesn't apply cleanly to `trunk` or the branches have diverged. Include a changelog entry, set the milestone to `X.Y.0`, and apply the `cherry pick to trunk` and/or `cherry pick to frozen release` labels as appropriate so the fix is forward-ported automatically after merge. Any cherry-pick PRs that get generated must also be reviewed and merged before the release is published; they share the same milestone as the original fix.

Get the PR reviewed and merged following the normal review process. Once it lands, the rest is on the release lead.

### For the release lead: create the tracking issue and cut the release

Run the [`Release: Create Tracking Issue`](https://github.com/woocommerce/woocommerce/actions/workflows/release-create-tracking-issue.yml) workflow for the target point release version (e.g. `9.9.1`). Do **not** reuse an existing tracking issue, even if a previous release in the same series was blocked. The workflow automatically nests the new Linear issue under the `[X.Y] Release tracking` parent for that release series, so the entire release series remains a single tree in Linear.

The tracking issue contains the version-specific checklist for cutting and publishing the release. Follow it from there. For underlying mechanics (workflows, draft releases, stable tag updates), refer to [Building and Publishing](/docs/contribution/releases/building-and-publishing).
