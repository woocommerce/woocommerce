# Email Button Spacing Fix

## Context

Rendered block email buttons have extra space below their wrapper table. The single-row flex layout renders that table as `display:inline-block`, which places it in an inline formatting context and leaves baseline space below it.

The flex renderer has a separate wrapping path for overflowing auto-width button rows. That path intentionally uses inline-block items so they can wrap in capable email clients and must remain unchanged.

## Goal

Remove the unintended space below buttons while preserving:

- Auto-width button groups.
- Left, center, and right alignment.
- Explicit button widths and gaps.
- RTL behavior.
- The existing overflowing-row wrapping behavior.
- Outlook's conditional table layout.

## Considered approaches

### Add vertical alignment to the inline table

Keep the table inline so the parent `text-align` continues to position it, and add an explicit `vertical-align` value to avoid baseline alignment.

This is the smallest change and is the recommended first implementation because it does not introduce a different alignment mechanism.

### Remove `display:inline-block` and replace alignment

Return the table to its default display and reproduce left, center, and right positioning with margins or table attributes.

This removes the cause directly, but email support for table margins varies. The legacy `align` attribute can also float tables and allow following content to wrap beside them, so this approach has a larger regression surface.

### Change the parent line height

Set the parent wrapper's line height or font size to zero.

This can suppress inline baseline space but risks affecting inherited typography and is therefore not recommended.

## Existing patterns

The selected approach follows existing email-renderer patterns:

- The wrapping path in the same flex renderer pairs `display:inline-block` with `vertical-align:top` for its items.
- WooCommerce's email styles explicitly vertically align inline-block images.
- The audio block renderer explicitly vertically aligns an inline-block link.

The alternatives also have known trade-offs in this codebase. The image renderer uses `font-size:0` to suppress whitespace, but it must restore the font size for captions. The gallery renderer intentionally avoids `align` on its wrapper table because left and right table alignment can take the table out of normal flow and interfere with following content.

## Design

Add a regression assertion for the single-row layout before changing production code. The test will require the wrapper table to opt out of baseline alignment without changing the intentionally wrapping layout.

Add `vertical-align:top` to the single-row wrapper table while retaining `display:inline-block`. Do not modify the button renderer, wrapping layout, width computation, conditional Outlook markup, or public APIs.

If the targeted test proves that vertical alignment does not express the required output cleanly, stop and reassess the table-alignment approach instead of combining multiple CSS changes.

## Verification

Automated verification will cover the flex layout renderer and ensure the wrapping path retains its inline-block items. PHP linting and static analysis will run for the modified PHP files.

Manual verification should compare rendered emails with:

- One auto-width button.
- Two auto-width buttons.
- Left, center, and right justification.
- An explicit-width button.
- An overflowing button row.
- LTR and RTL rendering.

Mailpit can verify the generated email HTML and browser rendering. Gmail, Apple Mail, and Outlook remain the important client checks when the project test infrastructure provides access to them.

## Compatibility

The change does not alter PHP signatures, hooks, filters, or stored data. It changes rendered email markup, so email-client rendering is the compatibility surface. The implementation remains additive at the CSS declaration level and preserves Outlook-specific conditional markup.
