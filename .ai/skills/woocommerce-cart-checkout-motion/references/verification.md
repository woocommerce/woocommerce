# Verifying motion

Unit tests can't show motion: Jest has no Web Animations API, layout or paint. Prove motion in a real browser, on a store with the block Cart and Checkout pages, several simple products, a coupon, Free shipping and Flat rate, Local pickup, and a tax rate that applies to only some addresses. Use desktop (1440px) and phone (390px) widths. Chrome is the baseline; Safari and Firefox are worth a pass for anything scripted.

## Static checks

From `plugins/woocommerce/client/blocks`:

- `npx stylelint "assets/**/*.scss" "packages/**/*.scss"`: this includes the motion rule.
- `npx eslint <changed files>` and `npx tsc --noEmit -p tsconfig.json`, filtered to the files you changed (the project has existing type errors elsewhere).
- `pnpm test:js`: every helper must be a no-op in Jest.
- From the repo root: `pnpm --filter=@woocommerce/block-library build`. Check that the compiled CSS keeps the token fallbacks (`var(--wpds-motion-duration-md, .2s)`).

## Watching it move

- **Slow it down.** Use DevTools' Animations panel at 10% or 25%, or record a screencast (CDP `Page.startScreencast`) and step through the frames.
- **Sample positions per frame.** Read an element's rect from a `setTimeout( …, 0 )` scheduled inside `requestAnimationFrame`. That reads the painted state after `ResizeObserver` and animation updates; reading directly inside `requestAnimationFrame` can show a layout that's never painted. Look for:
    - one direction of travel for content below a change (a `down → up` bounce means two movements),
    - the biggest single-frame step (a jump is any step much larger than the steps around it),
    - nothing important (the Place order button, the order summary top) leaving its expected box.
- **Log what starts.** Patch `Element.prototype.animate` and listen for `animationstart` and `transitionrun` (capture phase) to see every animation, its target, properties and duration. That's how you catch two helpers fighting over one element, or an animation that never starts.
- **Painted frames for flicker.** When something "vanishes for a moment", record a screencast and check each frame for the element's pixels, not just its rect. Rect checks can't see a frame painted blank.

## The settled check

After each interaction, wait about 1.3 seconds, then check that nothing is left behind inside `.wp-block-woocommerce-cart` and `.wp-block-woocommerce-checkout`:

- no attributes your motion set while it ran, such as `[data-wc-entering]` or `[data-wc-swapping]`,
- no stand-in copies of removed content,
- no inline styles your motion added (for example `overflow`, `height`, `translate` or `position`),
- no running finite animations, and no finished script animations with `fill: forwards` (except on content about to unmount),
- no page errors (listen for `error` and `unhandledrejection`, including "ResizeObserver loop" errors).

## Scenarios to run

For each area you touched, and each of desktop, phone, and phone with `prefers-reduced-motion: reduce`:

- The interaction itself, then its reverse.
- **Interruption:** reverse it 80–150ms after starting. It should turn around from where it is.
- **Keyboard:** Tab to it and use Space, Enter or the arrow keys. The change should apply at once.
- **Late content:** anything that loads after the change (rates, pickup locations, totals) should ease in or already be in place, not jump.
- **Combined changes:** a change that also updates totals (Ship ↔ Pickup with a tax rate that applies to only one of them). Watch the order summary and Place order through both updates.
- **Resize:** close something at phone width, then widen the window. Nothing should stay collapsed.
- **Finish the journey:** place an order afterwards. Motion must never leave a field invalid, a payment method stuck, or a button unusable.

## Comparing with a target

When regenerating work that already exists, compare behaviour, not just code. Record the same scripted interaction on both builds, crop the same region, and place the two side by side. Then check timings (when movement starts and ends), direction, what fades versus what moves, reduced motion, and the settled check.
