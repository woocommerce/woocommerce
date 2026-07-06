---
post_title: Onboarding
sidebar_label: Onboarding
sidebar_position: 2
---

# Onboarding

The first experience your users have with your extension is crucial. A user activating your extension for the first time provides an opportunity to onboard new and reorient returning users the right way.

Is it clear to the user how to get started? Keep in mind that the more difficult the setup, the more likely a user will abandon the product altogether, so keep it simple and direct.

## Onboarding best practices

Onboarding experiences welcome merchants and present the basics of your extension as quickly as possible so they don't have to discover them on their own.

### Do: Guide merchants to successful setup

Provide a clear next step if the extension isn't configured or if setup is not complete. Use primary buttons as calls to action and keep secondary information deprioritized for clarity. Avoid dead end links and pages. There should always be a way forward or back.

![Setup guide example](/img/doc_images/Setup-guide.png)

### Do: Use progress indicators for multi-step setup

If setup requires multiple actions, show merchants where they are in the process and how many steps remain. Keep the next action visible and easy to complete.

![Progress indicator example](/img/doc_images/progress-indicator.png)

### Don't: Promote your product in onboarding

Get to the point and keep it instructional. This is not a time to promote your brand or pitch the product. The user has bought your product and is ready to use it.

Keep the information instructional and precise and avoid the use of branded colors, fonts, and illustrations in empty states and other onboarding aids.

### Don't: Automatically redirect upon activation

Plugins should not redirect on activation from the WordPress plugins area. This can break bulk activation of plugins. Following the [dotorg plugin guideline 11](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#11-plugins-should-not-hijack-the-admin-dashboard), the extension shouldn't hijack the dashboard or hide functionality of core or other extensions.

### Do: Handle errors with clear messaging

If users encounter an error during setup, provide a clear and useful notification with clear and easily understood information on what went wrong and how to fix it. Refer to the [notices guidelines](/docs/extensions/ux-guidelines-extensions/notices/) for more information.

![Plugin activation notice example](/img/doc_images/plugins-notice.png)

### Plugins notice

If necessary, provide a dismissible notification in the plugin area. Add a notification to communicate next steps if setup or connection is required to successfully enable the plugin.

Notice guidelines:

- Use the standard WordPress notice format and WooCommerce admin notices API.
- Notices should be dismissible. Users should always have a clear way to close the notice.
- Keep the post-activation notice within the WordPress plugin area in context of the plugin listing; do not display it on the dashboard, or any other parts of the platform.
- Don't display more than one notice.
- Try to keep the notice copy between 125 to 200 characters.

### Do: Keep it short

Present only the required steps in the onboarding to avoid overwhelming users and causing dropoff. Any additional configuration can be placed in settings.

### Do: Make it dismissible

If your onboarding isn't essential, make it dismissible.

### Do: Use the Inbox to help merchants discover new features

For informational notices and other information that doesn't require action, the Inbox is a great place to reach merchants. Refer to the [task list and inbox guidelines](/docs/extensions/ux-guidelines-extensions/task-list-and-inbox/) for more information.

### Do: Use the task list for additional required actions

The task list is an extensible area of WooCommerce to notify merchants if additional information is required to complete setup. Refer to the [task list and inbox guidelines](/docs/extensions/ux-guidelines-extensions/task-list-and-inbox/) for more information.

![Helpful empty state example](/img/doc_images/empty-states.png)

### Do: Show helpful empty states

Rely on the existing plugin UI, if any, to guide users towards successful setup and use of the plugin. Avoid onboarding emails, push notifications, and welcome tours.
