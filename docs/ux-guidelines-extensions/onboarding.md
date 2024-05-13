---
post_title: WooCommerce Extension Guidelines - Onboarding
menu_title: Onboarding
---

The first experience your users have with your extension is crucial. A user activating your extension for the first time provides an opportunity to onboard new and reorient returning users the right way. Is it clear to the user how to get started? Keep in mind that the more difficult the setup, the more likely a user will abandon the product altogether so keep it simple and direct.

**Use primary buttons as calls to action and keep secondary information deprioritized for clarity**. Guide merchants towards successful setup with a clear next step and/or step-by-step process with progress indicator if the extension isn't configured or if setup is not complete.

**If necessary, provide a dismissible notification in the plugin area**. Add a notification to communicate next steps if setup or connection is required to successfully enable the plugin.

- Use the standard WordPress notice format and WooCommerce admin notices API.
- Notices should be dismissible. Users should always have a clear way to close the notice.
- Keep the post-activation notice with the WordPress plugin area in context of the plugin listing-do not display it on the dashboard, or any other parts of the platform.
- Don't display more than one notice.
- Try to keep the notice copy between 125 to 200 characters.

If no action is required for setup it's best to rely on other onboarding aids such as the Task List (link to component) and Inbox (link to component) to help users discover features and use your plugin.

**Get to the point and keep it instructional**. This is not a time to promote your brand or pitch the product. The user has bought your product and is ready to use it. Keep the information instructional and precise and avoid the use of branded colors, fonts, and illustrations in empty states and other onboarding aids. Help users with context on next steps.

**Show helpful empty states**. Rely on the existing plugin UI, if any, to guide users towards successful setup and use of the plugin. Avoid onboarding emails, push notifications, and welcome tours.

**Plugins should not redirect on activation from WordPress plugins area**. This can break bulk activation of plugins. Following the [dotorg plugin guideline 11](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#11-plugins-should-not-hijack-the-admin-dashboard), the extension shouldn't hijack the dashboard or hide functionality of core or other extensions.

**Avoid dead end links and pages**. There should always be a way forward or back.

**Error Handling and Messaging**. If users encounter an error during setup, provide a clear and useful notification with clear and easily understood information on what went wrong and how to fix it.
