---
name: woocommerce-blocks-motion
description: Add or review motion (transitions, animations, layout changes) in WooCommerce blocks and the components they share. Use when animating a component, choosing timings or easing, handling reduced motion, or fixing motion that jumps, flickers or blocks.
---

# WooCommerce blocks motion

Use this skill to add motion to WooCommerce blocks (`plugins/woocommerce/client/blocks`) and the shared components they render, or to review motion someone else added.

**Read `plugins/woocommerce/client/blocks/docs/contributors/motion.md` first, all of it.** It holds the rules: tokens, durations, the helpers, reduced motion, the general rules, the numbered rules that code comments cite (for example "rule 17"), and the exceptions. This skill doesn't repeat them. It adds the workflow, which technique fits which change, the traps that have caused bugs, and how to prove motion works.

## Workflow

1. **Name the change.** Write one sentence for what changes on screen and what triggers it: the shopper, a key press, or data loading (see "Animate what the shopper did" in `motion.md`).
2. **Pick the technique** from the table below, and read the rules it cites.
3. **Use what exists.** Use the helpers in the Helpers section of `motion.md`. When none covers the technique, write one in `assets/js/base/utils` (or `assets/js/base/hooks` for a React hook) that follows the rules and avoids the traps below, so the next component can reuse it, and add it to that Helpers section.
4. **Handle the edges:** reduced motion, keyboard input, interruption, content that loads late, and several things changing at once.
5. **Verify in a real browser** (`references/verification.md`), then run stylelint, ESLint, Jest and the build.
6. **Write it down.** Comments say why, citing the rule number, not what.

## Techniques

| What changes | Technique | Rules |
|---|---|---|
| Colour, background, border colour, opacity, focus ring | A state-change transition, both ways | Durations; General rules |
| Something dims while busy or disabled | Opacity down to half, fading both ways | Durations |
| A button is pressed | A slight scale down while held | 4 |
| A button shows a spinner while busy | The label fades out as the spinner grows in | 18 |
| A small icon, dot or tick changes | Scale up from slightly smaller with a fade; icons add blur | 18 |
| Content mounts in place, such as an empty state | Rise in while fading in | 1 |
| A list's rows mount | The same rise, staggered | 3 |
| A detail belongs to a row, such as a quantity badge | It settles a step after its row | 6 |
| A small item is removed, such as a chip | It stays in place while it fades and shrinks, then unmounts | 15, 16 |
| Success is confirmed, only for what the shopper just did | One pulse | 8 |
| A dropdown opens from a field | It slides from the field; it stays mounted while closing and turns around if reopened | 2 |
| A toast or snackbar | It rises in and drops out; script waits for the animation, not a fixed timeout | 1, 15 |
| A value replaces its loading skeleton | A fade, never a roll | Skeleton reveal |
| A price changes in place | Its digits roll; its text stays the plain price for assistive tech | Price roll |
| Several skeletons belong to one update | They end together, and what they reveal changes at that moment | Skeleton reveal |
| Content appears or goes and should push what's below, such as a panel, notice, field error or totals row | It grows and collapses in the real layout; it stays mounted while leaving | 17; Exceptions |
| A section the shopper shows or hides, such as an address or payment fields | The new one grows in and the old one collapses; a form that leaves stops being a form at once (see Traps) | 17 |
| A box's content changes size later, such as a list that loads | The box's height eases to its new size | 17 |
| One element becomes another view of itself, such as an address card becoming its form | The views crossfade while the box's height eases | 20 |
| A choice swaps several sections, such as Ship / Pickup | One change, one movement | 21 |
| A selection moves between options | The selected style slides to the new option, stretching along its path | 5, 9 |
| A table row is removed | It fades where it was while the rows below slide up, and the container's height eases | 17; Exceptions |
| A collapsible bar opens or closes, such as the mobile order summary | Its content grows and collapses; closing because the shopper scrolled past applies at once | 17 |

## Traps (each one has caused a bug)

- **Every script bundle gets its own copy of a module.** Blocks such as Cart and Checkout, and the shared components package, each bundle `base-utils`, so module-level state is duplicated. State that has to be shared between bundles lives on `globalThis`.
- **Cancelled and finished animations are not "in progress".** When reading where an animation got to, only count `playState` `running` or `paused`. A finished animation with `fill: forwards` keeps its end state applied until it's cancelled, which can leave an element collapsed long after it should have reset.
- **Starting a size animation inside a `ResizeObserver` callback** resizes the element again in the same frame, and the browser reports a "ResizeObserver loop" error. Start it from somewhere that runs before layout, such as a `MutationObserver` callback.
- **Nested motion fights.** A box that eases its own height must stand back while something inside it is growing or collapsing, or the two animate the same change twice. Only motion that changes size counts; fades, slides and price rolls don't.
- **Clipping cuts things off.** Clip only while a size animates, and only the element that animates. A parent clipped while its height eases on a different timing from its children's movement cuts them off: the Place order button once vanished this way.
- **Content that arrives after the change** (rates, pickup locations, totals) jumps, or looks like it's growing as a clip uncovers it. Either wait until it's in place before moving anything, or ease the box's height when it arrives.
- **Chrome can blank a region for frames** while something inside it animates during a snapshot-based (view) transition.
- **`!important` spacing can't be animated.** Animations can't override it. Drive it from a registered custom property (`@property`) that the animation can change.
- **A removed form must stop being a form at once.** If it stays mounted while it animates out, its validation errors and payment handlers stay registered, and Place order can hit them. Animate a stand-in instead. The stand-in must be inert and `aria-hidden`, without `id`, `name` or `for`, and with blank iframes (a copied payment iframe would load again). It also must not keep attributes that trigger entrances, or it replays them.
- **React 18 has no `inert` prop.** Spread `{ inert: '' }` to set the attribute.
- **Keyed lists:** keep keys stable, or entrances replay on every render.
- **Moving DOM between hosts restarts CSS animations** on it. Finish finite ones after the move.
- **A notice that flashes** for a frame during a state change moves everything around it, and the motion makes that visible. Fix the flash at its source (defer creating the notice until the state settles) rather than animating it.
- **Jest has no Web Animations API, layout or `ResizeObserver`.** Every helper must finish at once there and not throw.

## Checklist before finishing

- [ ] The technique matches the table, and timings and easing follow `motion.md`.
- [ ] Reduced motion: nothing moves; fades still run; nothing is left half-hidden.
- [ ] Keyboard: the change applies at once.
- [ ] Interrupting it (clicking again part way) turns it around without a jump.
- [ ] Nothing is left behind after it settles (`references/verification.md`).
- [ ] Content below moves smoothly, in one direction, and nothing it holds (such as Place order) disappears for a frame.
- [ ] Stylelint, ESLint, Jest and the build pass.
- [ ] A new helper is listed in the Helpers section of `motion.md`.
