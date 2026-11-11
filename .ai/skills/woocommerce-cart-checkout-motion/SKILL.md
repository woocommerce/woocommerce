---
name: woocommerce-cart-checkout-motion
description: Add or review motion (transitions, animations, layout changes) in the WooCommerce Cart and Checkout blocks and the components they share. Use when animating a component, choosing timings or easing, handling reduced motion, or fixing motion that jumps, flickers or blocks.
---

# Cart and Checkout motion

Use this skill to add motion to the Cart and Checkout blocks (`plugins/woocommerce/client/blocks`) and the shared components they render, or to review motion someone else added. The Mini-Cart is out of scope.

The rules live in `plugins/woocommerce/client/blocks/docs/contributors/motion.md`. Read that first: it's the source of truth for tokens, durations, reduced motion and the numbered rules, which code comments cite (for example "rule 17"). This skill is the how: which technique fits which change, the helpers to use, the traps to avoid, and how to prove it works.

## Workflow

1. **Name the change.** Write one sentence for what changes on screen and what triggers it (the shopper, loading data, or the page loading). The trigger decides a lot: see "Who triggered it" below.
2. **Pick the technique** from the decision table. Don't invent a new one when a row fits.
3. **Use the helpers.** SCSS mixins from `assets/css/abstracts/_motion.scss`; JavaScript helpers from `assets/js/base/utils` and `assets/js/base/hooks`. If a helper the table names doesn't exist yet, create it as `references/helpers.md` specifies, in the same place and with the same API, and add it to the Helpers section of `motion.md`.
4. **Handle the edges:** reduced motion, keyboard input, interruption, content that loads late, and several things changing at once.
5. **Verify in a real browser** (`references/verification.md`), then run stylelint, ESLint, Jest and the build.
6. **Write it down.** Comments say why (cite the rule number), not what. Update `motion.md` when you add a helper or a rule.

## Decision table

| What changes | Technique | Helper |
|---|---|---|
| Colour, background, border colour, opacity, focus ring | CSS transition, 200ms subtle, both ways | `transition: motion-state( … )` |
| Something dims while busy or disabled | Opacity to 0.5, fading both ways | `@include motion-dim( ".is-disabled" )` |
| A button is pressed | Scale to `$wc-scale-press` over 100ms | `motion-transition( $state: …, $move: scale, $duration: sm )` plus `:active` scale inside `motion-safe` |
| A button shows a spinner while busy | Label fades out, spinner grows in with blur | `@include motion-spinner-swap` |
| A small icon, dot or tick changes | Scale from `$wc-scale-icon-from` with a fade (and blur for icons) | `motion-transition( $state: opacity, $move: scale, $duration: md )`; `wc-grow` / `wc-shrink` |
| Content mounts in place (empty state, rows on first load) | Rise 4px over 300ms while fading in over 200ms | `@include motion-enter` |
| A list's rows mount | The same rise, 50ms apart, capped at 7 rows | `@include motion-stagger` (or `motion-stagger-index` and `motion-stagger-delay` by hand) |
| A detail belongs to a row, such as a quantity badge | Settles one 50ms step after its row | `motion-stagger-delay( 1 )` |
| A chip or small item is removed | Fade plus `wc-shrink`, held until unmount | `usePresenceList` + `@include motion-exit( wc-shrink )` |
| Success is confirmed (a coupon applied) | One pulse, 1 → `$wc-scale-pulse-peak` → 1 over 300ms balanced | Own keyframes inside `motion-safe`, only for what the shopper just did |
| A dropdown opens from a field | Slide 4px from the field over 300ms, fade 200ms; back over 200ms | `@starting-style` + `motion-transition( $state: opacity, $move: translate )`, `usePresence` keeps it mounted |
| A toast or snackbar | Rise 8px in over 300ms, drop 4px out over 200ms | `motion-transition` on the transition classes, `onAnimationsFinished` as the end listener |
| A value replaces its loading skeleton | Fade in over 200ms; never roll | `@include motion-fade-in` |
| A price changes in place | Digits roll over 200ms expressive | `RollingPrice`, or `animate` on `ProductPrice` / `ProductSaleBadge` |
| Several skeletons in one update | All end together, minimum 300ms | `useDelayedSkeleton` / `useSettledOrderSummaryValue` |
| Content appears or goes and should push what's below (panel, notice, field error, totals row) | Grow from nothing and collapse to nothing in the real layout, borders and margins included | `usePresence( isOpen, { collapse } )`, `usePresenceList( …, { collapse: true } )`, `useTotalsRow` |
| A whole section the shopper shows or hides (billing address, order note, password, payment fields) | Grow in; a collapsing inert copy replaces the one that leaves | `useSectionCollapse( shown, getElement )` |
| A box's content changes size later (a description gains a line, a list loads) | Ease the box's height | `useHeightChangeMotion()` ref, or `animateHeightChange` |
| One element becomes another view of itself (address card → form) | Crossfade the two while the wrapper's height eases | `.is-animated` crossfade + `animateHeightChange` |
| A choice swaps several sections (Ship / Pickup) | One movement: old sections fade out drifting the way the selection moved, new ones slide in from the other side, everything below moves once (rule 21) | `captureSwap` / `playSwap` |
| A selection moves between options (Ship / Pickup) | A copy of the selected style slides and stretches 12% along its path, keeping its area | Indicator element with move and stretch keyframes; restart it with a new `key` per move |
| A table row is removed | A faded copy where it was, rows below slide up (FLIP), the container's height eases | `useRowRemovalMotion` pattern: `fadeAway`, `slideFrom`, `animateHeightChange` |
| A collapsible bar (mobile order summary) | Grow open, collapse closed; content stays in place until it has collapsed | `collapseIn` / `collapseOut` on the host, `resetCollapse` once the content has moved out |

`references/patterns.md` describes how each existing component in the blocks does this, as worked examples.

## Timing and easing

Durations and easing always come from the WPDS tokens through the helpers (`motion-duration( lg )`, `motion-easing( balanced )`), never as literal values. Scripted motion reads the same tokens from custom properties on `:root` (see `references/helpers.md`).

| What | In | Out | Easing |
|---|---|---|---|
| Movement: position, size, a reveal | 300ms (`lg`) | 200ms (`md`) | Expressive for entrances and exits, balanced for moving or resizing on screen |
| State changes: colour, opacity, outline | 200ms | 200ms | Subtle |
| Small changes in place: tick, dot, icon, price roll | 200ms | 200ms | Expressive |
| Press | 100ms (`sm`) | 100ms | Expressive |
| Stagger step, detail settling | 50ms (`xs`) | | |

Opacity always runs on the 200ms state timing, even when the movement takes 300ms. Never use `linear` except for constant motion such as the spinner's turn and the skeleton shimmer. No bounce, except the one success pulse.

## Rules that decide most choices

- **Reduced motion means nothing moves, not that nothing changes.** Fades and colour changes still run. Movement (transform, translate, scale, clip-path, size changes, scripted layout motion) runs only inside `@include motion-safe`, with resting values outside it. Scripted helpers check `prefers-reduced-motion` themselves and either only fade or apply the change at once.
- **Who triggered it.**
    - The shopper clicked or tapped: animate.
    - The shopper pressed a key (arrowing through radios, Space on a toggle): apply at once. Repeated key presses would queue motion.
    - Data loaded or the page rendered for the first time: don't animate sections into place; only content changes that follow loading (totals rows, notices, errors) animate.
- **Interrupted motion turns around.** A change of direction part way through continues from where the element is. CSS transitions do this on their own. Scripted motion reads the current computed values before cancelling the old animation. Nothing blocks clicks while it animates.
- **Real layout, not snapshots.** Animate the element's own height, padding, borders and margins, so themes' borders and extension markup move with it. Same-document view transitions were tried and dropped: they block clicks, can't turn around, clip bordered boxes, and Chrome blanks a section while something inside it animates.
- **One change, one movement (rule 21).** When one choice swaps a group of sections, everything from the first changed section to the last moves as one group, and what's below moves once, in one direction.
- **No stagger inside something that's entering as a whole (rule 3).** Rows inside a section that grows or swaps in arrive with it. `motion-stagger` turns off under `[data-wc-entering]` and `[data-wc-swapping]`.
- **Overlap old and new (rule 16).** Never exit, pause, then enter.
- **Exits are shorter than entrances (rule 15).**
- **Whole pixels at rest.** Anything that stays transformed at rest moves in whole-pixel steps (`round( 1lh, 1px )`), and no animation keeps its end value applied, except on content that's about to unmount.

## Traps (each one cost a bug before)

- **Every script bundle gets its own copy of a module.** Cart, Checkout and the shared components package each bundle `base-utils`, so module-level state is duplicated. State that has to be shared between bundles, such as the map of running animations, lives on `globalThis`.
- **Cancelled and finished animations are not "in progress".** When reading where an animation got to, only count `playState` `running` or `paused`. A finished `fill: forwards` animation leaves an element collapsed until it's cancelled.
- **Starting a size animation inside a `ResizeObserver` callback** reports a "ResizeObserver loop" error. Start it from a `MutationObserver` callback (before layout and paint), and use the `ResizeObserver` only to track the last size.
- **Height easing must not fight nested motion.** Skip a box's own height easing while something inside it is growing or collapsing. Count only animations that change size (script animations of height, padding, margins or border widths, and CSS transitions of those). Fades, slides and price rolls don't count.
- **Clipping.** Clip (`overflow: clip` with `overflow-clip-margin: 4px`) only while a size animates, and only the element that animates. Never ease a parent's height with a clip and a different duration from its children's movement; content gets cut off (Place order once vanished this way).
- **Content that loads after a swap or entrance** (pickup locations, shipping options): hold the motion until it's in place, or ease the box's height when it arrives. Don't let it uncover itself from the top inside a clip; that reads as the item growing.
- **`!important` spacing can't be animated.** Scale it with the registered `--wc-collapse-scale` property instead: `padding: calc( #{ $gap } * var( --wc-collapse-scale, 1 ) ) !important`.
- **Copies of removed content** must be inert, `aria-hidden`, have no `id`, `name` or `for`, have blank iframes (a copied payment iframe would load again), and must not carry attributes that replay entrances (for example `data-cart-item-key`).
- **React 18 has no `inert` prop.** Spread `{ inert: '' }` to set the attribute.
- **Keyed lists:** keep keys stable, or entrances replay on every render.
- **Notices that flash** for a frame during a state change move everything around them. Fix the flash at its source (defer creating the notice until the state settles) rather than animating it.
- **Jest has no Web Animations API or `ResizeObserver`.** Every helper must finish at once there and not throw.

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
- [ ] Nothing is left behind after it settles: no copies, no inline `height`, `overflow`, `translate` or `position`, no `data-wc-*` markers, no running or forward-filled animations.
- [ ] Content below moves smoothly, in one direction, and nothing it holds (such as Place order) disappears for a frame.
- [ ] Stylelint, ESLint, Jest and the build pass, and `motion.md` lists any new helper.
