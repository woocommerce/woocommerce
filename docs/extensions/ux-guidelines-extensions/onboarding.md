---
post_title: Onboarding
sidebar_label: Onboarding
---

# Onboarding

Help merchants reach the first useful outcome with as little setup as possible.

## Choose the right starting point

- **Setup is required:** show the next action in the extension's interface. Use a [setup task](./task-list-and-inbox.md#task-list) when it belongs on WooCommerce Home, linking directly to the relevant step.
- **No setup is required:** let merchants use the feature immediately. Use an [Inbox note](./task-list-and-inbox.md#inbox) only for timely, useful information.
- **A post-activation notice is necessary:** show one dismissible notice in the Plugins area with a clear next step. Follow the [notice guidance](./notices.md) for placement and implementation.

Use the supported [setup task and Admin Notes integrations](../extension-onboarding/handling-merchant-onboarding.md), checking availability in the WooCommerce versions you support.

## Guide the work

Give each step one primary action. For multi-step setup, show progress and distinguish required work from optional configuration.

Before connecting a service, explain which account is being connected, why, and what permissions or data access are needed. Show the connection state: Not connected, Connecting, Connected or Reconnect required. After authorization, return to any remaining setup; authorization alone does not mean setup is complete.

Preserve entered values and completed steps when setup is interrupted or a request fails. Offer a [retry or correction step](./notices.md#announcements-dismissal-and-recovery), and mark a setup task complete only when its required outcome is achieved.

## Keep merchants in control

Do not redirect on activation from the Plugins area; this can break bulk activation. Follow [WordPress plugin guideline 11](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#11-plugins-should-not-hijack-the-admin-dashboard) and leave other admin functionality available.

Provide a way back from each step. Let merchants dismiss optional guidance and find it again from the relevant screen.

Use the [workflow testing guidance](./testing.md) to check first use, interruption and retry, and returning to completed setup.
