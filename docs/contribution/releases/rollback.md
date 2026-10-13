---
post_title: Rolling back a release
sidebar_label: Rollback
sidebar_position: 7.5
---

# Rolling back a release

The [readiness review](/docs/contribution/releases/readiness) asks who reverts, how, and what a revert would not undo for this version. Record an owner and verification checks for each action: stopping further updates, rolling back installed code, and recovering changed data.

## The decision

The release lead and Product DRI use the [decision matrix](/docs/contribution/releases/decision-matrix), coordinate in `#woo-core-releases`, and record the verdict with names on the linked issue and release tracking issue. Revert over rush: remove a change when its fix cannot get the original validation. A release-blocking issue found after shipping still needs a point release.

Before selecting a rollback target, record:

- Exact affected and target versions, affected platforms, and the person executing each action.
- Which migrations, settings, or background jobs ran or remain queued. Confirm whether the target code works with the resulting schema and data, and what it cannot restore.
- Whether the target retains required security fixes. Confirm with the security team, following their process, and keep sensitive details in the appropriate restricted channel.
- Evidence for the recovery path and the checks that will confirm checkout, admin, and affected data work again.

## Stop further WordPress.org updates

Follow the [post-stable troubleshooting procedure](/docs/contribution/releases/troubleshooting#a-critical-bug-surfaced-after-the-release-was-marked-stable-on-wordpressorg):

1. Notify the relevant engineering teams and agree on the exact previous known-good version, including the checks above.
2. Run [Release: Update stable tag](https://github.com/woocommerce/woocommerce/actions/workflows/release-update-stable-tag.yml). Enter the target version, check the confirmation, and enable **Revert** (`allow-revert`: "Allow setting stable tag to a lower version (revert)").
3. Check the run summary and the linked WordPress.org `readme.txt`. Merge the generated stable-tag PRs for `trunk` and affected release branches right away; the next build/upload fails if GitHub and SVN disagree.
4. Record the workflow run, merged PRs, target version, and verification time on the tracking issue. If a step fails, investigate before rerunning.

This changes the version offered through WordPress.org; it does not downgrade stores already updated. Update checks and propagation are delayed, so do not promise an immediate stop everywhere. Continue preparing the fix through [Point Releases](/docs/contribution/releases/point-releases).

## Recover installed code and changed data

The owning engineers prepare and validate any source revert or fix for the release branch and `trunk`. The platform owner confirms how an installed site or managed fleet receives it.

For WP Cloud / Atomic, coordinate with the platform team and record the affected deployment group, rollback procedure, and verification owner. If a serious bug appears during staging, follow [Troubleshooting](/docs/contribution/releases/troubleshooting#a-serious-bug-was-detected-during-internal-checks--monitoring): request a staging revert, pause the release, record the block, and retain unpublished draft releases. Once the fix is merged, create a new tracking issue for the new version and follow the release procedure.

Database changes need a release-specific assessment. Compatible migrations may allow older code to run; destructive changes may leave data missing after a downgrade. In the [11.2 variation-image investigation](https://github.com/woocommerce/woocommerce/pull/69631), rolling back code did not restore removed image metadata. That PR was closed without merging and is not a shipped recovery procedure.

If data repair is needed, the owning team must provide a tested procedure that accounts for subsequent store activity. Record what can be restored and what remains lost or unknown. Where rollback is unsafe or insufficient, agree on a validated workaround or fix-forward release. Do not infer a database restore procedure from a code revert.

## Check upgrade errors

The [10.9.1 reports](https://github.com/woocommerce/woocommerce/issues/66031) include both an error confined to the update request and an error persisting after it. Check whether the update finished, whether a fresh request still fails, and whether checkout and admin work. Record the exact error, versions, update method, and ongoing impact.

- If the error occurred only during the update and fresh requests work, record that evidence and monitor. A recovery-mode email alone does not establish an ongoing outage.
- If the error persists, escalate to the owning engineers and host/platform team. A [follow-up report](https://github.com/woocommerce/woocommerce/issues/66031#issuecomment-4916523363) describes recovery after refreshing stale OPcache. Confirm the diagnosis and the host's supported procedure before applying it; this report is not a universal restart or cache-reset runbook.
- If the cause or recovery remains uncertain, use the decision matrix based on the actual impact. A persistent outage must not be classified as self-healing.

## Comms

Post the decision and progress in `#woo-core-releases`, with links to the tracking issue and recovery guidance. Coordinate public release-note updates with Developer Advocacy and merchant guidance with support and the owning team. State separately what stores still awaiting the update should do and what already-updated stores should do. Continue [release monitoring](/docs/contribution/releases/monitoring), recording the owner, follow-up window, and unresolved symptoms.
