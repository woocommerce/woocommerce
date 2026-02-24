# [{release_main_version}] Release tracking

This issue provides visibility on the progress of the release process of WooCommerce core **{release_main_version}**.

- **Main version being released:** `{release_main_version}`
- **Milestone:** [{release_milestone}]({repository_url}/issues?q=is:open+milestone:{release_milestone})
- **Release branch:** [`{release_branch}`]({repository_url}/tree/{release_branch})
- **Release lead:** {lead_user} ({lead_team})
- **Relevant dates:** ([release calendar](https://developer.woocommerce.com/release-calendar/))
  - **Feature Freeze:** {date_feature_freeze}
  - **Beta 1:** {date_beta1}
  - **Beta 2:** {date_beta2}
  - **RC 1:** {date_rc1}
  - **Stable:** {date_stable}

---

⚠ Dear release lead:

- Please read this issue carefully and familiarize yourself with the [release process documentation](https://developer.woocommerce.com/docs/contribution/releases/).
- Join the release channels in Slack (`#woo-core-releases`, `#woo-core-releases-notifications`), where discussions happen and notifications are sent.
- For every release in the cycle, there's a corresponding sub-issue. On the date of each release (see schedule above), open the relevant issue and follow the instructions in it.
- Any additional point/patch releases after the first stable must be tracked as well. Run the **[Release: Create Tracking Issue]({repository_url}/actions/workflows/release-create-tracking-issue.yml)** workflow with the version (e.g., `{release_main_version}.1`) to create the sub-issue.

##### Resources

- [Release process documentation](https://developer.woocommerce.com/docs/contribution/releases/).
- [Troubleshooting Guide](https://developer.woocommerce.com/docs/contribution/releases/troubleshooting/).
- [Previous releases]({repository_url}/releases).
- [WooCommerce core changelog]({repository_url}/blob/trunk/changelog.txt).

