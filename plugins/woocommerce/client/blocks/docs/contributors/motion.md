# Motion

This guide gives the rules for motion in WooCommerce blocks. Motion started in the Cart and Checkout blocks, so most examples come from them.

The rules use the [WordPress Design System](https://github.com/WordPress/gutenberg/tree/trunk/packages/theme) (WPDS) motion tokens and its approach to reduced motion. Woo rules fill the gaps that WPDS does not cover.

This guide uses these terms:

- **Movement**: a change to position, size, or the visible area of an element.
- **State change**: a change to color, background, border color, opacity, or a focus ring.
- **Reduced motion**: the `prefers-reduced-motion: reduce` setting.

## Tokens

Use a token for every duration and easing. Do not write raw values. Do not write a fallback inside `var()`: the build adds it, so that the storefront works without the WPDS stylesheet.

| Token | Value | Use |
|-------|-------|-----|
| `--wpds-motion-duration-xs` | 50ms | Stagger steps. A detail that settles after its parent. |
| `--wpds-motion-duration-sm` | 100ms | The button press only. |
| `--wpds-motion-duration-md` | 200ms | State changes, small changes in place, and exits. |
| `--wpds-motion-duration-lg` | 300ms | Movement in, and the success pulse. |
| `--wpds-motion-easing-expressive` | `cubic-bezier(0.25, 0, 0, 1)` | Entrances, exits, and the press. |
| `--wpds-motion-easing-balanced` | `cubic-bezier(0.4, 0, 0.2, 1)` | Movement between two positions on screen, size changes, and the pulse. |
| `--wpds-motion-easing-subtle` | `cubic-bezier(0.15, 0, 0.15, 1)` | State changes. |

`assets/css/abstracts/_motion.scss` has Woo constants for values that WPDS does not have: `$wc-scale-press`, `$wc-scale-pulse-peak`, `$wc-scale-icon-from`, and `$wc-blur-icon`.

## Durations

| What | In | Out | Examples |
|------|----|-----|----------|
| Movement | `lg` 300ms | `md` 200ms | Content that rises in, expands, or collapses. Dropdowns, notices, snackbars. |
| State change | `md` 200ms | `md` 200ms | Button hover and focus, selected options, dims, field borders, values that replace skeletons. |
| Small change in place | `md` 200ms | `md` 200ms | Checkbox tick, radio dot, icon and spinner swaps, price rolls. |
| Press | `sm` 100ms | `sm` 100ms | The button press scale. |

A change of less than 200ms is too fast to see, unless an element changes size. For this reason, only the press uses 100ms.

- Stagger rows by `xs` (50ms).
- The success pulse takes `lg`.
- Constant motion keeps its own speed. The spinner turns once each second.
- Opacity always uses the state change timing. When content moves in over 300ms, it fades in over 200ms.

## Helpers

Use the helpers in `assets/css/abstracts/_motion.scss`. Do not write `transition` or `animation` values by hand. All blocks stylesheets can use the helpers.

Each helper applies the reduced motion rules. It writes the state change one time, and it always runs. It adds the movement inside `motion-safe`.

| Helper | Use | Example |
|--------|-----|---------|
| `transition: motion-state( … )` | A state change: 200ms, subtle. | `transition: motion-state( opacity, box-shadow );` |
| `@include motion-transition( $state, $move, $duration, $easing, $hold )` | A transition with a state change and movement. `$move` runs over `$duration` (default `lg`), only when motion is allowed. | `@include motion-transition( $state: opacity, $move: translate );` |
| `motion-hold( … )` | Keeps a property, such as `visibility`, at its old value until a fade ends. | `transition: motion-state( opacity ), motion-hold( visibility );` |
| `@include motion-enter( $keyframes, $duration, $delay )` | Content that appears. It fades in, and plays `$keyframes` (default `wc-rise`) when motion is allowed. | `@include motion-enter( wc-grow, md );` |
| `@include motion-exit( $keyframes )` | Content that goes. It fades out over 200ms and plays `$keyframes`. It keeps the end state until it unmounts. | `@include motion-exit( wc-shrink );` |
| `@include motion-stagger( $keyframes )` | Sibling rows that enter 50ms apart. | `@include motion-stagger;` |
| `@include motion-fade-in` / `motion-fade-out` | A fade with no movement, such as a value that replaces its skeleton. | |
| `@include motion-dim( $states )` | Dims the element while `$states` matches, such as `".is-disabled"`. It fades both ways. | Cart rows, disabled radios |
| `@include motion-spinner-swap` | A button that loads. The label fades out and the spinner grows in. | Place order, coupon Apply, Proceed to checkout |
| `@include motion-safe { … }` | Movement that the other helpers do not cover. | |

`motion-duration( lg )` and `motion-easing( balanced )` return tokens. To stagger rows by hand, use `motion-stagger-index()` and `motion-stagger-delay()`.

`assets/css/style.scss` has the shared keyframes:

- `wc-fade-in`, `wc-fade-out`, `wc-fade-text-out`, and `wc-hold-visible` only fade or hold.
- `wc-rise`, `wc-grow`, and `wc-shrink` move. They animate `translate`, `scale`, and `filter`. They never animate `opacity` or `transform`, so they combine with a fade and with the resting `transform` of an element.

Position an element that grows or shrinks with `translate`, not `transform`. Then it scales from its center.

Use `@starting-style` with transitions for an element that is new on the page, such as a dropdown. Then all of its motion is a transition, and a transition turns around by itself. This also applies to an element that changes from `display: none` to visible. Use keyframes for motion that only exists while it animates, such as a row that rises in.

Motion that changes the layout runs in JavaScript, because it must measure sizes. JavaScript helpers use the same tokens. They end immediately when nothing animates, and in Jest. This section lists the JavaScript helpers when they are added.

## Reduced motion

With reduced motion, nothing moves, but changes are still visible. Fades and color changes still run, because they show what changed.

Movement runs only inside `@include motion-safe` (`@media not (prefers-reduced-motion)`). Put the resting values outside it. Movement includes `transform`, `translate`, `scale`, `clip-path`, and size changes. The helpers do this for you.

- A state change runs outside `motion-safe`. `motion-transition` writes the state change outside and adds the movement inside.
- Content that appears or goes still fades. `motion-enter` and `motion-exit` write the fade outside `motion-safe` and add the movement inside.
- Movement that only adds feel has no reduced version. Examples are the press scale, the success pulse, and price rolls.
- The spinner stops halfway through a turn. The skeleton shimmer stops.

The `woocommerce/motion-safe` stylelint rule checks this. Another rule checks that durations use tokens and that transitions do not use `all`. These rules apply to the blocks that use motion (Cart and Checkout) and to the components they share. The rule allows `motion-state()`, `motion-hold()`, and the fade and hold keyframes outside `motion-safe`. When a block starts to use motion, add its stylesheets to the `overrides` `files` list in `.stylelintrc.json`.

## General rules

- Animate `transform`, `opacity`, `filter`, and `clip-path`. Do not animate layout properties, except for the items in [Exceptions](#exceptions).
- State changes use `--wpds-motion-duration-md` with `--wpds-motion-easing-subtle`. WPDS uses 100ms, which is too fast to see.
- Do not use `linear` easing. It makes motion look mechanical. Constant motion is the only exception, such as the spinner turn and the skeleton shimmer.
- List the properties that you transition.
- Scope new effects to the block that uses them, such as `.wp-block-woocommerce-cart`. Then they do not change blocks that do not use motion. A shared component that already has its own motion, such as snackbars, can replace that motion everywhere it renders.
- Components in `packages/public-api/blocks-components` are public. Add only optional props. Keep the class names that themes can use. Write each change to their markup or styles in the PR description.
- **Animate what the shopper does.** A click or a tap animates. Content that loads with the page appears immediately. A section that renders when data arrives appears immediately. A later change that loading causes can animate, such as a totals row, a notice, or an error.
- **Keyboard changes apply immediately.** A shopper can press keys quickly, for example to move through options. After a key press, sections that show, hide, or swap do not animate, and selection indicators do not slide. While an option in a radio group has keyboard focus, its highlight and dot change immediately.
- **Interrupted motion turns around.** When the direction changes during motion, the motion continues from its current position. For example, a panel can close while it opens. Transitions do this automatically. JavaScript motion reads the current position and continues from it.
- **Nothing blocks.** Clicks and typing work while motion runs.
- **Leave nothing behind.** When motion ends, the page is the same as without motion. Remove all extra elements, inline styles, attributes, and held animations.
- **Rest on whole pixels.** An element that stays transformed at rest must move in whole-pixel steps (`round(1lh, 1px)`). An example is the digit column of a rolling price. Only content that will unmount can keep the end state of an animation. On some screens, fractional positions make digits and edges look out of line.

## Rules

The numbers match the original motion rules. Code comments use these numbers. Some numbers are not used.

**1. Entrances ease out** with `--wpds-motion-easing-expressive`.

**2. Dropdowns follow WPDS, without the linear fade.** They slide 4px from the trigger over `lg` expressive, and fade in over `md`. They slide back over `md` to close.

**3. Stagger list rows by 50ms** with `motion-stagger-delay()`. Stop at 7 rows. When a section enters as one unit, its rows enter with it and do not stagger. `motion-stagger` stops inside an element that has `data-wc-entering` or `data-wc-swapping`.

**4. Buttons scale to `$wc-scale-press` while pressed**, over `sm`.

**5. A moving indicator stretches about 12% along its path, and keeps its area.** Then it settles.

**6. A detail settles one `xs` step after its parent**, with `motion-stagger-delay(1)`.

**8. Pulse one time to confirm success**: 1 → `$wc-scale-pulse-peak` → 1, over `lg` balanced. Do not loop it.

**9. Movement between two positions on screen uses `--wpds-motion-easing-balanced`.**

**13. No bounce**, except the success pulse.

**15. Exits are shorter than entrances.** Movement that enters over `lg` exits over `md`, with its own keyframes. State changes and small changes in place take `md` both ways.

**16. Overlap the old and new states.** Do not exit, pause, and then enter.

**17. Make space by moving the content below.** Content that appears grows from nothing, and the content after it moves down. Content that goes collapses, and the content after it moves up. Borders move with the content.

**18. Swap icons with scale, opacity, and blur** (`$wc-scale-icon-from`, `$wc-blur-icon`).

**20. Animate the same element between views.** For example, an address card becomes its form. Do not swap in a different element.

**21. One change, one movement (Woo rule).** When a choice swaps a group of sections, such as Ship or Pickup, the group changes as one unit:

- Its height changes one time, so the content below moves one time, in one direction.
- The old sections fade out over `sm` and move 24px in the direction of the selection.
- After they fade, the new sections fade in over `md`. They slide in from the other side over `lg` expressive.
- The group includes all sections from the first changed section to the last. This includes a section between them that is in both states, such as a billing address.
- The sections after the group stay in their old positions until the old sections have faded. Content that loads during this time, such as pickup locations, is then in position before anything moves. Then the sections after the group slide to their new positions.
- With reduced motion, the sections only fade. After a key press, the change applies immediately.

**Price roll (Woo rule).** When a price changes in place, its digits roll over `md` expressive. Prices that change together roll at the same time, such as a line total and its "Save" badge. Prices do not roll while the cart loads for the first time.

**Skeleton reveal (Woo rule).** A value or list that replaces a loading skeleton fades in over `md` subtle. It does not roll, because the old value is not on screen. All skeletons in one loading phase end together. Totals rows that appear or go, and the total in the summary bar, wait for that moment. Then everything changes at once.

## Exceptions

- **Content that makes space animates its size.** Examples are panels, notices, field errors, totals rows, and sections that the shopper shows or swaps. This is real layout, so theme borders, spacing, and extension markup move with it, and it turns around when interrupted. It runs for 300ms in and 200ms out. Table rows cannot change height, so they slide with `transform`. Same-document view transitions were tested for this and removed: they block clicks, they cannot turn around, and they distort boxes that have borders.
- **Outline focus rings change immediately.** A transparent outline shows in Windows high contrast mode.
- **Floating labels animate `top`**, because themes and extensions position them with `top`.
