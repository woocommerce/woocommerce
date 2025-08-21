---
post_title: WooCommerce Release Schedule
sidebar_label: Release Schedule
sidebar_position: 6
---

# WooCommerce Release Schedule

The schedule can be found on [this page](https://developer.woocommerce.com/release-calendar/), which also explains the types 
of events in the calendar like `Releases`, `Release Candidates (RC)` and `Feature Freeze Dates`.

While the calendar reflects the events made public, there are specific steps in the release process that are internal, 
and this page aims to provide necessary context about those events.

## Detailed release schedule

This section will use the publicly available release schedule as anchors and clarify where the internal events fit in.

### Feature Freeze (start of the release cycle)

This step is mostly automated and creates a dedicated release branch where the future release undergoes testing and stabilization.
At this point, the Developer Advocacy team publishes pre-release updates ([example](https://developer.woocommerce.com/2025/05/12/woocommerce-9-9-pre-release-updates/))

### RC1 (Feature Freeze + 1 week)

This step is where various testing processes are happening:

- internally: regression testing with Woo maintained extensions, regression testing in multiple environments, and exploration testing (including by the contributing teams).
- community: the Developer Advocacy team updates the pre-release announcement so the community can [begin testing](/docs/contribution/testing/beta-testing/) the release.

### RC2 (RC1 + 1 week)

This step is where we release regressions fixes discovered in RC1, as part of the release stabilization.
At this point, the Developer Advocacy team continues to update the pre-release announcement.

### Final Release (RC2 + 1 week)

This step is where the stable release version becomes available to everyone.
At this point, the Developer Advocacy team publishes release highlights that are prepared in-advance ([example](https://developer.woocommerce.com/2025/06/09/woocommerce-9-9-its-fast-period/)).

## Delays

Due to business needs, the release dates may be subject to change. Below we will describe some of the internal processes for how we wrangling this situation.

Once the need for changes in the release schedule is confirmed, the Woo team creates an internal Slack thread to communicate the necessary details. This thread provides an opportunity for teams to share additional context, which may help verify or challenge schedule changes.

Once the feedback and release schedule changes have been finalized, we procced to:

- ask the Developer Advocacy team to communicate the changes publicly ([example](https://developer.woocommerce.com/2025/06/02/woocommerce-9-9-release-is-delayed/))
- update [the calendar](https://developer.woocommerce.com/release-calendar/) with the new release dates

> Note: To reduce disruption for internal teams and contributors, we avoid changing the time between release candidates (RCs) and the final release. Instead, we adjust the overall release schedule. These intervals are based on several factors, including team capacity.
