---
post_title: WooCommerce Release Monitoring
sidebar_label: Release Monitoring
sidebar_position: 7
---

# WooCommerce Release Monitoring

After the release, the release lead should continue to monitor the following for any bugs directly related to the latest version.  Monitoring should continue for 3 days after a major release and 1 day for a point release.

If there are questions whether a specific issue is critical enough to warrant a new point release, please start a discussion in the `#woo-core-releases` Slack channel.

## WordPress.org Forums

Check newly created threads on the [WordPress.org Forums](https://wordpress.org/support/plugin/woocommerce/) for any issues that appear to be caused by the latest update.

## WooCommerce GitHub Repository Issues

Watch the [Newest Created Issues](https://github.com/woocommerce/woocommerce/issues?q=is%3Aissue%20state%3Aopen%20sort%3Acreated-desc) and verify that none are critical.

## Extension Fatal-Error Alerts

Every Woo-owned extension has a Grafana fatal-error alert on WP Cloud, backed by the internal *Woo\* Fatals on WP Cloud* dashboard. Alerts post to the owning team's channel and to `#woo-core-releases-notifications`. Watch that channel during the monitoring window.

When an alert fires, open the linked dashboard panel to see how many sites are affected, then reply on the alert to ping the owning team - triage is theirs. If the fatal traces back to the release, treat it as a candidate critical issue (see below).

An old extension version can point to a compatibility issue, but it does not prove the release caused the fatal. Compare the affected WooCommerce and extension versions before calling it a non-regression.

## Handling Critical Issues

If monitoring uncovers a bug that **cannot wait** for the next scheduled release, plan a point release. Before doing so, confirm that the issue:

1. **Impacts core store functionality** (e.g., checkout, orders, taxes).
2. **Affects a significant number of sites** or stems from a widely-used extension or theme.
3. **Lacks a reasonable workaround** that merchants can apply themselves.

If these conditions are met, follow the [Point Releases guide](/docs/contribution/releases/point-releases) to create a tracking issue, prepare the fix, and ship the patch.
