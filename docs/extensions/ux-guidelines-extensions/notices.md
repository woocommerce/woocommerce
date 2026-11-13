---
post_title: Notices
sidebar_label: Notices
---

# Notices

Use notices primarily to provide user feedback in response to an action. Avoid using notices to communicate offers or announcements. Don't apply brand colors, fonts, or illustrations to your notices.

If a post-activation notice is required, keep it within the WordPress plugin area—do not display it on the dashboard or unrelated screens. For non-critical announcements and setup work, follow the [Task List and Inbox guidance](./task-list-and-inbox.md).

## Choose the implementation for the host

- **PHP admin screens:** use the [standard WordPress admin notice format and hook](https://developer.wordpress.org/reference/hooks/admin_notices/), with [`wp_admin_notice()`](https://developer.wordpress.org/reference/functions/wp_admin_notice/) where supported. When maintaining a WooCommerce admin notices integration, use the [`WC_Admin_Notices` API](https://woocommerce.github.io/code-reference/classes/WC-Admin-Notices.html) and verify its screen scope, persistence and dismissal behavior. These PHP APIs do not place feedback beside a field inside a React interface.
- **React admin interfaces:** follow the [admin component guidance](./README.md#admin-components). Prefer [`Notice` from `@wordpress/ui`](https://wordpress.github.io/gutenberg/?path=/docs/design-system-components-notice--docs) for persistent, contextual feedback. Verify its exports, API and required theme setup in the supported version. Use a suitable `@wordpress/components` fallback only for a verified coverage gap and document the reason.
- **Brief confirmations:** confirm success when the result is not otherwise visible. When a separate message is needed, use the host's existing feedback region where available, without duplicating inline confirmation. [`Snackbar`](https://wordpress.github.io/gutenberg/?path=/docs/components-snackbar--docs) currently comes from `@wordpress/components`; it is a fallback when the supported `@wordpress/ui` version lacks that capability. Check placement and timing, and keep any snackbar action available elsewhere in the interface.

Use the component's semantic intent or status props and default styling. Follow the [color guidance](./colors.md) for custom styling rather than applying brand colors or copying values from a screenshot.

## Language

Providing timely feedback like success and error messages is essential for ensuring that the user understands whether changes have been made.

Use short, meaningful messages in sentence case. Explain what happened, the cause when known, and what the merchant can do next. For example: "Changes weren't saved because the connection was lost. Check your connection and try again." Avoid blame, abbreviations, technical jargon and sensitive details. See the WPDS [error message guidance](https://wordpress.github.io/gutenberg/?path=/docs/design-system-patterns-error-messages--docs).

## Design

Place feedback close to the task it describes. Field validation belongs beside the field and must be programmatically associated with it. A row or panel failure belongs in that row or panel. Put screen-wide feedback below the screen's toolbars; do not duplicate the same message in several regions.

| Message | When to use it |
| --- | --- |
| Success | Confirm a completed action when the result is not otherwise obvious. Keep confirmation brief and avoid duplicate feedback. |
| Error | Explain an action that could not complete or a value that needs attention, with a recovery step. |
| Warning | Explain a risk or limitation the merchant should review before proceeding. |
| Information | Give context relevant to the current task, such as a setting that limits available actions. |

Match severity to the actual outcome. For partial success, use one summary that explains what completed and what still needs attention, with links or actions for the failed items. Do not stack contradictory success and error notices. Follow the host's save contract and the WPDS [save and submit feedback guidance](https://wordpress.github.io/gutenberg/?path=/docs/design-system-patterns-save-submit--docs).

## Announcements, dismissal and recovery

- Announce dynamic feedback to assistive technology. With `@wordpress/ui`, render notices inside [`Notice.List`](https://wordpress.github.io/gutenberg/?path=/docs/design-system-components-notice-announcements--docs): a standalone `Notice.Root` does not announce its content. Use polite announcements by default and reserve assertive announcements for time-sensitive updates. Avoid duplicate live-region announcements.
- Keep errors that require action visible until resolved or deliberately dismissed. Do not rely on a timed snackbar for information the merchant needs to recover.
- Make dismissal controls and recovery actions keyboard accessible and clearly named. Dismissing a message must not discard edits or imply that an unresolved problem has been fixed. Do not move focus for routine confirmations; help merchants reach the relevant field when validation prevents saving.
- Preserve entered values and successful changes after a failure. Offer a focused retry or correction step for the failed work, without requiring successful work to be repeated.

Check the integrated screen with a keyboard and screen reader, including adding, updating, dismissing and acting on feedback.

## Legacy examples

These screenshots show legacy notice styling. Use them to understand message types and context; follow the current component references above for implementation and visual styling.

![Legacy examples of success, error, warning and informational notices](https://developer.woocommerce.com/wp-content/uploads/2023/12/notices1.png)

![Legacy informational notice providing context for the current screen](https://developer.woocommerce.com/wp-content/uploads/2023/12/informational-notice.png)
