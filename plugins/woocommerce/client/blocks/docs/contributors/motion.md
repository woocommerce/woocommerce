# Motion

Motion in the Cart, Checkout, and Mini-Cart blocks follows the rules below. It builds on the [WordPress Design System](https://github.com/WordPress/gutenberg/tree/trunk/packages/theme) (WPDS): the same tokens, and the same way of handling reduced motion, plus a few Woo rules where WPDS doesn't cover something.

## Tokens

Use tokens for every duration, easing, and scale in motion code. Don't write raw values.

### WPDS tokens

Use these directly. Don't write a fallback value inside `var()`: the build adds one, so they also work on the storefront, where the WPDS stylesheet isn't loaded. A theme can still change them by defining the token on `:root`.

| Token | Value | Use |
|-------|-------|-----|
| `--wpds-motion-duration-xs` | 50ms | Delay between rows entering in sequence, and a secondary detail settling after its parent. |
| `--wpds-motion-duration-sm` | 100ms | Press feedback, focus rings, and state changes such as a floating label, a colour change, or a disabled option dimming. |
| `--wpds-motion-duration-md` | 200ms | Small elements appearing: rows, notices, badges, dropdowns. |
| `--wpds-motion-duration-lg` | 300ms | Reveals and slides, and the success pulse. |
| `--wpds-motion-easing-expressive` | `cubic-bezier(0.25, 0, 0, 1)` | Entrances, exits, and press feedback. |
| `--wpds-motion-easing-balanced` | `cubic-bezier(0.4, 0, 0.2, 1)` | Movement between two on-screen positions, resizing, and the success pulse. |
| `--wpds-motion-easing-subtle` | `cubic-bezier(0.15, 0, 0.15, 1)` | Colour, background, opacity, and focus ring changes that follow a state change. |

### Woo constants

WPDS has no tokens for these. They're defined on `:root` in `assets/css/style.scss`, the `wc-blocks-style` stylesheet loaded with every block.

| Token | Value | Use |
|-------|-------|-----|
| `--wc-motion-exit-ratio` | `0.8` | Exit duration as a share of the entrance. |
| `--wc-scale-press` | `0.97` | Button scale while pressed. |
| `--wc-scale-pulse-peak` | `1.05` | Peak scale of the success pulse. |
| `--wc-stretch-along` | `1.12` | Scale along the direction of travel for a moving indicator. |
| `--wc-stretch-across` | `0.893` | Scale across the direction of travel (`1 / 1.12`, so the area stays the same). |
| `--wc-scale-icon-from` | `0.8` | Scale an icon or label swaps in from and out to. |
| `--wc-blur-icon` | `4px` | Blur an icon or label swaps in from and out to. |

Add a constant when a rule below needs one for the first time, rather than adding a set up front.

### SCSS helpers

These live in `assets/css/abstracts/_mixins.scss`, which is available in every blocks stylesheet:

- `@include motion-safe { … }` wraps motion declarations (see below).
- `motion-exit(lg)` is the exit duration for a `--wpds-motion-duration-lg` entrance: the duration times `--wc-motion-exit-ratio`.
- `@include motion-stagger-index()` sets `--wc-motion-index` on sibling rows, capped at 7 rows. `motion-stagger-delay()` turns it into a delay, and `motion-stagger-delay(1)` adds one more `xs` step for a detail that settles after its row.

## Reduced motion

As in WPDS, motion is only added for shoppers who haven't asked for reduced motion. Put every `transition` and `animation` declaration inside `@include motion-safe`, which is `@media not (prefers-reduced-motion)`. Keep resting values (opacity, transform, colour, and so on) outside it, so the end state renders when motion is off.

Two stylelint rules in `bin/stylelint-plugins/motion.js` check the Cart, Checkout, and Mini-Cart stylesheets and the shared components they use:

- `woocommerce/motion-safe` checks that motion sits inside a reduced-motion guard. To keep motion running under reduced motion on purpose, as the spinner does, disable the rule for that line with a reason.
- `woocommerce/motion-tokens` checks that durations and delays use the WPDS duration tokens rather than literal times, and that transitions list their properties instead of using `all`. Looping animations, such as loaders, keep their own durations.

## Waiting for motion in JavaScript

JavaScript never reads durations. When it has to wait for motion to finish, for example before unmounting content that is animating out, it waits for the element's own animations:

- `usePresence( isOpen )` from `@woocommerce/base-hooks` keeps content mounted while it animates out. It returns `isPresent`, a `transition` of `entering`, `exiting`, or `null` to drive classes, and a `ref` for the animating element. `Panel`, the address suggestions, and the mobile order summary use it.
- `onAnimationsFinished( element, callback )` from `@woocommerce/base-utils` calls `callback` once the element's finite animations and transitions have finished. It ignores looping animations, such as a spinner inside the element.

Both finish straight away when nothing is animating: under reduced motion, when the styles are missing, and in Jest, where the `matchMedia` mock reports reduced motion. Tests stay synchronous.

### Checkout layout changes

When a change adds, removes, or resizes a section of the checkout form, run it through `startCheckoutLayoutTransition( update, morph? )` from `assets/js/blocks/checkout/layout-transition.ts`. It runs `update` inside a same-document view transition: sections below the change slide to their new positions, a section that appears wipes in from the top in step with them, and one that leaves wipes up. Pass `morph` to grow one element into its new state as the same object, such as an address card becoming its form (rule 20). Wrap state updates in `flushSync`, so the new layout exists when the browser takes its snapshot.

It returns `false` without calling `update` when view transitions with types aren't supported or the shopper prefers reduced motion; apply the change directly then. Editing either address, the **Use same address for billing** checkbox, and the Ship / Pickup choice use it.

The view transition calls `update` a frame or two after the shopper's click. When the change comes from a control, show the shopper's choice on that control straight away, with local state that `update` clears, so the control never lags behind the click. The checkbox and the Ship / Pickup toggle do this. Without it, checking or unchecking the box with Playwright fails because its state hasn't changed yet when Playwright checks it.

## General rules

- Animate `transform`, `opacity`, `filter`, and `clip-path` for movement and reveals. Never animate layout properties such as `width`, `height`, `top`, or `margin`.
- Colour, background, and border colour changes on hover or state change may transition, as in WPDS: `--wpds-motion-duration-sm` with `--wpds-motion-easing-subtle`.
- Focus rings may transition `box-shadow` the same way, as in WPDS. Keep it that short, so the ring never lags behind keyboard focus.
- List the properties you transition. Never use `transition: all`.
- Scope new effects to `.wp-block-woocommerce-cart`, `.wp-block-woocommerce-checkout`, and `.wc-block-mini-cart__drawer` so they don't leak into other blocks that share the component.

## Rules

The rule numbers match the original motion rules and demo, so they can be cross-referenced. Rules 10–12 were replaced by rule 2, which follows WPDS.

### Timing and easing

**1. Entrances ease out.** Elements appearing use `--wpds-motion-easing-expressive`. Never ease in, which is slow at the start, exactly when the shopper is watching.

**2. Dropdowns follow WPDS.** Dropdowns and other popovers anchored to a field or button slide 4px from the trigger over `--wpds-motion-duration-md` with `--wpds-motion-easing-expressive`, and fade in over `--wpds-motion-duration-sm` with `linear` easing. They don't scale. Closing slides back toward the trigger. This matches the dropdown motion in `@wordpress/ui`.

**9. On-screen moves ease in and out.** An element moving from one on-screen position to another, rather than entering or leaving, uses `--wpds-motion-easing-balanced`.

**13. No bounce on everyday UI.** Menus, panels, dialogs, and toggles don't overshoot. The single success pulse is the only exception.

**15. Exits are about 20% shorter than entrances.** Pick the entrance duration from the WPDS scale, then exit with `motion-exit()` of the same step. Exits need their own keyframes: reusing the entrance's keyframes won't restart an animation that has already finished.

### Stagger

**3. Stagger list rows by 50ms.** Delay each row by `--wpds-motion-duration-xs` with `motion-stagger-delay()`, capped at 7 rows so the last row starts within 300ms. Rows past the cap share the last delay.

**6. Secondary details settle after their parent.** Badges, counts, and icons land one `--wpds-motion-duration-xs` step after their parent, with `motion-stagger-delay(1)`.

### Feedback

**4. Buttons scale to 0.97 while pressed.** `:active` sets `transform: scale(var(--wc-scale-press))` over `--wpds-motion-duration-sm`.

**8. Pulse once to confirm success.** Run one pulse, 1 → `--wc-scale-pulse-peak` → 1, over `--wpds-motion-duration-lg` with `--wpds-motion-easing-balanced`, alongside the success message. Never loop it.

**Price roll (Woo rule).** A price that changes in place without a loading skeleton, such as the total on the Place order button, rolls its digits like a counter. Use the `RollingPrice` component from `@woocommerce/base-components/rolling-price`: it renders each digit as a column of 0–9, and a CSS transition moves each changed column straight to its new digit over `--wpds-motion-duration-md` with `--wpds-motion-easing-expressive`. Pass `animate={ false }` while the cart is still loading, so the first real total doesn't roll. Prices with a skeleton keep the skeleton instead. The columns run left to right on right-to-left pages too, matching how `FormattedMonetaryAmount` lays out a price.

### Movement

**5. Moving indicators stretch about 12%, keeping their area.** While a switch thumb or selection indicator travels, scale it by `--wc-stretch-along` in the direction of travel and `--wc-stretch-across` across it, then settle back to its size.

**7. Objects travelling between two points follow an arc.** Bow the path about 32px at the midpoint.

**14. Track the pointer 1:1 while dragging.** No easing or transition while the shopper holds the element. Animate only after release.

**19. Rubber-band past drag limits.** Past a boundary, apply diminishing resistance, then spring back on release.

### State changes

**16. Overlap old and new states.** The new state starts entering while the old one exits. Never exit, pause, then enter.

**17. Reveal with clip-path.** Reveal content with a `clip-path` wipe rather than a plain fade.

**18. Swap icons with scale, opacity, and blur.** The outgoing icon fades out, scales down to about 0.8, and blurs to about 4px, while the incoming icon does the reverse, overlapping.

**20. Animate the same object between views.** When an element becomes a larger view of the same thing, animate that element expanding, with FLIP or the View Transitions API, instead of swapping in a different element.

## Exceptions

- **Inline expand and collapse** may animate `grid-template-rows` between `0fr` and `1fr`. Content that opens inline has to push the content below it, and this is the only way to do that smoothly. Apply the grid and clipping only while animating, so the resting layout doesn't change and focus rings aren't clipped. Animate any gap above the content, such as a margin on the toggle, with it, or the gap jumps in before the content grows and snaps away after it closes. The `Panel` component is the reference.
- **Loading indicators (Woo rule).** WPDS stops loading animations under reduced motion. Woo keeps the spinner turning, because it is often the only sign that something is happening, but it steps through 8 positions instead of rotating smoothly. The skeleton shimmer stops under reduced motion, as in WPDS. Loaders keep their own loop durations.
- **Outline focus rings stay instant.** Radio buttons and checkboxes draw their focus ring with `outline`, which can only fade in from an outline that is always present but transparent. In Windows high contrast mode, transparent outlines are shown in the system colour, so every radio and checkbox would look focused. Don't add one; only `box-shadow` focus rings transition.
- **Floating labels animate `top`.** Text input labels float by changing `top` and `scale`, as they always have, because themes and extensions position them with `top`. Moving them with `transform` alone would put the label in the wrong place wherever `top` is overridden.

## Where the rules apply today

| Rule | Where |
|------|-------|
| 1, 15 | Snackbar notices, panel open and close, the address form reveal, the Mini-Cart drawer, cart and Mini-Cart line items, and checkout order summary items. |
| 2 | The address autocomplete suggestions in checkout. |
| 3, 6 | Cart and Mini-Cart line items, checkout order summary items and their quantity badges, and checkout shipping and pickup options. |
| 4 | Buttons inside the Cart, Checkout, and Mini-Cart blocks. |
| 5, 9 | The selection indicator on the checkout Ship / Pickup toggle. |
| 8 | A newly applied coupon's chip. |
| 16, 17 | The coupon and shipping package panels, the mobile checkout order summary, checkout sections appearing and disappearing, and the address forms where view transitions aren't available. |
| 18 | The Place order button's label, spinner, and check. |
| Price roll | The total on the Place order button and on the mobile checkout order summary bar. |
| 20 | The shipping and billing address cards growing into their forms, with a view transition. |

Rules 7, 14, and 19 have nothing to apply to in these blocks yet: there are no objects moving between two points or draggable elements. Follow them when adding one.

The mobile order summary animates closed only when the shopper closes it. When it closes itself as the shopper scrolls toward Place order, it's already off-screen, so it moves straight away.
