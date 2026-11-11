# Motion

Motion in the Cart and Checkout blocks follows the rules below. It builds on the [WordPress Design System](https://github.com/WordPress/gutenberg/tree/trunk/packages/theme) (WPDS): the same tokens, and the same way of handling reduced motion, plus a few Woo rules where WPDS doesn't cover something.

## Tokens

Use tokens for every duration and easing. Don't write raw values, and don't write a fallback inside `var()`: the build adds one, so they also work on the storefront, where the WPDS stylesheet isn't loaded.

| Token | Value | Use |
|-------|-------|-----|
| `--wpds-motion-duration-xs` | 50ms | Stagger steps, and a detail settling after its parent. |
| `--wpds-motion-duration-sm` | 100ms | The button press only. |
| `--wpds-motion-duration-md` | 200ms | State changes, small changes in place, and exits. |
| `--wpds-motion-duration-lg` | 300ms | Movement in: slides, wipes, reveals, expanding, layout changes, and the success pulse. |
| `--wpds-motion-easing-expressive` | `cubic-bezier(0.25, 0, 0, 1)` | Entrances, exits, and press feedback. |
| `--wpds-motion-easing-balanced` | `cubic-bezier(0.4, 0, 0.2, 1)` | Movement between two on-screen positions, resizing, and the pulse. |
| `--wpds-motion-easing-subtle` | `cubic-bezier(0.15, 0, 0.15, 1)` | Colour, background, opacity, and focus ring changes. |

Woo constants for what WPDS doesn't cover are SCSS variables in `assets/css/abstracts/_motion.scss`: `$wc-scale-press`, `$wc-scale-pulse-peak`, `$wc-scale-icon-from`, and `$wc-blur-icon`.

## Durations

Three speeds cover almost everything. Changes under 200ms are too quick to notice unless something visibly changes size, so only the press uses 100ms.

| What | In | Out | Examples |
|------|----|-----|----------|
| **Movement**: position, size, or visible area changes | `lg` 300ms | `md` 200ms | Slides, wipes, expanding and collapsing, layout changes, dropdowns, rows fading up, notices, snackbars, the Ship/Pickup indicator |
| **State changes**: colour, outline, background, opacity | `md` 200ms | `md` 200ms | Button hover and focus, selected options, dims while loading, field border and label, values replacing skeletons |
| **Small changes in place** | `md` 200ms | `md` 200ms | Checkbox tick, radio dot, icon and spinner swaps, price rolls |
| **Press feedback** | `sm` 100ms | `sm` 100ms | The button press scale, which is visible at any speed |

Stagger rows by `xs` (50ms). The success pulse takes `lg`. Constant motion keeps its own speed: the spinner turns once a second. Opacity is always a state change: content that rises, wipes, or grows in fades over 200ms while it moves over 300ms, and a reduced-motion fade takes 200ms.

## Helpers

Write motion with the helpers in `assets/css/abstracts/_motion.scss`, available in every blocks stylesheet, rather than `transition` and `animation` by hand. Each one follows the reduced motion policy below: the fade or colour change is written once and always runs, and movement is added inside `motion-safe`.

| Helper | For | Example |
|--------|-----|---------|
| `transition: motion-state( … )` | State changes: 200ms, subtle | `transition: motion-state( opacity, box-shadow );` |
| `@include motion-transition( $state, $move, $duration, $easing, $hold )` | A transition that fades and moves; `$move` runs over `$duration` (`lg` by default) only when motion is allowed | `@include motion-transition( $state: opacity, $move: translate );` |
| `motion-hold( … )` | Keeps a property, such as `visibility`, at its old value until a fade ends | `transition: motion-state( opacity ), motion-hold( visibility );` |
| `@include motion-enter( $keyframes, $duration, $delay )` | Content appearing: fades in, and plays `$keyframes` (`wc-rise` by default) when motion is allowed | `@include motion-enter( wc-grow, md );` |
| `@include motion-exit( $keyframes )` | Content going: fades out over 200ms, and plays `$keyframes`; holds the end until it unmounts | `@include motion-exit( wc-shrink );` |
| `@include motion-stagger( $keyframes )` | Sibling rows entering 50ms apart | `@include motion-stagger;` |
| `@include motion-fade-in` / `motion-fade-out` | Fades with no movement, such as a value replacing its skeleton | |
| `@include motion-dim( $states, $opacity )` | Dims while a state matches, such as `".is-disabled"`, fading back too | Cart rows, disabled radios |
| `@include motion-spinner-swap` | A button's loading state: the label fades out and the spinner grows in | Place order, coupon Apply, Proceed to checkout |
| `@include motion-safe { … }` | Movement the helpers don't cover, such as the Ship/Pickup indicator | |

`motion-duration( lg )` and `motion-easing( balanced )` return tokens, and `motion-stagger-index()` with `motion-stagger-delay()` stagger rows by hand.

Shared keyframes live in `assets/css/style.scss`. `wc-fade-in`, `wc-fade-out`, `wc-fade-text-out` and `wc-hold-visible` only fade or hold. The movement keyframes, `wc-rise`, `wc-grow` and `wc-shrink`, animate `translate`, `scale` and `filter`, never `opacity` or `transform`. They combine with a fade, and with an element's resting `transform`. Position an element that grows or shrinks in with `translate` rather than `transform`, so it scales about its own centre.

Prefer `@starting-style` with transitions for an entrance when the element is new to the page and what it animates exists at rest, as for the address suggestions: everything is then a transition, which turns around by itself. It also applies when an element goes from `display: none` to shown. Use keyframes when the motion only exists while animating, such as a row rising in.

Motion that changes the layout runs in JavaScript, because it measures sizes; the [motion skill](../../../../../../.ai/skills/woocommerce-cart-checkout-motion/SKILL.md) describes those helpers. They take their timings from the same tokens, and finish straight away when nothing is animating, and in Jest.

## Reduced motion

Reduced motion means nothing moves, not that nothing changes: fades and colour changes still play, because they show what changed without moving anything. Movement (anything with `transform`, `translate`, `scale`, `clip-path`, or a size change) runs only for shoppers who haven't asked for reduced motion, inside `@include motion-safe` (`@media not (prefers-reduced-motion)`), with resting values outside it. The helpers do this for you.

- A transition that only fades or changes colour (`opacity`, colours, `box-shadow` focus rings) runs outside `motion-safe`. `motion-transition` writes the fade-only list outside and adds the movement inside.
- Content appearing or going fades in or out: `motion-enter` and `motion-exit` write the fade outside `motion-safe` and add the movement inside it.
- Movement that only adds feel (press scale, success pulse, the Ship/Pickup indicator, price rolls, content moving to make room) has no reduced variant.
- The spinner shows still, half way through a turn, and the skeleton shimmer stops.

Stylelint checks this (`woocommerce/motion-safe`), and that durations use tokens and transitions never use `all`, in the Cart and Checkout stylesheets and the components they share. It accepts `motion-state()`, `motion-hold()`, and the fade and hold keyframes outside `motion-safe`.

## General rules

- Animate `transform`, `opacity`, `filter`, and `clip-path`. Never animate layout properties, except as listed in Exceptions.
- Colour, background, border colour, opacity, and `box-shadow` focus rings may transition over `--wpds-motion-duration-md` with `--wpds-motion-easing-subtle`. WPDS uses 100ms, which is too quick to see.
- Don't use `linear` easing, even where WPDS does: it makes UI motion feel mechanical. Use one of the easing tokens. The exception is constant motion, such as the spinner's turn and the skeleton shimmer, which is what `linear` is for.
- List the properties you transition.
- Scope new effects to `.wp-block-woocommerce-cart` and `.wp-block-woocommerce-checkout`.
- **Interrupted motion turns around.** When something changes direction part way through, such as a panel closed while it's still opening, it continues from where it is. Transitions do this on their own; motion run from JavaScript reads where it had got to and continues from there. Nothing blocks clicks while it animates.
- **Keyboard changes apply at once.** Changes made with the keyboard, such as arrowing through options, are repeated quickly, so sections the shopper shows, hides or swaps, and the Ship/Pickup indicator, skip after keyboard input, and a radio group's highlight and dot change at once while one of its options has keyboard focus.
- **Rest on whole pixels.** Anything that stays transformed at rest, such as a rolling price's digit columns, moves in whole-pixel steps (`round(1lh, 1px)`), and no animation keeps its end value applied except on content that's about to unmount. Fractional resting positions leave digits and edges visibly off on some screens.

## Rules

The numbers match the original motion rules, which code comments cite.

**1. Entrances ease out** with `--wpds-motion-easing-expressive`.

**2. Dropdowns follow WPDS, without the linear fade:** slide 4px from the trigger over `lg` expressive, fade in over `md` expressive, and slide back over `md` to close.

**3. Stagger list rows by 50ms** with `motion-stagger-delay()`, capped at 7 rows. Rows inside a section that's entering as a whole, such as pickup locations as Pickup swaps in or shipping options in a growing section, arrive with it instead: `motion-stagger` turns off under `data-wc-entering` and `data-wc-swapping`.

**4. Buttons scale to `$wc-scale-press` while pressed**, over `sm`.

**5. Moving indicators stretch about 12% along their path, keeping their area**, then settle.

**6. Secondary details settle one `xs` step after their parent**, with `motion-stagger-delay(1)`.

**8. Pulse once to confirm success**, 1 → `$wc-scale-pulse-peak` → 1, over `lg` balanced. Never loop it.

**9. On-screen moves use `--wpds-motion-easing-balanced`.**

**13. No bounce**, apart from the success pulse.

**15. Exits are shorter than entrances**: movement that enters over `lg` leaves over `md`, with its own keyframes. State and in-place changes take `md` both ways.

**16. Overlap old and new states.** Never exit, pause, then enter.

**17. Make room by moving what's below.** Content that appears grows from nothing as everything after it moves down, and content that goes collapses as everything after it moves up, borders included, instead of appearing or vanishing in place.

**18. Swap icons with scale, opacity, and blur** (`$wc-scale-icon-from`, `$wc-blur-icon`).

**20. Animate the same object between views**, such as an address card becoming its form, instead of swapping in a different element.

**21. One change, one movement (Woo rule).** When a choice swaps a group of sections, such as Ship/Pickup, the group changes as one: its height changes once, so everything below moves once, in one direction. The old sections fade out over `sm` while moving 24px the way the selection moved, and the new ones fade in over `md` after them, sliding in from the other side over `lg` expressive, so all the motion follows the shopper's choice. Every section from the first that changes to the last moves with the group, including one in both states that sits between them, such as a billing address, so nothing stays still in the middle of the slide. Sections after the group hold their old places until the old sections have faded, so content that loads in the meantime, such as pickup locations, is in place before anything moves; then they slide straight to their new places. Under reduced motion the sections only fade; after a key press the change applies at once.

**Price roll (Woo rule).** A price that changes in place rolls its digits over `md` expressive. Prices that change together, such as a line total and its "Save" badge, roll from the same data, so they move at the same time. Prices don't roll while the cart first loads.

**Skeleton reveal (Woo rule).** A price or list that comes back from a loading skeleton, such as the cart totals, fades in over `md` subtle. It doesn't roll: the old value isn't on screen to roll from. All skeletons in a loading phase end together, and totals rows that appear or go and the summary bar's total wait for that moment, so everything changes at once. Cart line totals don't wait: while a new quantity saves, they show the unit price times that quantity, unless the item's pricing isn't linear.

## Exceptions

- **Content that makes room animates its size**: panels, the mobile order summary, totals rows, notices, field errors, the sections the shopper shows or swaps, and the cart's height as a row goes. It's real layout, so themes' borders, spacing and extension markup move with it, and it turns around when interrupted. It runs for 300ms in and 200ms out, on small areas; cart rows can't change size, so they slide with `transform` instead.
- **Outline focus rings stay instant:** a transparent outline would show in Windows high contrast mode.
- **Floating labels animate `top`**, because themes and extensions position them with `top`.
