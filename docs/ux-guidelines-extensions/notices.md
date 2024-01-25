---
post_title: WooCommerce Extension Guidelines - Notices
menu_title: Notices
---

Use notices primarily to provide user feedback in response to an action. Avoid using notices to communicate offers or announcements. Don't apply brand colors, fonts, or illustrations to your notices.

If a post-activation notice is required, keep it within the WordPress plugin area-do not display it on the dashboard, or any other parts of the platform.

Use the standard WordPress notice format and WooCommerce admin notices API.

## Language

Providing timely feedback like success and error messages is essential for ensuring that the user understands whether changes have been made.

Use short but meaningful messages that communicate what is happening. Ensure that the message provides instructions on what the user needs to do to continue. Proper punctuation should be used if the message contains multiple sentences. Avoid abbreviations.

## Design

The placement of feedback is vital so the user notices it. For example, when validation messages are needed to prompt the user to enter data, get the user's attention by displaying a message close to the inputs where data needs to be revised.

![visualization of four different notice designs next to one another](https://developer.woo.com/wp-content/uploads/2023/12/notices1.png)

**Success** message: When the user performs an action that is executed successfully.

**Error Message**: When the user performs an action that could not be completed. (This can include validation messages.) When requiring the user to input data, make sure you verify whether each field meets the requirements, such as format, ranges, and if the field is required. Provide validation messages that are adjacent to each field so that the user can act on each in context. Avoid technical jargon.

**Warning Message**: When the user performs an action that may have completed successfully, but the user should review it and proceed with caution.

**Informational Message**: When it's necessary to provide information before the user executes any action on the screen. Examples can be limitations within a time period or when a global setting limits actions on the current screen.

### Examples

![an example of an informational message as a notice](https://developer.woo.com/wp-content/uploads/2023/12/informational-notice.png)
