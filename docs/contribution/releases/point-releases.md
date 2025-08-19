---
post_title: WooCommerce Point Releases
sidebar_label: Point Releases
sidebar_position: 2
---

# Point Releases

## What Are Point Releases?

Point releases are patch releases that address specific issues without adding substantial new functionality. Point releases typically contain:

- **Critical bug fixes** affecting store functionality or checkout processes
- **Security patches** for urgent vulnerabilities
- **Compatibility fixes** for WordPress, theme, or plugin conflicts

## The Point Release Requests (PRR) flow

The **Point Release Request (PRR) flow** is a structured process for requesting and managing critical fixes that need to be included in WooCommerce point releases. This process ensures that urgent bug fixes can be safely incorporated into current stable release and automatically forward-port them to trunk and any frozen branches, preserving code quality, enforcing thorough reviews, and preventing regressions.

**⚠️ Important:** Security Vulnerability reports must not go through the PRR flow. All potential security issues should be reported privately via Automattic’s HackerOne program: [https://hackerone.com/automattic/](https://hackerone.com/automattic/).

### Step-by-Step Process

#### 1a. Initial Issue Creation

To ensure the release lead is aware of all planned fixes to be included in the next point release, it is important to create either an issue or PR as soon as a bug is discovered and planned as a patch fix.  This will help reduce the number of patch releases that need to be created.

If the initial PR may take more than a few hours to create, please create an issue and set the milestone of the issue to targeted release. E.g. use milestone `10.1.0` for a new point release request for `10.1.x`.

#### 1b. Initial Pull Request Creation

**Author Action**: Create a pull request against the release branch (`release/x.y`) instead of the trunk branch, following the standard PR creation process.

- The PR should target the specific release branch (e.g., `release/9.5` for an issue found on WooCommerce 9.5.x)
- Include a regular changelog file as you would for trunk PRs
- Ensure all standard PR requirements are met (description, testing, etc.)
- Ensure that the PR has a milestone set to the target release so it can be tracked by the release lead, e.g. use milestone `10.1.0` for a new point release request for `10.1.x`.

#### 2. Point Release Request Submission

**Author Action**: Submit a PRR using the [Point Release Request template](https://github.com/woocommerce/woocommerce/issues/new?template=new-prr-template.yml).

Provide the required PRR template information:

**Required Fields:**

- **PR URL**: The pull request URL against the release branch
- **Justification**: Why this PR needs a point release
- **Impact Assessment**: Consequences if the fix is not included (number of users affected and how)
- **Contingency Plan**: What to do if defects are discovered after the point release
- **Communication Plan**: How the change should be communicated in the release blog post
- **Workaround**: Any available workarounds and how to communicate them
- **Alternative Contact**: Who to contact if the author is unavailable

#### 3. Release-Lead Review

After a PRR is opened, the **release lead** evaluates it.  
When deciding whether to approve a PRR, the release lead should consider the following:

| Evaluation Criterion | Guidance |
| -------------------- | -------- |
| **Scope of Impact**  | How many stores are already affected? Larger reach increases urgency. |
| **Error Commonality** | Does the problem stem from a widely-used core flow, plugin, or theme? Issues in common components usually merit faster action. |
| **Workarounds**      | Is there an easy, documented workaround (e.g., a filter, setting toggle, or temporary feature disable) that store owners can apply? Readily available workarounds lower the need for a point release. |
| **Impact Severity**  | Does the bug block critical commerce functionality (checkout, payments, product visibility)? The more business-critical the failure, the higher the priority. |

#### 4. Approval or Rejection

| Outcome | Release-Lead Action | Workflow Triggered |
|---------|--------------------|--------------------|
| **Approve** | Apply the **`Approved`** label to the PRR issue and optionally leave a short rationale referencing the criteria above. | Labels are automatically added to the PR (“cherry pick to trunk”, “cherry pick to frozen release”); the issue milestone is set to the current release; the PRR is commented with an approval note. |
| **Reject** | Apply the **`Rejected`** label and briefly state the reason (e.g., limited impact, simple workaround available). | A workflow adds a comment, closes the PRR, and the author must retarget the PR to `trunk`, resolve conflicts, and merge through the normal path. |


#### 5. Merge to Release Branch (Release Lead / Core Contributor)

- **Verify cherry-pick requirements**
    - Check whether the fix is already included in `trunk` and/or the next frozen branch.
    - If the fix *should not* be forward-ported, remove the labels `cherry pick to trunk` and `cherry pick to frozen release`.

- **Merge the PR**
    - After reviewing the labels, merge the PR into the current `release/x.y` branch.
    - Confirm that the changelog entry and milestone are correct.

- **Resulting automation**
    - If either cherry-pick label remains, GitHub Actions opens follow-up PRs to `trunk` and/or the frozen release branch.
    - If both labels were removed, no cherry-pick workflows run.

#### 6. Review & Merge Follow-up PRs (Release Lead)

After the primary fix is merged into `release/x.y`, the labels that remain on the PR determine what happens next:

| Label present | Automation result | What the release lead must do |
|---------------|------------------|------------------------------|
| `cherry pick to trunk` | Action opens a new PR targeting `trunk` and adds the current milestone. | Review tests / CI and merge this PR. |
| `cherry pick to frozen release` | Action opens a new PR targeting the **next frozen branch** (e.g., `release/9.6`) and adds the milestone. | Review and merge this PR as well. |

Both follow-up PRs **must be merged before the point-release tag is cut**.  
If either cherry-pick is not required, ensure its label was removed during Step 5 so no unnecessary PR is generated.

#### 7. Publish the Point Release (Release Lead)

Once all required PRs are merged into:

- `release/x.y` (current maintenance branch)
- `trunk` (future feature branch)
- *optional* frozen branch (`release/x.y+1`)

the **release lead** creates and publishes the new point release that contains every approved PRR since the last shipment.

Follow the established [WooCommerce release process](/docs/contribution/releases/building-and-publishing).
