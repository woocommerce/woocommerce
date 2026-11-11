# Motion

This guide gives the rules for motion in WooCommerce blocks, how to add motion, and how to test it. Motion started in the Cart and Checkout blocks, so most examples come from them.

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

## Add motion

1. Write one sentence that describes the change: what changes on screen, and what causes it. The cause is the shopper, a key press, or data that loads.
2. Select the technique from the table below, and read the rules that it gives.
3. Use the helpers in the Helpers section. If no helper does the technique, write a small one in `assets/js/base/utils`, or in `assets/js/base/hooks` for a React hook. Add it to the Helpers section.
4. Do the edge cases: reduced motion, keyboard input, interruption, content that loads late, and changes that occur at the same time.
5. Test in a real browser (see Test motion). Write comments that tell why, not what, and give the rule number.

### Techniques

| Change | Technique | Rules |
|---|---|---|
| Color, background, border color, opacity, or focus ring | A state change transition, both ways | Durations |
| An element dims while it is busy or disabled | Opacity decreases to half, and fades both ways | Durations |
| The shopper presses a button | The button scales down a little while pressed | 4 |
| A button shows a spinner while busy | The label fades out, and the spinner grows in | 18 |
| A small icon, dot, or tick changes | It scales up from a smaller size and fades in. Icons also blur. | 18 |
| Content appears in place, such as an empty state | It rises in and fades in | 1 |
| The rows of a list appear | The same rise, staggered | 3 |
| A detail of a row appears, such as a quantity badge | It settles one step after its row | 6 |
| A small item goes, such as a chip | It stays in position while it fades and shrinks. Then it unmounts. | 15, 16 |
| The shopper completes an action, such as a coupon | One pulse, only for the action that the shopper did | 8 |
| A dropdown opens from a field | It slides from the field. It stays mounted while it closes. It turns around if it opens again. | 2 |
| A toast or snackbar appears and goes | It rises 8px in and drops 4px out. The script waits for the animation to end. It does not use a fixed timeout. | 1, 15 |
| A value replaces its loading skeleton | It fades in. It does not roll. | Skeleton reveal |
| A price changes in place | Its digits roll. Its text stays the plain price for assistive technology. | Price roll |
| Many skeletons are part of one update | They end together. The values change at that moment. | Skeleton reveal |
| Content appears or goes and must move the content below, such as a panel, notice, field error, or totals row | It grows and collapses in the real layout. It stays mounted while it goes. | 17, Exceptions |
| The shopper shows or hides a section, such as an address or payment fields | The new section grows in, and the old section collapses. A form that goes must stop being a form immediately (see Traps). | 17 |
| The content of a box changes size later, such as a list that loads | The height of the box changes smoothly to the new size | 17 |
| An element becomes a different view of itself, such as an address card that becomes its form | The two views crossfade while the height of the box changes smoothly | 20 |
| A choice swaps a group of sections, such as Ship or Pickup | One change, one movement | 21 |
| The selection moves to a different option | The selected style slides to the new option, and stretches along its path | 5, 9 |
| A table row goes | It fades in its position, the rows below slide up, and the container height changes smoothly | 17, Exceptions |
| A bar opens or closes, such as the mobile order summary | Its content grows and collapses. If it closes because the shopper scrolls past it, it closes immediately. | 17 |

### Keep the change small

- Change only what the motion needs. Do not rewrite how a component works, and do not add, remove, or replace dependencies. If the component already has a mechanism that can carry the motion, use it. For example, `react-transition-group` can end a transition when the animation ends, with `addEndListener`.
- Do not add wrapper elements to the markup of a shared component. Themes and extensions can target that markup. Put the ref or class on an element that exists, or add an optional prop.
- Do not change `pnpm-lock.yaml` or `package.json` in a motion change.

### Traps

Each of these traps caused a bug before.

- **Each script bundle has its own copy of a module.** Cart, Checkout, and the shared components package each include `base-utils`. Thus, module state is not shared. Put state that bundles must share on `globalThis`.
- **A cancelled or finished animation is not in progress.** To read the current position of an animation, use only animations with `playState` `running` or `paused`. A finished animation with `fill: forwards` keeps its end state until you cancel it. This can keep an element collapsed.
- **Do not start a size animation in a `ResizeObserver` callback.** The animation changes the size again in the same frame, and the browser shows a "ResizeObserver loop" error. Start it before layout, for example in a `MutationObserver` callback.
- **Nested motion can conflict.** A box that changes its own height must stop while an element inside it grows or collapses. If not, the change animates two times. Only motion that changes size counts. Fades, slides, and price rolls do not count.
- **A clip can cut off content.** Clip only the element that changes size, and only while it changes. If a parent is clipped while its height changes at a different speed from its children, the clip cuts off the children. The Place order button once disappeared because of this.
- **Content that arrives after the change can jump.** Examples are shipping rates, pickup locations, and totals. If a clip uncovers it, it looks like it grows. Wait until the content is in position before anything moves, or change the height of the box smoothly when the content arrives.
- **Chrome can show a blank area for some frames** when an element inside it animates during a view transition.
- **An animation cannot change `!important` spacing.** Use a registered custom property (`@property`) for the spacing, and animate the property.
- **A form that goes must stop being a form immediately.** If it stays mounted while it animates out, its validation errors and payment handlers stay registered. Then Place order can use them. Animate a copy of the form, not the form. The copy must be inert and `aria-hidden`. It must not have `id`, `name`, or `for` attributes. Its iframes must be blank, because a copied payment iframe loads again. It must not have attributes that start entrances, because it then plays them again.
- **React 18 has no `inert` prop.** To set the attribute, spread `{ inert: '' }`.
- **Keep the keys of a list stable.** If keys change, entrances play again on each render.
- **When DOM moves to a different parent, its CSS animations start again.** After the move, finish the animations that are not loops.
- **A notice can show for one frame during a state change.** It moves the content around it, and the motion makes this easy to see. Fix the cause: create the notice only after the state is stable. Do not animate the notice.
- **Jest has no Web Animations API, layout, or `ResizeObserver`.** In Jest, each helper must end immediately and must not throw an error.

## Test motion

Unit tests cannot show motion, because Jest has no Web Animations API, layout, or paint. Test motion in a real browser, on pages that use the blocks that you changed.

For Cart and Checkout, use a store with:

- the block Cart and Checkout pages,
- some simple products and a coupon,
- Free shipping, Flat rate, and Local pickup,
- a tax rate that applies to only some addresses.

Test at desktop width (1440px) and phone width (390px). Use Chrome first. For motion that JavaScript runs, also test Safari and Firefox.

### Static checks

From `plugins/woocommerce/client/blocks`, run:

- `npx stylelint "assets/**/*.scss" "packages/**/*.scss"`. This includes the motion rule.
- `npx eslint <changed files>`.
- `npx tsc --noEmit -p tsconfig.json`. The project has type errors in other files, so look only at the files that you changed.
- `pnpm test:js`. Each helper must do nothing in Jest.

From the repository root, run `pnpm --filter=@woocommerce/block-library build`. Make sure that the compiled CSS keeps the token fallbacks, for example `var(--wpds-motion-duration-md, .2s)`.

### Watch the motion

- **Slow it down.** Use the Animations panel in DevTools at 10% or 25%. Or record a screencast (CDP `Page.startScreencast`) and look at each frame.
- **Measure positions in each frame.** Inside `requestAnimationFrame`, schedule `setTimeout( …, 0 )`, and read the position of the element in it. This reads the painted state. A read directly in `requestAnimationFrame` can show a layout that never paints. Look for:
    - content below a change that moves in two directions, such as down and then up. This means two movements.
    - the largest change between two frames. A jump is a change that is much larger than the changes near it.
    - an important element, such as Place order, that moves out of its expected area.
- **Record each animation that starts.** Wrap `Element.prototype.animate`, and listen for `animationstart` and `transitionrun` in the capture phase. Record the target, the properties, and the duration. This shows two helpers that animate one element, and animations that do not start.
- **Use painted frames to find a flicker.** If an element disappears for a moment, record a screencast. Look for the pixels of the element in each frame. A position check cannot find a frame that paints blank.

### The settled check

After each interaction, wait approximately 1.3 seconds. Then make sure that the blocks you changed (for example `.wp-block-woocommerce-cart`) have:

- no attributes that your motion added, such as `[data-wc-entering]` or `[data-wc-swapping]`,
- no copies of content that went,
- no inline styles that your motion added, for example `overflow`, `height`, `translate`, or `position`,
- no running animations that are not loops,
- no finished script animations with `fill: forwards`, except on content that will unmount,
- no page errors. Listen for `error` and `unhandledrejection`, and for "ResizeObserver loop" errors.

### Scenarios

Do these tests for each area that you changed. Do them at desktop width, at phone width, and at phone width with `prefers-reduced-motion: reduce`.

- **Change and reverse.** Do the interaction. Then do the reverse.
- **Interruption.** Reverse the interaction 80–150ms after it starts. The motion must turn around from its current position.
- **Keyboard.** Go to the control with Tab. Use Space, Enter, or the arrow keys. The change must apply immediately.
- **Late content.** Content that loads after the change, such as rates, pickup locations, or totals, must not jump. It must be in position before the motion, or ease in.
- **Changes together.** Do a change that also updates the totals. For example, switch between Ship and Pickup with a tax rate that applies to only one of them. Look at the order summary and the Place order button during both updates.
- **Resize.** Close an element at phone width. Then make the window wider. No element must stay collapsed.
- **Complete an order.** Place an order after the tests. Motion must not leave a field invalid, a payment method stuck, or a button that does not work.
