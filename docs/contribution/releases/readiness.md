---
post_title: Release Readiness and Go/No-Go
sidebar_label: Readiness and Go/No-Go
sidebar_position: 4
---

# Release Readiness and Go/No-Go

Two explicit decision points anchor every release cycle. Both live as checklists in the release tracking sub-issues, so each release leaves a written trail of what was checked, what was decided, and by whom. This page explains what each point is for; the checklists themselves are in the sub-issues the [Release: Assignment workflow](/docs/contribution/releases/workflows) creates.

## Release roles

Each cycle has two named owners, listed on the parent tracking issue:

* **Release lead** (engineering) - runs the release process end to end: builds, publishes, monitors, and executes the run-books.
* **Product DRI** - the product-side counterpart. Joins the readiness review at RC and the go/no-go before stable, and owns the product read on open findings: what blocks the release, what waits for a point release, and what ships with a known-issues note.

The release lead is assigned by rotation via the [Release: Assignment workflow](/docs/contribution/releases/workflows). The Product DRI is confirmed per cycle on the tracking issue.

## Readiness review (at RC)

The RC is the last point where finding a problem is cheap: nothing has shipped, and delaying costs a day, not a revert. The review runs before the RC build starts and answers one question - is there anything we know about that should stop this release?

The checklist covers four areas:

* **Compatibility evidence.** The QIT compatibility regression sweep runs automatically against each prerelease and reports which extension versions the release would break. Introduced issues need a verdict, not just a look.
* **Open findings.** Bug reports, testing threads, and monitoring alerts against the release each get a linked issue and a verdict: release-blocking, fix in a point release, or not a bug.
* **Rollback path.** Who reverts, how, and what revert means for this version - answered before it's needed, not during an incident.
* **Comms.** Changelog in shape, and a known-issues list when verdicts left something open.

## Go/no-go (24-48 hours before stable)

A deliberate decision to ship, made while there is still time to not ship. The release lead and the Product DRI confirm the readiness verdicts still hold and nothing blocking has appeared since the readiness review, then record the decision on the release sub-issue: **go**, **no-go**, or **go with conditions** - with names.

Recorded decisions are the input for release retrospectives and future updates to these checklists.

## For point releases

Point releases have no RC, so both decision points fold into the go/no-go on the release sub-issue: the release lead and the Product DRI run the readiness criteria above over the changes being shipped, then record the decision. An unscheduled point release shipping an urgent fix compresses this further: a quick go/no-go with the Product DRI in `#woo-core-releases`, recorded on the release sub-issue. The criteria for whether an issue warrants a point release at all are in [Release Monitoring](/docs/contribution/releases/monitoring).
