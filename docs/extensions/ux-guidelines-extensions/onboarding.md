---
post_title: Onboarding
sidebar_label: Onboarding
---

# Onboarding

Help merchants reach the first useful outcome with as little setup as possible. Make the next step clear, and let returning merchants resume without repeating completed work.

## Choose the right starting point

- **Setup is required:** show the next action in the extension's interface. Use an actionable [setup task](./task-list-and-inbox.md#task-list) when it belongs on WooCommerce Home, linking directly to the relevant step.
- **No setup action is required:** let merchants start using the feature. Explain it in context; use an [Inbox note](./task-list-and-inbox.md#inbox) only when the information is timely, relevant and useful. Do not create a task simply to announce a feature.
- **A post-activation notice is necessary:** provide one dismissible notice in the Plugins area, with a short explanation and a clear next step. Do not display it on the dashboard or unrelated screens. Follow the [notice guidance](./notices.md) for the host's PHP or React implementation.

Use the supported [setup task and Admin Notes integrations](../extension-onboarding/handling-merchant-onboarding.md), checking availability in the WooCommerce versions you support. These integrations place the task or note; choose controls using the shared [admin component guidance](./README.md#admin-components).

Compare the [task list examples](./task-list-and-inbox.md#task-list) with the [Inbox examples](./task-list-and-inbox.md#inbox): a task points to work to complete, while an Inbox note provides useful information.

## Guide the work

Use one clear primary action for the current step. If setup has several steps, show progress and distinguish required work from optional configuration.

Before connecting a service, explain which account is being connected, its purpose, requested permissions and relevant data use. Make connection state clear: Not connected, Connecting, Connected or Reconnect required. Successful authorization may still leave required setup choices; return the merchant to the relevant task with completed work preserved.

Keep copy instructional and in sentence case. Use the host's components and [semantic styling](./colors.md), without turning empty states or setup screens into brand promotions. An empty state should explain what belongs there and offer the next useful action.

Preserve entered values and completed steps when a request fails. Explain what needs attention, provide a retry or correction step, and let merchants resume. Follow the [feedback and recovery guidance](./notices.md#announcements-dismissal-and-recovery). Mark a setup task complete when its required outcome is achieved, rather than when its link is opened.

## Keep merchants in control

Do not redirect on activation from the WordPress Plugins area; this can break bulk activation. Follow [WordPress plugin guideline 11](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#11-plugins-should-not-hijack-the-admin-dashboard) and leave other admin functionality available.

Provide a way forward or back from every step. Let merchants dismiss optional guidance and find it again from the relevant screen. Avoid onboarding emails, push notifications and welcome tours as substitutes for clear in-product guidance.

Check the complete journey: activation, discovery, setup, interruption, retry, completion and return. Confirm setup links use the [chosen navigation location](./navigation.md), and follow the [Task List and Inbox rules](./task-list-and-inbox.md) so the same message is not repeated across several surfaces.
