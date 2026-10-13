---
post_title: Rolling back a release
sidebar_label: Rollback
sidebar_position: 7.5
---

# Rolling back a release

Plan the rollback during the [readiness review](/docs/contribution/releases/readiness). Name who handles distribution, installed code, and data recovery, and how each will verify the result.

## Before reverting

The release lead and Product DRI use the [decision matrix](/docs/contribution/releases/decision-matrix) and record the verdict with names on the issue and release tracking issue. Remove a change if its fix cannot get the original validation.

- Record the affected and target versions, platforms, and executor for each action.
- Check that the target code works with completed or queued migrations, settings changes, and background jobs.
- Confirm with the security team that the target retains required security fixes. Follow their process and keep sensitive details restricted.
- Agree on checks for checkout, admin, and affected data.

## Stop further WordPress.org updates

Follow the [post-stable troubleshooting procedure](/docs/contribution/releases/troubleshooting#a-critical-bug-surfaced-after-the-release-was-marked-stable-on-wordpressorg):

1. Notify the owning teams and agree on the previous known-good version, using the checks above.
2. Run [Release: Update stable tag](https://github.com/woocommerce/woocommerce/actions/workflows/release-update-stable-tag.yml) with that version, confirmation checked, and **Revert** (`allow-revert`) enabled.
3. Check the run summary and linked WordPress.org `readme.txt`. Merge the generated PRs for `trunk` and affected release branches immediately; mismatched GitHub and SVN stable tags block the next build/upload.
4. Record the run, merged PRs, target version, and verification time on the tracking issue. Investigate failures before rerunning.

This does not downgrade stores already updated, and propagation takes time. A release-blocking issue found after shipping still needs a [point release](/docs/contribution/releases/point-releases).

## Recover installed code and data

Owning engineers validate a revert or fix for the release branch and `trunk`. The platform owner confirms how stores receive it. For WP Cloud / Atomic, agree on the deployment group, rollback steps, and verification owner with the platform team.

For staging failures, [follow the staging procedure](/docs/contribution/releases/troubleshooting#a-serious-bug-was-detected-during-internal-checks--monitoring): request a revert, pause the release, record the block, and keep unpublished draft releases. After the fix, use a new version and tracking issue.

Rolling back code does not restore deleted data. The owning team must test any repair, account for store activity since the update, and record what remains lost or unknown. If rollback is unsafe or insufficient, use a validated workaround or fix-forward release.

## Check upgrade errors

Check whether the update finished, fresh requests fail, and checkout and admin work. Record the error, versions, update method, and ongoing impact.

If the update finished and fresh requests work, monitor. A recovery-mode email alone does not establish an outage. If errors persist, escalate to the owning engineers and host. One [report](https://github.com/woocommerce/woocommerce/issues/66031#issuecomment-4916523363) describes recovery after refreshing OPcache; confirm the diagnosis and the host's supported procedure first.

## Comms

Post updates in `#woo-core-releases` with the tracking issue and recovery guidance. Coordinate release notes with Developer Advocacy and merchant guidance with support and the owning team. Give separate guidance for stores awaiting the update and those already updated. Assign an owner and follow-up window for [release monitoring](/docs/contribution/releases/monitoring), including unresolved symptoms.
