---
post_title: Testing
sidebar_label: Testing
---

# Testing

Test whether merchants can complete the work your extension promises. Combine automated checks, hands-on review and usability sessions with representative merchants. Apply the same review criteria to human-written and AI-generated implementations.

## Start with merchant tasks

List the important scenarios your extension supports and define a successful outcome for each. For example: "Create a customizable product that lets shoppers add text and choose from five text colors." Ask testers to complete the task and observe where they hesitate, make mistakes or need help, without prescribing every click.

Include first use, everyday work and returning to an existing configuration. Record the WordPress and WooCommerce versions, extension version, relevant component packages, browser, user role and important settings so the results are reproducible. Cover the versions and integrations your extension claims to support.

## Check the complete workflow

| Scenario | Expected outcome |
| --- | --- |
| Find the feature | Merchants reach the task from the appropriate WooCommerce area, understand labels and can navigate back. Settings and setup links open the intended destination. |
| Configure and save | Valid changes save through the host's save model. Reloading or returning shows the saved values. Untouched settings remain unchanged, and defaults behave as described. |
| Correct an error | Required, malformed and out-of-range values receive clear, associated feedback. Entered values remain available, and correcting the problem allows progress. |
| Handle slow or failed requests | Loading and saving states are understandable. A failure offers recovery, preserves work and does not report success before persistence is confirmed. Retrying does not duplicate completed work. |
| Handle partial results | If the feature supports bulk or multi-item actions, show what completed and what failed. Keep successful changes and let merchants review or retry the failed items. |
| Leave and return | Completed setup stays complete. Interrupted work can be resumed where supported. Manual-save screens protect unsaved changes according to the host contract; feedback matches what is actually persisted. |
| Use different layouts and input methods | The complete task works with a keyboard, screen reader, text enlargement and narrow layouts. Check long labels and translations as well as the default language. |

Use the [accessibility checks](./accessibility.md) and [feedback and recovery guidance](./notices.md) during these scenarios. Test supported classic and React paths when both are provided; do not assume a component preview represents the integrated screen.

## Use automation where it protects behavior

Add regression coverage for important behavior such as validation, permissions, saving and failure recovery. Choose checks that exercise the real integration rather than only asserting that a component or text string exists. Pair automated accessibility checks with manual interaction checks and observation of merchants using the feature.

For each issue, record the task, expected and actual outcome, environment, reproduction steps and useful screenshots or recordings. Fix barriers and repeat the affected scenario, including persistence and recovery when relevant.

## Find people to test with

- Recruit potential customers who perform the tasks your extension supports, including people who use assistive technology.
- Use usability-testing services such as UserTesting.com to reach new users.
- Ask enthusiasts in the [WooCommerce Community Slack](https://woocommerce.com/community-slack/) for feedback.

For accessibility testing methods, start with the [WordPress accessibility testing guide](https://make.wordpress.org/handbook/pathways/test/test-for-accessibility/). Treat usability findings and accessibility checks as complementary evidence when deciding whether the complete experience is ready.
