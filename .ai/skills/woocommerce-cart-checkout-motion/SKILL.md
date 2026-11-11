---
name: woocommerce-cart-checkout-motion
description: Add or review motion (transitions, animations, layout changes) in the WooCommerce Cart and Checkout blocks and the components they share. Use when animating a component, choosing timings or easing, handling reduced motion, or fixing motion that jumps, flickers or blocks.
---

# Cart and Checkout motion

Use this skill to add motion to the Cart and Checkout blocks (`plugins/woocommerce/client/blocks`) and the shared components they render, or to review motion someone else added. The Mini-Cart is out of scope.

The rules live in `plugins/woocommerce/client/blocks/docs/contributors/motion.md`. Read that first: it's the source of truth for tokens, durations, reduced motion and the numbered rules, which code comments cite (for example "rule 17"). This skill is the how: which technique fits which change, the constraints a solution has to meet, the traps to avoid, and how to prove it works.

## Workflow

1. **Name the change.** Write one sentence for what changes on screen and what triggers it (the shopper, loading data, or the page loading). The trigger decides a lot: see "Who triggered it" below.
2. **Pick the technique** from the decision table. Don't invent a new one when a row fits.
3. **Use what exists.** Check "Helpers" below and the Helpers section of `motion.md`. Reuse a helper when one covers the technique. When none does, write one in `assets/js/base/utils` (or `assets/js/base/hooks` for a React hook) that meets the constraints here, so the next component can reuse it. Then add it to "Helpers" in this skill and to `motion.md`.
4. **Handle the edges:** reduced motion, keyboard input, interruption, content that loads late, and several things changing at once.
5. **Verify in a real browser** (`references/verification.md`), then run stylelint, ESLint, Jest and the build.
6. **Write it down.** Comments say why (cite the rule number), not what.

## Decision table

| What changes | Technique |
|---|---|
| Colour, background, border colour, opacity, focus ring | CSS transition, 200ms subtle, both ways. |
| Something dims while busy or disabled | Opacity to 0.5, fading both ways. |
| A button is pressed | Scale to `$wc-scale-press` over 100ms while held. |
| A button shows a spinner while busy | The label fades out while the spinner grows in with scale and blur. |
| A small icon, dot or tick changes | Scale from `$wc-scale-icon-from` with a fade; icons add a blur (rule 18). |
| Content mounts in place (an empty state, rows on first load) | Rise 4px over 300ms while fading in over 200ms. |
| A list's rows mount | The same rise, 50ms apart, capped at 7 rows (rule 3). |
| A detail belongs to a row, such as a quantity badge | It settles one 50ms step after its row (rule 6). |
| A small item is removed, such as a chip | It stays where it was while it fades and shrinks out, then unmounts. |
| Success is confirmed, such as a coupon applied | One pulse, 1 → `$wc-scale-pulse-peak` → 1 over 300ms balanced, only for what the shopper just did (rule 8). |
| A dropdown opens from a field | It slides 4px from the field over 300ms with a 200ms fade, and back over 200ms (rule 2). It stays mounted while it closes, and turns around if it reopens part way. |
| A toast or snackbar | It rises 8px in over 300ms and drops 4px out over 200ms. Script waits for the animation to end rather than a hard-coded timeout. |
| A value replaces its loading skeleton | It fades in over 200ms and never rolls: the old value isn't on screen to roll from. |
| A price changes in place | Its digits roll to the new value over 200ms expressive. The text stays the plain price for assistive tech. |
| Several skeletons belong to one update | They all end together, after a short minimum, and the values they reveal change at that same moment. |
| Content appears or goes and should push what's below (a panel, notice, field error or totals row) | It grows from nothing and collapses to nothing in the real layout, so what follows moves with it (rule 17). It stays mounted while it leaves, and turns around if interrupted. |
| A section the shopper shows or hides (an address, an order note, payment fields) | It grows in, and the section that leaves collapses. A form that leaves must stop being part of the form at once (see Traps). |
| A box's content changes size later (a description gains a line, a list loads) | The box's height eases to its new size. |
| One element becomes another view of itself (an address card becoming its form) | The two views crossfade while the box's height eases (rule 20). |
| A choice swaps several sections (Ship / Pickup) | One change, one movement (rule 21). |
| A selection moves between options (Ship / Pickup) | The selected style slides to the new option, stretching about 12% along its path while keeping its area (rule 5). |
| A table row is removed | It fades where it was while the rows below slide up into its place (they can't change height, so they move with `transform`), and the container's height eases. |
| A collapsible bar opens or closes (the mobile order summary) | Its content grows open and collapses closed, staying in place until it has collapsed. Closing because the shopper scrolled past applies at once. |

## Helpers

SCSS helpers live in `assets/css/abstracts/_motion.scss` and are available in every blocks stylesheet; `motion.md` lists them with examples. Shared keyframes (`wc-fade-in`, `wc-fade-out`, `wc-fade-text-out`, `wc-hold-visible`, `wc-rise`, `wc-grow`, `wc-shrink`) are in `assets/css/style.scss`.

| Technique | SCSS helper |
|---|---|
| State change | `transition: motion-state( … )` |
| A transition that fades and moves | `@include motion-transition( $state, $move, $duration, $easing, $hold )` |
| Hold a property until a fade ends | `motion-hold( … )` |
| Dim | `@include motion-dim( $states )` |
| Mount in place | `@include motion-enter( $keyframes, $duration, $delay )` |
| Stagger rows | `@include motion-stagger`, or `motion-stagger-index()` with `motion-stagger-delay( $extra )` |
| Remove, held until unmount | `@include motion-exit( $keyframes )` |
| Replace a skeleton | `@include motion-fade-in` |
| Spinner swap | `@include motion-spinner-swap` |
| Anything else that moves | `@include motion-safe { … }` |

Prefer `@starting-style` with transitions when an element is new to the page and what it animates exists at rest (a dropdown): everything is then a transition, which turns around by itself. Use keyframes when the motion only exists while animating (a row rising in).

`motion-stagger` turns itself off inside an element marked `[data-wc-entering]` or `[data-wc-swapping]`. Motion that grows or swaps a section in should set one of those while it runs, so rows inside it arrive with it (rule 3).

JavaScript helpers: none yet. Add each one here as it lands, with the technique it covers.

## Timing and easing

Durations and easing always come from the WPDS tokens (`motion-duration( lg )`, `motion-easing( balanced )`), never as literal values. Motion run from JavaScript must read the same tokens, for example from custom properties set from them on `:root`, so the tokens stay the one source.

| What | In | Out | Easing |
|---|---|---|---|
| Movement: position, size, a reveal | 300ms (`lg`) | 200ms (`md`) | Expressive for entrances and exits, balanced for moving or resizing on screen |
| State changes: colour, opacity, outline | 200ms | 200ms | Subtle |
| Small changes in place: tick, dot, icon, price roll | 200ms | 200ms | Expressive |
| Press | 100ms (`sm`) | 100ms | Expressive |
| Stagger step, detail settling | 50ms (`xs`) | | |

Opacity always runs on the 200ms state timing, even when the movement takes 300ms. Never use `linear` except for constant motion such as the spinner's turn and the skeleton shimmer. No bounce, except the one success pulse.

## Constraints every solution meets

- **Reduced motion means nothing moves, not that nothing changes.** Fades and colour changes still run. Movement (transform, translate, scale, clip-path, size changes) runs only inside `@include motion-safe`, with resting values outside it. Motion run from JavaScript checks `prefers-reduced-motion` itself and either only fades or applies the change at once.
- **Who triggered it.**
    - The shopper clicked or tapped: animate.
    - The shopper pressed a key (arrowing through radios, Space on a toggle): apply at once. Repeated key presses would queue motion.
    - Data loaded or the page rendered for the first time: don't animate sections into place. Content that changes because of loading (totals rows, notices, errors) can animate.
- **Interrupted motion turns around.** A change of direction part way through continues from where the element is, not from the start. CSS transitions do this on their own; scripted motion has to read the current values before replacing the old animation.
- **Nothing blocks.** Clicks and typing keep working while anything animates.
- **Real layout, not snapshots.** Animate the element itself (its height, padding, borders and margins together), so theme borders and extension markup move with it. Same-document view transitions were tried and dropped: they block clicks, can't turn around, distort bordered boxes, and Chrome blanks a section while something inside it animates.
- **One change, one movement (rule 21).** When one choice swaps a group of sections, everything from the first changed section to the last changes as one group, including sections in between that are in both states. What's below moves once, in one direction, and all sideways motion follows the way the selection moved.
- **Overlap old and new (rule 16).** Never exit, pause, then enter.
- **Exits are shorter than entrances (rule 15).**
- **Whole pixels at rest.** Anything that stays transformed at rest moves in whole-pixel steps (`round( 1lh, 1px )`), and no animation keeps its end value applied, except on content that's about to unmount.
- **Leave nothing behind.** When motion settles, the DOM is as it would be without motion: no extra elements, inline styles, attributes or held animations.
- **Jest has no Web Animations API, layout or `ResizeObserver`.** Every helper must finish at once there and not throw.

## Traps (each one cost a bug before)

- **Every script bundle gets its own copy of a module.** Cart, Checkout and the shared components package each bundle `base-utils`, so module-level state is duplicated. State that has to be shared between bundles lives on `globalThis`.
- **Cancelled and finished animations are not "in progress".** When reading where an animation got to, only count `playState` `running` or `paused`. A finished animation with `fill: forwards` keeps its end state applied until it's cancelled, which can leave an element collapsed long after it should have reset.
- **Starting a size animation inside a `ResizeObserver` callback** resizes the element again in the same frame, and the browser reports a "ResizeObserver loop" error. Start it from somewhere that runs before layout, such as a `MutationObserver` callback.
- **Nested motion fights.** A box that eases its own height must stand back while something inside it is growing or collapsing, or the two animate the same change twice. Only motion that changes size counts; fades, slides and price rolls don't.
- **Clipping cuts things off.** Clip only while a size animates, and only the element that animates. A parent clipped while its height eases on a different timing from its children's movement cuts them off: the Place order button once vanished this way.
- **Content that arrives after the change** (rates, pickup locations, totals) jumps, or looks like it's growing as a clip uncovers it. Either wait until it's in place before moving anything, or ease the box's height when it arrives.
- **Chrome can blank a region for frames** while something inside it animates during a snapshot-based transition. Another reason not to use view transitions here.
- **`!important` spacing can't be animated.** Animations can't override it. Drive it from a registered custom property (`@property`) that the animation can change.
- **A removed form must stop being a form at once.** If it stays mounted while it animates out, its validation errors and payment handlers stay registered, and Place order can hit them. Animate a stand-in instead. The stand-in must be inert and `aria-hidden`, without `id`, `name` or `for`, and with blank iframes (a copied payment iframe would load again). It also must not keep attributes that trigger entrances, or it replays them.
- **React 18 has no `inert` prop.** Spread `{ inert: '' }` to set the attribute.
- **Keyed lists:** keep keys stable, or entrances replay on every render.
- **Moving DOM between hosts restarts CSS animations** on it. Finish finite ones after the move.
- **A notice that flashes** for a frame during a state change moves everything around it, and the motion makes that visible. Fix the flash at its source (defer creating the notice until the state settles) rather than animating it.

## Scope and compatibility

- Scope new CSS to `.wp-block-woocommerce-cart` and `.wp-block-woocommerce-checkout`, or to a component class those blocks render. Shared components (`packages/public-api/blocks-components`) are public: changes to their props, classes or DOM need a backwards-compatibility note in the PR.
- Never add a required prop to a public component; add optional ones. Keep class names that themes may target.
- Transitions list their properties; never `transition: all`.
- Position things that scale with `translate`, not `transform`, so a theme's resting `transform` still applies.

## Stylelint

`woocommerce/motion-safe` (in `bin/stylelint-plugins/motion.js`) and the `declaration-property-value-disallowed-list` override in `.stylelintrc.json` check the Cart and Checkout stylesheets and the components they share: movement must be inside `motion-safe` or a motion mixin, durations must be tokens, and `transition: all` is rejected. When you bring a new directory under motion, add it to the override's `files` list.

## Checklist before finishing

- [ ] The technique matches the decision table, and the timings match the timing table.
- [ ] Reduced motion: nothing moves; fades still run; nothing is left half-hidden.
- [ ] Keyboard: the change applies at once.
- [ ] Interrupting it (clicking again part way) turns it around without a jump.
- [ ] Nothing is left behind after it settles.
- [ ] Content below moves smoothly, in one direction, and nothing it holds (such as Place order) disappears for a frame.
- [ ] Stylelint, ESLint, Jest and the build pass.
- [ ] A new helper is listed in "Helpers" here and in `motion.md`.
