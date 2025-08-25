---
post_title: WooCommerce Release Schedule
sidebar_label: Release Schedule
sidebar_position: 6
---

# WooCommerce Release Schedule

WooCommerce follows a regular release schedule designed to deliver new features, improvements, and fixes approximately every five weeks.

This document outlines the key milestones in our release process, including feature freeze, beta and release candidate phases, and final and patch releases.

For the latest details on upcoming or current releases, please visit our [release calendar](https://developer.woocommerce.com/release-calendar/).

#### Example release cycle
<img src="/img/doc_images/release-cycle.png" width="500" />

## Milestones

### Feature Freeze & `-dev` release (day 1)
As a result of the code freeze automation, a few things happen:

- A dedicated release branch is created (`release/x.y`), where the future release undergoes testing and stabilization. No new features are included on this branch, only bug fixes.
- A `-dev` release is built and made available on GitHub.
- Development of new features continues on `trunk`.
- A pre-release post is published on our developer website ([example](https://developer.woocommerce.com/2025/05/12/woocommerce-9-9-pre-release-updates/)).

### Beta 1 (+1 week)
At this stage, various testing processes take place:

- (Internally) Regression testing with Woo-maintained extensions, regression testing in multiple environments, and exploratory testing (including by contributing teams).
- (Community) The pre-release is announced so the community can [begin testing](/docs/contribution/testing/beta-testing/).

Any issues found during the beta period are addressed either directly against the release branch or by backporting fixes from `trunk` (preferred). Refer to the [backporting guide](/docs/contribution/releases/backporting) for more details.

`release/x.y` remains the source of truth for anything going into the upcoming release.

### Beta 2 (+1 week)
At this stage, we release regression fixes discovered in `-beta.1` as part of release stabilization. The pre-release announcement continues to be updated.

### RC 1 (+1 week)

Last round of (internal) checks before the final release.

If anything is found at this stage, a fix is merged into the release branch (`release/x.y`) as in the beta phase.

### Final Release (+1 day)
We make the stable release version available to everyone.
At this point, the Developer Advocacy team publishes release highlights that are prepared in advance ([example](https://developer.woocommerce.com/2025/06/09/woocommerce-9-9-its-fast-period/)).

### Point/Patch Releases
Patch releases are used to ship important bug fixes to our users, which were  detected after the final release. They are versioned `x.y.z` where `z` is non-zero.

We follow the same convention as during the beta and RC phases for merging any fixes:
- Bugs that are only present on the release branch are fixed against the release branch `release/x.y`.
- Bugs that have a working fix on `trunk` are [backported](/docs/contribution/releases/backporting).


## Delays

Due to business needs or any critical bugs discovered during testing, the release dates may be subject to change.

We do not take this decision lightly and only do so to guarantee the stability of a release. When this happens, we will always communicate the situation ([example](https://developer.woocommerce.com/2025/06/02/woocommerce-9-9-release-is-delayed/)) and update the release calendar.

For details on how a delay is managed, refer to the [release troubleshooting guide](/docs/contribution/releases/troubleshooting#release-delay).
