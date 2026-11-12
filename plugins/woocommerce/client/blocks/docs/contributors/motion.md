# Motion

This guide gives the rules for motion in WooCommerce blocks, how to add motion, and how to test it. Motion started in the Cart and Checkout blocks, so most examples come from them.

The rules use the [WordPress Design System](https://github.com/WordPress/gutenberg/tree/trunk/packages/theme) (WPDS) motion tokens and its approach to reduced motion.

This guide uses these terms:

- **Movement**: a change to position, size, or the visible area of an element.
- **State change**: a change to color, background, border color, opacity, or a focus ring.
- **Reduced motion**: the `prefers-reduced-motion: reduce` setting.

## Rules

- **Animate what the shopper does.** A click or a tap animates. Content that loads with the page appears immediately, apart from the lists in the next rule. A later change that the shopper's action causes can animate, such as a totals row, a notice, or an error.
- **Where appropriate, list items stagger on first load.** The items of a list, such as cart items, order summary items, or the order confirmation sections, can rise in 50ms apart when the page first shows them. They do not stagger again, such as after an update, a new item, or a key press. Lists of options, such as shipping or payment methods, appear immediately.
- **Keyboard changes apply immediately.** A shopper can press keys quickly, for example to move through options. After a key press, content that shows, hides, or swaps does not animate.
- **Content moves to make space.** Content that appears grows from nothing, and the content below moves down. Content that goes collapses, and the content below moves up. Nothing jumps.
- **One change, one movement.** When one action changes many things, the content below moves one time, in one direction.
- **Motion follows the selection.** When the shopper picks another option in a row of options, such as Ship or Pickup, the selection slides to the new option. Content that the choice swaps moves the same way: the old content leaves in that direction, and the new content follows from the other side.
- **Exits are shorter than entrances.** Movement in takes 300ms. Movement out takes 200ms.
- **Interrupted motion turns around.** When the direction changes during motion, the motion continues from its current position. For example, a panel can close while it opens.
- **Nothing blocks.** Clicks and typing work while motion runs.
- **Leave nothing behind.** When motion ends, the page is the same as without motion. Remove all extra elements, inline styles, attributes, and held animations.
- **Respect reduced motion.** Nothing moves, but changes are still visible. See [Reduced motion](#reduced-motion).
- **Keep public components compatible.** Components in `packages/public-api/blocks-components` are public. Add only optional props, keep the markup and class names that themes can use, and write each change to them in the PR description. Scope new motion to the blocks that use it, such as `.wp-block-woocommerce-cart`.

## Tokens

Use a token for every duration and easing. Do not write raw values. Do not write a fallback inside `var()`: the build adds it, so that the storefront works without the WPDS stylesheet.

| Token | Value | Use |
|-------|-------|-----|
| `--wpds-motion-duration-xs` | 50ms | Stagger steps. |
| `--wpds-motion-duration-sm` | 100ms | The button press only. |
| `--wpds-motion-duration-md` | 200ms | State changes, small changes in place, and exits. |
| `--wpds-motion-duration-lg` | 300ms | Movement in. |
| `--wpds-motion-easing-expressive` | `cubic-bezier(0.25, 0, 0, 1)` | Entrances, exits, and the press. |
| `--wpds-motion-easing-balanced` | `cubic-bezier(0.4, 0, 0.2, 1)` | Movement between two positions, and size changes. |
| `--wpds-motion-easing-subtle` | `cubic-bezier(0.15, 0, 0.15, 1)` | State changes. |

`assets/css/abstracts/_motion.scss` has Woo constants for values that WPDS does not have, such as `$wc-scale-press`.

## Durations

| What | In | Out | Examples |
|------|----|-----|----------|
| Movement | `lg` 300ms | `md` 200ms | Content that rises in, grows, or collapses. Dropdowns, notices, snackbars. |
| State change | `md` 200ms | `md` 200ms | Hover and focus, selected options, dims, field borders. |
| Small change in place | `md` 200ms | `md` 200ms | Checkbox tick, radio dot, icon and spinner swaps. |
| Press | `sm` 100ms | `sm` 100ms | The button press scale. |

- A change of less than 200ms is too fast to see. WPDS uses 100ms for state changes, so Woo uses 200ms.
- Opacity always uses the state change timing. When content moves in over 300ms, it fades in over 200ms.
- Do not use `linear` easing, except for constant motion such as the spinner. Do not bounce.

## Reduced motion

With reduced motion, nothing moves, but changes are still visible. Fades and color changes still run, because they show what changed.

Movement runs only inside `@include motion-safe` (`@media not (prefers-reduced-motion)`). Put the resting values outside it. The helpers do this for you.

- Content that appears or goes still fades.
- Movement that only adds feel, such as the press scale, has no reduced version.
- The spinner stops halfway through a turn. The skeleton shimmer stops.

The `woocommerce/motion-safe` stylelint rule checks this. Another rule checks that durations use tokens and that transitions do not use `all`. These rules apply to the blocks that use motion and to the components they share. When a block starts to use motion, add its stylesheets to the `overrides` `files` list in `.stylelintrc.json`.

## Helpers

Use the helpers in `assets/css/abstracts/_motion.scss`. Do not write `transition` or `animation` values by hand. Each helper writes the state change so that it always runs, and adds the movement inside `motion-safe`.

| Helper | Use | Example |
|--------|-----|---------|
| `transition: motion-state( … )` | A state change: 200ms, subtle. | `transition: motion-state( opacity, box-shadow );` |
| `@include motion-transition( $state, $move, $duration, $easing, $hold )` | A state change and movement. `$move` runs over `$duration` (default `lg`), only when motion is allowed. | `@include motion-transition( $state: opacity, $move: translate );` |
| `motion-hold( … )` | Keeps a property, such as `visibility`, at its old value until a fade ends. | `transition: motion-state( opacity ), motion-hold( visibility );` |
| `@include motion-enter( $keyframes, $duration, $delay )` | Content that appears. It fades in, and plays `$keyframes` (default `wc-rise`). | `@include motion-enter( wc-grow, md );` |
| `@include motion-exit( $keyframes )` | Content that goes. It fades out over 200ms and keeps the end state until it unmounts. | `@include motion-exit( wc-shrink );` |
| `@include motion-stagger( $keyframes )` | Sibling rows that enter 50ms apart. | `@include motion-stagger;` |
| `@include motion-fade-in` / `motion-fade-out` | A fade with no movement. | |
| `@include motion-dim( $states )` | Dims the element while `$states` matches, such as `".is-disabled"`. | Cart rows, disabled radios |
| `@include motion-spinner-swap` | A button that loads. The label fades out and the spinner grows in. | Place order, coupon Apply |
| `@include motion-collapse` | The timing for `animateCollapse`. The content grows over 300ms and collapses over 200ms, and fades. With reduced motion, it only fades. | `Panel` content |
| `@include motion-safe { … }` | Movement that the other helpers do not cover. | |

`assets/css/style.scss` has the shared keyframes. `wc-fade-in` and `wc-fade-out` only fade. `wc-rise`, `wc-grow`, and `wc-shrink` move with `translate`, `scale`, and `filter`, so they combine with a fade and with the resting `transform` of an element.

Use `@starting-style` with transitions for an element that is new on the page, such as a dropdown. Then all of its motion is a transition, and a transition turns around by itself.

Motion that changes the layout runs in JavaScript, because it must measure sizes. JavaScript helpers end immediately when nothing animates, and in Jest.

| Helper | Use |
|--------|-----|
| `whenAnimationsEnd( element, callback )` (`@woocommerce/base-utils`) | Runs `callback` when the animations of `element` end, instead of after a fixed timeout. It waits again if a transition turns around. It returns a function that cancels the callback. |
| `usePresence( value, { animate, collapse } )` (`@woocommerce/base-hooks`) | Keeps content mounted while it animates out. Pass the content, or `null` to remove it. It returns `{ value, isExiting, ref }`. Put `ref` on the element that animates. Pass `collapse: true` to grow and collapse `ref` in the real layout. Pass `animate: false` to apply the change immediately, for example after a key press. |
| `animateCollapse( element, isOpen, animate, from )` (`@woocommerce/base-utils`) | Grows `element` open or collapses it shut in the real layout, and turns around if it still moves. Give `element` the `motion-collapse` mixin. Use it through `usePresence` when the component owns the content. Pass `from`, such as `{ height: '20px' }`, to grow from the size of the content that `element` replaces, or to change the height of an element that stays. |
| `collapseCopy( element )` (`@woocommerce/base-utils`) | Collapses an inert copy of `element` in its place, so that `element` can unmount at once. Call it just before `element` unmounts. If `element` grows again first, it grows from the copy. |
| `useToggleCollapse( isShown, getElement, { growFromTrigger } )` (`@woocommerce/base-hooks`) | Content that a control shows and hides, such as a checkbox that reveals a field. Call the `onToggle` that it returns in the handler of the control, before the state changes. Pass it the click event if there is one, or else put `triggerRef` on the control. `getElement` must not return the copy, which keeps the element's classes: find the element by id, or skip `[data-wc-collapse-copy]`. After a click, the element grows in, or an inert copy of it collapses while the real element unmounts. After a key press, the change applies immediately. Pass `growFromTrigger: true` when the content replaces the control, to grow from the height of the control. |

## Techniques

Find the change in this table, and use the helpers above.

| Change | Technique |
|---|---|
| Color, background, border color, opacity, or focus ring | A state change, both ways. |
| An element is busy or disabled | It dims, and fades both ways. |
| The shopper presses a button | The button scales down a little while pressed. |
| A button shows a spinner | The label fades out, and the spinner grows in. |
| A small icon, dot, or tick changes | It grows from a smaller size and fades in. |
| Content appears in place, such as an empty state | It rises in and fades in. |
| The rows of a list appear | The same rise, 50ms apart. A section that enters as one unit does not stagger its rows. |
| A small item goes, such as a chip | It fades and shrinks in place. Then it unmounts. |
| A dropdown opens from a field | It slides 4px from the field and fades in. It slides back to close. |
| A toast or snackbar appears and goes | It rises in and drops out. |
| Content appears or goes and moves the content below, such as a panel, notice, field error, or section | It grows and collapses in the real layout. It stays mounted while it goes. |
| A form or its fields go | The real form unmounts at once, so its fields stop counting. An inert copy collapses in its place. |
| An element becomes a different view of itself, such as an address card that becomes its form | The two views crossfade while the height changes. |
| A table row goes | It fades in place, and the rows below slide up. |

Real layout means the element's height, padding, and margins change, so theme borders, spacing, and extension markup move with it. Use `transform` for everything else.

## Test motion

Unit tests cannot show motion, because Jest has no Web Animations API, layout, or paint. Test motion in a real browser, on pages that use the blocks that you changed. Test at desktop width and phone width, in Chrome first. For motion that JavaScript runs, also test Safari and Firefox.

To see the motion, slow it down with the Animations panel in DevTools, or record the screen and look at each frame.

Do these tests for each change, also with reduced motion:

- **Change and reverse.** Do the change, then do the reverse. The content below moves in one direction each time, and nothing jumps.
- **Interruption.** Reverse the change 80–150ms after it starts. The motion turns around from its current position.
- **Keyboard.** Use Tab, Space, Enter, or the arrow keys. The change applies immediately.
- **Late content.** Content that loads after the change, such as rates or totals, does not jump.
- **First time.** Open the element for the first time on the page, with the smallest input that opens it. It must stay open. Motion makes an existing flicker easy to see, so fix the cause.
- **Settled.** About 1 second after each change, the blocks have no attributes, inline styles, copies of content, or running animations that the motion added, and no console errors.
- **Complete an order.** Place an order after the tests. Motion must not leave a field invalid or a button that does not work.

Also run stylelint, ESLint, `pnpm test:js`, and the build. Make sure that the compiled CSS keeps the token fallbacks, for example `var(--wpds-motion-duration-md, .2s)`.
