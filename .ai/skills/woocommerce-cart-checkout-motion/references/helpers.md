# Motion helpers

The SCSS helpers exist in every blocks stylesheet. The JavaScript helpers land with the first component that needs them: if one doesn't exist yet, create it here, with this API, in this file, and add it to the Helpers section of `docs/contributors/motion.md`. Paths are relative to `plugins/woocommerce/client/blocks`.

## SCSS (`assets/css/abstracts/_motion.scss`)

Every helper writes the fade or colour change once (it always runs) and adds movement inside `motion-safe`.

| Helper | Use |
|---|---|
| `@include motion-safe { … }` | Movement the helpers don't cover. Compiles to `@media not (prefers-reduced-motion)`. |
| `motion-duration( xs \| sm \| md \| lg )`, `motion-easing( expressive \| balanced \| subtle )` | Token `var()`s. The build adds fallbacks for the storefront; never write one yourself. |
| `transition: motion-state( prop, … )` | State changes: 200ms subtle. |
| `motion-hold( prop, … )` | Keeps a property (such as `visibility`) at its old value until a 200ms fade ends (`step-end`). |
| `motion-move( props, $duration: lg, $easing: expressive )` | A movement transition list (used by `motion-transition`). |
| `@include motion-transition( $state: (), $move: (), $duration: lg, $easing: expressive, $hold: () )` | State properties always transition; `$move` properties only when motion is allowed. |
| `@include motion-dim( $states, $opacity: 0.5 )` | Dims while `$states` matches (`".is-disabled"`), fading both ways. `@content` adds to the dimmed state. |
| `@include motion-fade-in( $delay: null )` / `motion-fade-out` | Fades with no movement. Fade-out holds the end (`forwards`). |
| `@include motion-enter( $keyframes: wc-rise, $duration: lg, $delay: null )` | Content appearing: fade over 200ms subtle, plus `$keyframes` with expressive easing when motion is allowed. |
| `@include motion-exit( $keyframes )` | Content going: fade out over 200ms, plus `$keyframes` over 200ms expressive, both held until unmount. |
| `@include motion-spinner-swap` | On a button's loading class: the label fades with `wc-fade-text-out`, kept visible with `wc-hold-visible`, and the spinner enters with `motion-enter( wc-grow, md )`. |
| `@include motion-stagger-index( $cap: 7 )`, `motion-stagger-delay( $extra: 0 )` | Sets `--wc-motion-index` by `:nth-child`, and returns `index + extra` times `xs`. |
| `@include motion-stagger( $keyframes: wc-rise, $cap: 7 )` | Both of the above with `motion-enter`. Turns itself off under `[data-wc-entering]` and `[data-wc-swapping]`. |
| `$wc-scale-press: 0.97`, `$wc-scale-pulse-peak: 1.05`, `$wc-scale-icon-from: 0.8`, `$wc-blur-icon: 4px` | Woo constants WPDS doesn't define. |

Shared keyframes in `assets/css/style.scss`: `wc-fade-in`, `wc-fade-out`, `wc-fade-text-out` (fades `-webkit-text-fill-color`, leaving a spinner drawn with `color`), `wc-hold-visible`, `wc-rise` (from `translate: 0 4px`), `wc-grow` (from `scale` icon-from and `filter: blur`), `wc-shrink` (to the same). Movement keyframes never animate `opacity` or `transform`, so they combine with a fade and with a resting `transform`.

Prefer `@starting-style` with transitions when an element is new to the page and what it animates exists at rest (the address suggestions): everything is a transition, so it turns around by itself. Use keyframes when the motion only exists while animating (a row rising in).

## Timing for scripted motion

Scripted helpers read their timings from custom properties on `:root` in `assets/css/style.scss`, so the tokens stay the one source:

```scss
:root {
	--wc-motion-duration-move-in: #{motion-duration(lg)};
	--wc-motion-duration-move-out: #{motion-duration(md)};
	--wc-motion-duration-fade: #{motion-duration(md)};
	--wc-motion-duration-quick: #{motion-duration(sm)};
	--wc-motion-easing-move: #{motion-easing(balanced)};
	--wc-motion-easing-fade: #{motion-easing(subtle)};
	--wc-motion-easing-slide: #{motion-easing(expressive)};
}

// For spacing set with `!important`, which animations can't override.
@property --wc-collapse-scale {
	syntax: "<number>";
	inherits: false;
	initial-value: 1;
}
```

A `timing( 'in' | 'out' )` function reads them with `getComputedStyle( document.documentElement )`, converts `ms`/`s` to milliseconds, and falls back to 300 / 200 / 200 / 100 and `ease-in-out` / `ease-out` when a property is missing.

## `onAnimationsFinished( element, callback )` (`assets/js/base/utils/on-animations-finished.ts`)

Calls `callback` once the finite animations and transitions on `element` and its descendants finish (`getAnimations( { subtree: true } )`, ignoring `iterations: Infinity`), or at once when none run. Returns a cancel function. It also works as a react-transition-group `addEndListener` (snackbars), so JavaScript never hard-codes a duration.

## `usePresence( isOpen, { data, enterOnMount, collapse } )` (`assets/js/base/hooks/use-presence.ts`)

Keeps content mounted while it animates out.

- Returns `{ isPresent, transition, className, ref, data }`. `transition` is `'entering' | 'exiting' | null`; `className` is `is-entering` / `is-exiting`. While exiting, `data` is the last value seen while open, so the content doesn't empty out as it leaves.
- Set the transition in the same render as the change (compare against a `wasOpen` state), so closing content never unmounts for a frame.
- In a layout effect on `transition`: if `collapse` is set, call `collapseIn` or `collapseOut` on `ref.current`, or on `ref.current.querySelector( collapse )` when `collapse` is a selector. Then `return onAnimationsFinished( ref.current, () => setTransition( null ) )`.
- `enterOnMount` starts in `entering` when mounted open (field errors).
- Exiting content gets `inert` (spread `{ inert: '' }`) so focus can't land in it.

## `usePresenceList( ref, items, getKey, { collapse } )` (`assets/js/base/hooks/use-presence-list.ts`)

`usePresence` for a keyed list (notices, coupon chips). Returns `[ { item, className } ]`: items added after the first render are `is-entering`, removed items stay at their old index, with their last value, as `is-exiting` until the animations inside `ref` finish. Track `knownKeys` in state so the render-phase update doesn't loop; keep the last items in a ref updated in a layout effect. With `collapse`, the layout effects call `collapseIn` on `:scope > .is-entering` and `collapseOut` on `:scope > .is-exiting`.

## Collapse helpers (`assets/js/base/utils/collapse.ts`)

- **Box:** height (border-box, from `getBoundingClientRect`), `paddingTop`, `paddingBottom`, `borderTopWidth`, `borderBottomWidth`, `marginTop`, `marginBottom` and `--wc-collapse-scale`, animated together so what follows moves with the element, borders included.
- **Shared running map:** `globalThis.wcCollapseAnimations ??= new WeakMap< HTMLElement, Animation[] >()`. It has to be global because every bundle has its own copy of this module.
- **`interrupt( element )`:** if any tracked animation is `running` or `paused`, read the current box and opacity; then cancel all tracked animations and forget them. A new animation starts from what was read.
- **`clip` / `unclip`:** inline `box-sizing: border-box`, `overflow: clip`, `overflow-clip-margin: 4px` while a size animates.
- **`collapseIn( element )`:** interrupt; quiet inner entrances; fade opacity from where it was (or 0) to 1 over the fade timing. Under reduced motion stop there. Otherwise clip and animate the box from where it was (or the empty box) to its natural box over the `in` move timing; unclip and end the quiet when it finishes.
- **`collapseOut( element )`:** interrupt; fade to 0 (`fill: forwards`); unless reduced motion, clip and animate the box to empty over the `out` move timing (`fill: forwards`). It stays collapsed until removed or reset.
- **`resetCollapse( element )`:** interrupt and unclip, for a container that stays in the page after its content has gone (the mobile summary host).
- **`isAnimating( element )`:** whether a tracked animation is running.
- **`quietInnerEntrances( element, attribute = 'data-wc-entering' )`:** sets the attribute (which turns `motion-stagger` off inside) and returns an end function. The end function removes it, then finishes any pending `CSSAnimation`s in the subtree, so the held-back entrances don't play late.
- **`inertCopy( element )`:** a deep clone with no `id`, `name` or `for`, `inert`, `aria-hidden`, `pointer-events: none`, and iframes without `src` / `srcdoc`.
- **`getPlace( element )` → `{ element, parent, next, previous }`** and **`collapseCopyOut( place )`:** for content React has already removed. It puts an inert copy back where the element was (before `next`, after `previous`, or at the end), collapses it, and removes it when done. It does nothing under reduced motion.
- **`animateHeightChange( element, fromHeight )`:** eases height from `fromHeight` (or from where a running animation got to) to the current height, with a clip, over the `in` or `out` timing depending on direction. It does nothing under reduced motion or when the difference is under 1px.
- **`slideFrom( element, offset )`:** `translateY( offset )` → none over the `in` move timing, for content that moved and can't resize (table rows). Not under reduced motion.
- **`fadeAway( element )`:** opacity 1 → 0 over the `out` fade, held.
- Every function returns early when `element` isn't an `HTMLElement` with `animate` (Jest).

## `wasRecentPointerInput()` and `isUsingKeyboard()` (`assets/js/base/utils/input-modality.ts`)

Capture-phase `keydown` sets keyboard mode; `pointerdown` clears it and records the time. `isUsingKeyboard()` reads the mode. `wasRecentPointerInput()` is true when the last input was a pointer within 1000ms (a named constant), so a section change straight after a click animates and one made by loading data doesn't.

## `useSectionCollapse( shown, getElement, { easeHeight } )` (`assets/js/base/hooks/use-section-collapse.ts`)

For a section the shopper shows, hides or swaps. Call it from a component that stays mounted (the block's frontend component, before its early `return null`).

- `shown` is `true`, or a key such as the selected payment method; falsy for nothing. `getElement()` returns the section's element (often `document.getElementById( … )`, since `FormStep` has no ref).
- In a layout effect with no dependency list: when `shown` changed, the last input was a recent pointer, and the section isn't inside a swap (`isInSwap`), call `collapseCopyOut( lastPlace )` and `collapseIn( element )`. Then record `lastShown` and `getPlace( element )`.
- With `easeHeight`, attach `useHeightChangeMotion` to the current element by calling its ref callback; detach on unmount.

## `useHeightChangeMotion()` (`assets/js/base/hooks/use-height-change-motion.ts`)

Returns a `useRefEffect` ref that eases the element's height when its content changes size (`TotalsWrapper` uses it).

- Skip in environments without `ResizeObserver`, `MutationObserver` or `getAnimations`.
- A `ResizeObserver` only records the last size.
- A `MutationObserver` (`childList`, `subtree`, `characterData`, `attributeFilter: [ 'class', 'hidden' ]`) compares the new size with the last. If the width is the same and the height differs by 1px or more, and nothing is moving, it calls `animateHeightChange( element, last.height )`.
- "Moving" means: the element is inside a swap that's still holding (`isSwapHolding`), or `isAnimating`, or any running animation in the subtree changes size. A script animation changes size when its keyframes include height, margin, padding or border width; a CSS transition when its `transitionProperty` is one of those. Never a CSS keyframe animation.

## Swapping sections (`assets/js/base/utils/swap-sections.ts`)

`captureSwap( container, direction )` in the event handler before the change, then `playSwap( snapshot )` in a layout effect once React has applied it. `direction` is the way the selection moved on screen: compare the option buttons' centres, so right-to-left works.

- **Capture:**
    - Record each child's rect (skipping old copies), remove old copies, and clear the inline `translate` holds of a swap still running.
    - Cancel the script animations (not the CSS ones) on the container and its children.
    - Mark the container with `data-wc-swapping` via `quietInnerEntrances`, and remember it as the latest swap.
    - If `playSwap` hasn't run within 1 second, end it.
- **Group:** the sections from the first changed one to the last, in the old order and in the new order, plus every section that left or arrived. A section that stays but sits inside that range (a billing address shown in both states) is in the group.
- **Play:**
    - Each group section's old rect gets an inert copy, absolutely positioned in the container. It fades out over 100ms while translating `24px * direction`.
    - Each group section fades in over 200ms after a 100ms delay (`fill: backwards`, so it's hidden until then).
    - For those 100ms, the sections after the group are held in their old places with an inline `translate`, recomputed every animation frame against wherever the layout now puts them (`data-wc-swap-holding` is set meanwhile). This means content that loads during the fade-out (pickup locations) is in place before anything moves.
    - After the 100ms: each held section slides from its held offset to 0 over 300ms balanced, and each group section slides in from `-24px * direction` over 300ms expressive.
    - Don't animate the container's height and don't clip it; the slides already move everything inside it.
- **Reduced motion:** copies and new sections only fade, and nothing is held.
- **Helpers:** `isInSwap( element )` and `isSwapHolding( element )` let other helpers stand back.

## Prices and loading

- **`RollingPrice` (`assets/js/base/components/rolling-price`):**
    - Props are `value` (minor units), `currency`, `className`, `style`, and `animate = true`.
    - The formatted price is split into parts: each digit becomes a column, and the characters between digits stay together.
    - A screen-reader span carries the full price; the visible characters are CSS-generated (`data-text` with `::before`), so the element's text is just the price.
    - Each digit column is a strip of 0–9 positioned with `--wc-rolling-price-digit`, line height `round( 1lh, 1px )`, and `tabular-nums`. The roll is `motion-transition( $move: transform, $duration: md )` unless `.is-static`.
    - Key columns by position from the right, so cents keep their column when the price gains a digit.
- **`ProductPrice` / `ProductSaleBadge`:** an optional `animate` prop renders `RollingPrice` for a single price changing in place. Sale prices and ranges ignore it.
- **Cart line totals:** while a new quantity saves, show the unit price times the new quantity. Only do this when the saved total is exactly that, so pricing that isn't linear waits for the server.
- **`useDelayedSkeleton( isLoading )` (in `delayed-content-with-skeleton`):**
    - The minimum display time is 300ms (it was 2s), measured from the start of the loading phase.
    - That start time is shared across skeletons, so one that mounts part way through a phase ends with the others. Share it on `globalThis` too, as bundles differ.
    - Under reduced motion it shows only while loading.
- **`useSettledOrderSummaryValue( value )`:** returns the value as it was before the order summary's skeletons appeared, until they end. Prices and totals rows that use it change at the same moment as the skeletons.
- **`useTotalsRow( cart, className, isShown )`:** the settled cart from `useSettledOrderSummaryValue`, plus `usePresence( isShown( settledCart ), { data: settledCart, collapse: true } )`. It returns the row's data and `wrapperProps` (`className`, `ref`) for `TotalsWrapper`, which forwards its ref. Keep `cart` as an argument: tests pass a mocked cart.
- Pass `animate={ ! cartIsLoading }` (or similar), so prices don't roll while the cart first loads.
