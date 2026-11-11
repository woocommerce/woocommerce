# Test motion

Unit tests cannot show motion, because Jest has no Web Animations API, layout, or paint. Test motion in a real browser, on pages that use the blocks that you changed.

For Cart and Checkout, use a store with:

- the block Cart and Checkout pages,
- some simple products and a coupon,
- Free shipping, Flat rate, and Local pickup,
- a tax rate that applies to only some addresses.

Test at desktop width (1440px) and phone width (390px). Use Chrome first. For motion that JavaScript runs, also test Safari and Firefox.

## Static checks

From `plugins/woocommerce/client/blocks`, run:

- `npx stylelint "assets/**/*.scss" "packages/**/*.scss"`. This includes the motion rule.
- `npx eslint <changed files>`.
- `npx tsc --noEmit -p tsconfig.json`. The project has type errors in other files, so look only at the files that you changed.
- `pnpm test:js`. Each helper must do nothing in Jest.

From the repository root, run `pnpm --filter=@woocommerce/block-library build`. Make sure that the compiled CSS keeps the token fallbacks, for example `var(--wpds-motion-duration-md, .2s)`.

## Watch the motion

- **Slow it down.** Use the Animations panel in DevTools at 10% or 25%. Or record a screencast (CDP `Page.startScreencast`) and look at each frame.
- **Measure positions in each frame.** Inside `requestAnimationFrame`, schedule `setTimeout( …, 0 )`, and read the position of the element in it. This reads the painted state. A read directly in `requestAnimationFrame` can show a layout that never paints. Look for:
    - content below a change that moves in two directions, such as down and then up. This means two movements.
    - the largest change between two frames. A jump is a change that is much larger than the changes near it.
    - an important element, such as Place order, that moves out of its expected area.
- **Record each animation that starts.** Wrap `Element.prototype.animate`, and listen for `animationstart` and `transitionrun` in the capture phase. Record the target, the properties, and the duration. This shows two helpers that animate one element, and animations that do not start.
- **Use painted frames to find a flicker.** If an element disappears for a moment, record a screencast. Look for the pixels of the element in each frame. A position check cannot find a frame that paints blank.

## The settled check

After each interaction, wait approximately 1.3 seconds. Then make sure that the blocks you changed (for example `.wp-block-woocommerce-cart`) have:

- no attributes that your motion added, such as `[data-wc-entering]` or `[data-wc-swapping]`,
- no copies of content that went,
- no inline styles that your motion added, for example `overflow`, `height`, `translate`, or `position`,
- no running animations that are not loops,
- no finished script animations with `fill: forwards`, except on content that will unmount,
- no page errors. Listen for `error` and `unhandledrejection`, and for "ResizeObserver loop" errors.

## Scenarios

Do these tests for each area that you changed. Do them at desktop width, at phone width, and at phone width with `prefers-reduced-motion: reduce`.

- **Change and reverse.** Do the interaction. Then do the reverse.
- **Interruption.** Reverse the interaction 80–150ms after it starts. The motion must turn around from its current position.
- **Keyboard.** Go to the control with Tab. Use Space, Enter, or the arrow keys. The change must apply immediately.
- **Late content.** Content that loads after the change, such as rates, pickup locations, or totals, must not jump. It must be in position before the motion, or ease in.
- **Changes together.** Do a change that also updates the totals. For example, switch between Ship and Pickup with a tax rate that applies to only one of them. Look at the order summary and the Place order button during both updates.
- **Resize.** Close an element at phone width. Then make the window wider. No element must stay collapsed.
- **Complete an order.** Place an order after the tests. Motion must not leave a field invalid, a payment method stuck, or a button that does not work.

## Compare with a target

When you build again work that already exists, compare the behavior, not only the code. Record the same scripted interaction on the two builds. Crop the same area, and put the two recordings side by side. Compare when the movement starts and ends, the direction, what fades and what moves, reduced motion, and the settled check.
