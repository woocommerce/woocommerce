# WordPress Design System tokens

Review rules for styling in `.css`, `.scss`, and JS/TS style code. Use `--wpds-*` tokens from `@wordpress/theme` instead of hardcoded values.

## Rules

- Don't hardcode colors, spacing, radii, font sizes, line heights, or transition durations and easings when a `--wpds-*` token exists for the role.
- Pick the token by semantic role (what is being styled), not by the nearest raw value.
- Don't invent token names. Only use tokens that exist in the installed `@wordpress/theme`.
- Keep the target and tone consistent across the background, foreground, and stroke of one element. Change only the state segment between states.
- Don't use legacy Sass palette variables or `--wp-admin-*` tokens in new code.

## Naming

Color tokens follow `--wpds-color-<property>-<target>-<tone>[-<emphasis>][-<state>]`.

- Property: `background`, `foreground`, `stroke`.
- Target: `surface` (static containers), `interactive` (buttons, inputs, links), `content` (text and icons), `track`, `thumb`.
- Tone: `neutral`, `brand`, `success`, `info`, `caution`, `warning`, `error`. Use `caution` for minor risks and `warning` when the user must act.
- Emphasis: `weak`, `strong`. State: `active`, `disabled`.
- The only color token outside this grammar is `--wpds-color-stroke-focus`, used for focus rings.

## Other families

| Family     | Pattern                                | Example                              |
| ---------- | -------------------------------------- | ------------------------------------ |
| Spacing    | `--wpds-dimension-{padding,gap}-<size>` | `--wpds-dimension-gap-md`            |
| Size       | `--wpds-dimension-size-<size>`         | `--wpds-dimension-size-lg`           |
| Radius     | `--wpds-border-radius-<size>`          | `--wpds-border-radius-sm`            |
| Border     | `--wpds-border-width-<size>`           | `--wpds-border-width-focus`          |
| Type       | `--wpds-typography-<property>-<size>`  | `--wpds-typography-font-size-md`     |
| Motion     | `--wpds-motion-{duration,easing}-<x>`  | `--wpds-motion-easing-subtle`        |

Sizes run `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`.

## Radius by role

- `xs`: elements nested inside controls.
- `sm`: standalone buttons, inputs, compact controls.
- `md`: menus, popovers, small overlays.
- `lg`: cards, dialogs, notices.
- `xl`: page and app shell surfaces.
