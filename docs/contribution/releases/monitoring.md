---
post_title: WooCommerce Release Monitoring
sidebar_label: Release Monitoring
sidebar_position: 7
---

# WooCommerce Release Monitoring

After the release, the release lead should continue to monitor the following for any bugs directly related to the latest version.  Monitoring should continue for 3 days after a major release and 1 day for a point release.

If there are questions whether a specific issue is critical enough to warrant creating a new Point Release Fix, please start a discussion WooCommerce Slack [#core-development](https://woocommercecommunity.slack.com/archives/C4TNYTR28).

## WordPress.org Forums

Check newly created threads on the [WordPress.org Forums](https://wordpress.org/support/plugin/woocommerce/) for any issues that appear to be caused by the latest update.

## WooCommerce GitHub Repository Issues

Watch the [Newest Created Issues](https://github.com/woocommerce/woocommerce/issues?q=is%3Aissue%20state%3Aopen%20sort%3Acreated-desc) and verify that none are critical.

## Handling Critical Issues: Point Release Requests

If monitoring uncovers a bug that **cannot wait** for the next scheduled release, initiate a **Point Release Request (PRR)**.  
The PRR workflow lets the release lead fast-track a fix into the current maintenance branch and, when necessary, automatically cherry-pick it to trunk and the next frozen branch.

[Read the full Point Release guide](/docs/contribution/releases/point-releases).

Before opening a PRR, confirm that the issue:

1. **Impacts core store functionality** (e.g., checkout, orders, taxes).
2. **Affects a significant number of sites** or stems from a widely-used extension or theme.
3. **Lacks a reasonable workaround** that merchants can apply themselves.

If these conditions are met, follow the PRR guide to create the request, provide the required justification, and notify the release lead for approval and merge.
