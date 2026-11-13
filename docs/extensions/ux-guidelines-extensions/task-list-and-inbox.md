---
post_title: Task list and inbox
sidebar_label: Task list and inbox
---

# Task list and inbox

Choose the surface that matches the merchant's need. Do not publish the same message as both a task and an Inbox note. Use both sparingly, with concise, relevant content and sentence case; follow the [grammar, punctuation and capitalization guide](https://woocommerce.com/document/grammar-punctuation-style-guide/).

| Merchant need | Surface |
| --- | --- |
| Complete a specific setup or store-management action | Task list, when the task belongs in the supported WooCommerce Home integration |
| Read useful, non-critical information | Inbox |
| Understand the result of an action or correct a field | Contextual feedback on the screen where the action happened; follow the [notice guidance](./notices.md) |
| Address a critical condition affecting store operation | A suitably prominent notice or supported alert, scoped to the affected context; do not bury it in the Inbox or rely only on a task |

## Task list

A task must describe actionable work with a clear destination and a meaningful completion state. Examples include connecting a service or configuring an extension. Store-management work may also qualify when the supported integration can represent the action and its state.

![Setup task prompting the merchant to connect Amazon Pay to complete setup](https://developer.woocommerce.com/wp-content/uploads/2023/12/task-list1.png)

Show the task only to merchants for whom it is relevant and available. Link directly to the next step, preserve progress when interrupted, and reflect the actual completion state. Opening a task is not the same as completing its setup outcome.

![WooCommerce task list with three visible setup tasks and an option to show more](https://developer.woocommerce.com/wp-content/uploads/2023/12/task-list-example.png)

Do not use a task for a feature announcement, a tip, a routine success message or an extension that needs no setup. Keep urgent operational issues visible in their affected context.

Use the [setup task integration guide](../extension-onboarding/handling-merchant-onboarding.md#using-setup-tasks) for registration and lifecycle details. Verify the task list and APIs available in your supported WooCommerce versions; do not build a replacement task list just to display an onboarding message.

## Inbox

Use the Inbox for timely, useful information that does not require action to make the store or extension work. Examples include a relevant feature tip, an insight or milestone, and a considerate request for feedback.

![Informational Inbox note with Learn more and Dismiss actions](https://developer.woocommerce.com/wp-content/uploads/2023/12/inbox1.png)

Do not put required setup, critical operational problems or routine action feedback in the Inbox. Optional links may help a merchant explore, but reading or acting on a note must not be a prerequisite for using the extension.

![WooCommerce Inbox containing two informational notes with optional actions and dismissal controls](https://developer.woocommerce.com/wp-content/uploads/2023/12/inbox-examples.png)

Use the [Admin Notes integration guide](../extension-onboarding/handling-merchant-onboarding.md#using-admin-notes) to manage note identity, relevance, duplicate prevention and lifecycle. Check the note type: some types appear as Store Alerts rather than Inbox notes. Respect dismissal and avoid recreating the same note on every visit or activation.
