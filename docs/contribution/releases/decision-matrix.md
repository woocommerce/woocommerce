---
post_title: Release Decision Matrix
sidebar_label: Decision Matrix
sidebar_position: 5
---

# Release Decision Matrix

Every finding against a release gets a verdict - at the readiness review, at the go/no-go, and when weighing whether an already-shipped issue warrants a point release. This page is the criteria for picking that verdict. The decision points themselves are described in [Readiness and Go/No-Go](/docs/contribution/releases/readiness); the criteria apply to Core and Woo-owned extension releases, for all release types.

The matrix guides the people making the call - the release lead and the Product DRI. It does not change which automated checks gate a build.

## Verdicts

| Verdict | Meaning |
| --- | --- |
| **Release-blocking** | The release does not ship with this issue in it: hold the date or remove the change. Found after shipping: point release now. |
| **Fix in a point release** | Ship on schedule. The fix has an issue, an owner, and a target release *before* the go decision - without those, it re-enters as release-blocking. |
| **Next release** | Ship. The fix rides the next scheduled release. |
| **Not a bug** | Expected behavior, or an invalid finding. |

A finding no one can reproduce - tried on at least two independent stacks - gets **Next release, with a named owner**: it does not block the release, and the issue stays open rather than being closed as not a bug. A finding that reproduces anywhere keeps its impact-class verdict.

## Default verdict by impact class

| Impact class | Default verdict | Example |
| --- | --- | --- |
| Checkout or revenue interruption - a store cannot take payments | Release-blocking; no modifiers apply | - |
| Site down or fatal on load, in Core or any Woo-owned extension - including older extension versions running with the new Core | Release-blocking | [#64394](https://github.com/woocommerce/woocommerce/pull/64394) forced the revert after 10.9.0; [#65957](https://github.com/woocommerce/woocommerce/pull/65957) rescheduled 11.0.0 after a fatal surfaced on an early deployment |
| Data loss or corruption | Release-blocking | - |
| A break in a public contract or shared surface with consumers in the wild - PHP API, hooks, REST, datastore behavior | Release-blocking | [#65595](https://github.com/woocommerce/woocommerce/pull/65595) changed datastore behavior extensions relied on and forced a revert; [#66382](https://github.com/woocommerce/woocommerce/pull/66382) removed released public methods, restored within a day by [#66822](https://github.com/woocommerce/woocommerce/pull/66822) |
| A contract change whose surface has no known consumers, or that went through the deprecation path in the repository's [`AGENTS.md`](https://github.com/woocommerce/woocommerce/blob/trunk/AGENTS.md) | Next release | - |
| WordPress forward-compatibility - the release breaks under a WordPress version shipping before our next release | Fix in a point release scheduled before the WordPress date; release-blocking if the breakage is site-down class | [#67061](https://github.com/woocommerce/woocommerce/pull/67061) shipped WordPress 7.1 fixes inside the 11.0.0 rebuild, because 7.1 lands before 11.1.0 |
| Broken upgrade path - the update itself fails or leaves a site inconsistent | Release-blocking if deterministic; fix in a point release if transient and self-healing | The 10.9.1 upgrade race self-healed on cache refresh and produced a single report - no point release was needed |
| A regression in a default-on, merchant-facing feature | Release-blocking without a workaround; fix in a point release with one | - |
| A regression behind a flag that is off by default | Next release | An 11.0 REST API field inconsistency was judged non-blocking because the flag is off by default, with the rename scheduled for a later release |
| A performance regression | Release-blocking at scale with no mitigation; otherwise fix in a point release with the mitigation named | [#66088](https://github.com/woocommerce/woocommerce/pull/66088) had a filter mitigation available immediately; the fix ([#66786](https://github.com/woocommerce/woocommerce/pull/66786)) was backported into the next build |
| Cosmetic and UX papercuts | Next release | - |

Security issues follow the security team's process, not this matrix. Sensitive fixes may ship as a dedicated point release immediately after stable - coordinate with the security team.

## Modifiers

A modifier moves a verdict one level, never past the floor: **checkout, data, and site-down verdicts do not demote.** A verdict moves at most one level in total: when a row already prices in a modifier's condition - as the off-by-default flag row does - that modifier does not apply again; the more specific row wins. A fatal with a documented workaround is still a fatal for every store that hits it before reading the workaround.

- **Feature flags, by trajectory.** A flag that is off by default demotes the verdict one level - unless the flag is scheduled to default on in the next release, in which case no demotion applies: for release purposes it is already on. When nobody can state the flag's trajectory, the demotion applies, the verdict records the assumption ("assumes the flag stays off through next cycle"), and the unanswered question goes to the feature's owner.
- **Blast radius.** How many stores are exposed, and how fast. Managed hosting platforms can deploy an update to every site at once rather than following WordPress.org's staged rollout, so managed-fleet exposure weighs heavier than install counts alone. This extends the scope and commonality criteria in [Point Releases](/docs/contribution/releases/point-releases).
- **Workaround.** A documented workaround that merchants can apply themselves demotes one level - never past the floor. This matches the workaround criterion in [Point Releases](/docs/contribution/releases/point-releases).

## Response times

| Verdict | Response time |
| --- | --- |
| Release-blocking | The verdict is recorded the same day the finding is raised; the release does not proceed to the next run-book step until it is. |
| Fix in a point release | The fix is scheduled - issue, owner, target release - before the go decision. For findings that surface after shipping: within the [monitoring window](/docs/contribution/releases/monitoring) (3 days after a major release, 1 day after a point release). |
| Next release | The issue is filed and milestoned when the verdict is recorded: before the go decision for findings against an unshipped release, within the same monitoring window for findings that surface after shipping. |
| Not a bug | The verdict and the reasoning are recorded on the linked issue when it is closed. |

These are verdict-and-scheduling times, not fix-delivery guarantees.

## Decision rules

1. **Revert over rush.** When a fix cannot get the same validation the original change had before the release date, remove the change instead of patching under pressure.
2. **Silence is not a pass.** Every signal read at the readiness review and go/no-go has a named responder. An unreviewed signal counts as missing, not green.
3. **Verdicts are recorded, with names.** The release lead and the Product DRI record each verdict on the linked issue. Disputes over a classification are argued and settled on that issue, so the reasoning is written down where the verdict lives.
4. **The same matrix applies to extensions**, in both directions of the Core-extension version pairing. Extensions maintained by partners route through the owning team's channel.
