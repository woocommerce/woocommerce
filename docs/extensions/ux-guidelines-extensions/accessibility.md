---
post_title: Accessibility
sidebar_label: Accessibility
---

# Accessibility

Your extension must meet the [WordPress Accessibility Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/), including WCAG 2.2 at level AA.

Use the shared [admin component guidance](./README.md#admin-components) when choosing controls. Reusing a component helps, but labels, composition, custom styling and integration with the surrounding screen still need review.

## Check the integrated screen

Test the complete merchant task inside WooCommerce, including the host navigation and feedback. Check both supported PHP and React rendering paths when your extension provides them.

| Area | What to check |
| --- | --- |
| Labels and structure | Every control has an accessible name. Give form fields meaningful visible labels; use supported icon-only or visually hidden-label patterns where appropriate, with clear accessible names. Associate descriptions and errors with their field; placeholders are not labels. Use headings and group related controls so the page remains understandable without its visual layout. |
| Keyboard and focus | Reach and operate every action without a mouse. Keep focus order logical and focus indicators visible and unobscured. Check menus, dialogs, dismissal controls and recovery actions; return focus to a sensible place when a dialog closes. |
| Announcements | Dynamic saving, success, error and loading feedback is available to screen readers without unnecessary interruptions. Follow the [notice announcement guidance](./notices.md#announcements-dismissal-and-recovery), including `Notice.List` when using `@wordpress/ui`. Avoid announcing the same result twice. |
| Validation and recovery | Explain errors beside the affected fields, make them discoverable after submission, and provide a next step. Preserve entered values and let merchants reach and correct the problem. |
| Color and states | Follow the [color and contrast guidance](./colors.md). Check actual foreground/background pairings, focus and error states in supported themes and admin color schemes. Do not rely on color alone. |
| Zoom and layout | Enlarge text to 200% without losing content or functionality. Check reflow at 320 CSS pixels wide, equivalent to a 1280-pixel viewport at 400% zoom, subject to WCAG's exceptions for content requiring a two-dimensional layout. Keep labels, errors and actions readable and reachable. |

See the WCAG explanations for [text resizing](https://www.w3.org/WAI/WCAG22/Understanding/resize-text.html) and [reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html) for the full requirements and exceptions.

Use the [failed-save screenshot](./notices.md#failed-save) to discuss notice placement, retained input and a named recovery action. The [partial-results screenshot](./notices.md#partial-results) shows outcomes labeled with text as well as color. These images illustrate composition; verify accessible names, associations, focus and live announcements in the working screen rather than inferring them from a screenshot.

## Combine automated and manual checks

Run automated accessibility checks, then complete the workflow using only a keyboard and with a screen reader. Include empty, loading, populated, error and success states; an automated scan of the initial page does not cover these interactions.

Use the current [WordPress accessibility testing guide](https://make.wordpress.org/handbook/pathways/test/test-for-accessibility/) for practical starting steps. Record the browser, operating system, assistive technology and versions used, along with steps to reproduce any barriers. Follow the [testing guidance](./testing.md) to check persistence, interrupted work and recovery as part of the same journey.
