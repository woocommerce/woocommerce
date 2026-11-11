---
name: woocommerce-blocks-motion
description: Add or review motion (transitions, animations, layout changes) in WooCommerce blocks and the components they share. Use when you animate a component, choose timings or easing, handle reduced motion, or fix motion that jumps, flickers, or blocks.
---

# WooCommerce blocks motion

Use this skill to add motion to WooCommerce blocks (`plugins/woocommerce/client/blocks`) and the components they share. Also use it to review motion.

**First, read all of `plugins/woocommerce/client/blocks/docs/contributors/motion.md`.** It has the rules: tokens, durations, helpers, reduced motion, general rules, numbered rules, and exceptions. This skill does not repeat the rules. It gives the workflow, the technique for each type of change, the traps, and the checks.

## Workflow

1. **Describe the change.** Write one sentence: what changes on screen, and what causes it. The cause is the shopper, a key press, or data that loads.
2. **Select the technique** from the table below. Read the rules that it gives.
3. **Use the existing helpers** from the Helpers section of `motion.md`. If no helper does the technique, write one. Put it in `assets/js/base/utils`, or in `assets/js/base/hooks` for a React hook. Make it as small as the technique needs. Make sure that it obeys the rules and avoids the traps below. Add it to the Helpers section of `motion.md`.
4. **Do the edge cases:** reduced motion, keyboard input, interruption, content that loads late, and changes that occur at the same time.
5. **Test in a real browser** with `references/verification.md`. Then run stylelint, ESLint, Jest, and the build.
6. **Write comments that tell why, not what.** Give the rule number.

## Techniques

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

## Keep the change small

- Change only what the motion needs. Do not rewrite how a component works, and do not add, remove, or replace dependencies. If the component already has a mechanism that can carry the motion, use it. For example, `react-transition-group` can end a transition when the animation ends, with `addEndListener`.
- Do not add wrapper elements to the markup of a shared component. Themes and extensions can target that markup. Put the ref or class on an element that exists, or add an optional prop.
- Do not change `pnpm-lock.yaml` or `package.json` in a motion change.

## Traps

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
- **`react-transition-group` uses `findDOMNode` without `nodeRef`.** React deprecates it, and the warning fails Jest tests in StrictMode. Give each `CSSTransition` a `nodeRef`, and wait for the animations in `addEndListener`.
- **A fake animation in Jest must change its `playState` when it finishes.** If not, a helper that waits for animations that are in progress waits forever, and the test does not end.

## Checklist

- [ ] The technique matches the table. Timings and easing obey `motion.md`.
- [ ] Reduced motion: nothing moves, fades still run, and no element stays partly hidden.
- [ ] Keyboard: the change applies immediately.
- [ ] Interruption: a second click during the motion turns it around without a jump.
- [ ] Nothing stays after the motion ends (see `references/verification.md`).
- [ ] The content below moves smoothly and in one direction. No element in it, such as Place order, disappears for a frame.
- [ ] Stylelint, ESLint, Jest, and the build pass.
- [ ] Each new helper is in the Helpers section of `motion.md`.
