# Patterns in the Cart and Checkout blocks

How each part of the blocks moves, as worked examples of the decision table. Paths are relative to `plugins/woocommerce/client/blocks`. When you animate one of these areas, match this; when you animate something new, find the closest example.

## Shared building blocks

- **Buttons** (`base/components/button/style.scss`):
    - Inside Cart and Checkout, `motion-transition( $state: ( box-shadow, background-color, border-color, color, opacity ), $move: scale, $duration: sm )`. This replaces a theme's own button transition, so hover colours fade here too.
    - `&:active:not(:disabled):not([aria-disabled="true"])` sets `scale: $wc-scale-press` inside `motion-safe`.
- **Spinner swap:** `@include motion-spinner-swap` on the loading class of Place order (`place-order-button`), coupon Apply (`totals/coupon`) and Proceed to checkout. The Place order icon and spinner are centred with `translate`, not `transform`.
- **Spinner** (`blocks-components/spinner`): the turn runs inside `motion-safe`. Under reduced motion it rests half way through a turn.
- **Fields** (`blocks-components/text-input`):
    - The floating label uses `motion-transition( $state: color, $move: ( top, transform ), $duration: md, $easing: balanced )`. Themes position it with `top`, so `top` animates too.
    - The border uses `motion-state( border-color )`.
    - The date mask's reveal is delayed by `md` only when motion is allowed.
- **Checkboxes** (`blocks-components/checkbox-control`): the tick scales from `$wc-scale-icon-from` and draws in with `clip-path`, using `motion-transition( $state: opacity, $move: ( scale, clip-path ), $duration: md )`. Unchecked, it holds `visibility` and `clip-path` until the fade ends.
- **Radios** (`blocks-components/radio-control`, inside Cart and Checkout):
    - The dot (`::before`) is always rendered, hidden while unchecked, and centred with `translate: -50% -50%`. It scales from `$wc-scale-icon-from` with opacity, holding `background-color`.
    - The checked option's highlight fades with `motion-state( box-shadow )`.
    - While an option has `:focus-visible` (arrow keys), the dot and highlight transitions are `none`, so keyboard selection is instant.
    - Disabled options use `motion-dim`.
- **Quantity selector:** `motion-state( opacity, box-shadow )` on its buttons and input.
- **Loading mask:** its content dims with `motion-state( opacity )` (the content needs its own class).
- **Totals values:** a value replacing its skeleton gets `@include motion-fade-in` (`blocks-components/totals/item`).
- **`TotalsWrapper`** (public): `forwardRef`, with its ref merged (`useMergeRefs`) with `useHeightChangeMotion()`, so a row whose content changes height, such as Delivery gaining a description, eases.

## Cart

- **Rows on load** (`cart-line-items-table/style.scss`):
    - Target `.wp-block-woocommerce-cart .wc-block-cart-items__row[data-cart-item-key]`. Skeleton rows have no key, so they don't animate.
    - Set `motion-stagger-index()` on the row and `motion-enter( $delay: motion-stagger-delay() )` on `> td`. The cells animate, not the `<tr>`, so transforms work in table and grid layouts and the row's dim opacity is never overridden.
- **Busy rows:** `motion-dim( ".is-disabled" ) { pointer-events: none; }`.
- **Quantity change** (`cart-line-item-row.tsx`): the line total and "Save" badge use `ProductPrice` / `ProductSaleBadge` with `animate`. They show the unit price times the new quantity while it saves, when the pricing is linear.
- **Row removal** (`cart-line-items-table/use-row-removal-motion.ts`, `useRowRemovalMotion( tableRef, keys )`):
    - After every render, snapshot each keyed row's page position (rect top plus scroll), its left, and its cells' widths. Also record the table's width, the cart block's height, and the page tops of what follows: the rows, `.wp-block-woocommerce-cart-items-block > :not(table)`, and `.wp-block-woocommerce-cart-totals-block`.
    - When keys disappear, rebuild each removed row as an `inertCopy` (without `data-cart-item-key`, so it doesn't replay the entrance). Put it in a shallow clone of the table with `table-layout: fixed` and the old cell widths, positioned absolutely where the row was (work out the containing block's origin), and `fadeAway` it, then remove it.
    - `slideFrom` each element that moved by `oldTop - newTop`, and `animateHeightChange` the `.wp-block-woocommerce-cart` block from its old height.
- **Empty cart** (`empty-cart-block/frontend.tsx` and `style.scss`): only the empty state that replaces the shopper's last item enters (track a `hasHadItems` ref), via an `is-entering` class with `motion-enter`. A cart that loads empty renders as is.
- **Coupon panel** (`Panel`, public):
    - `usePresence( isOpen, { collapse: ':scope > .wc-block-components-panel__content' } )`.
    - Render the content while `isPresent`, with `inert` while exiting.
    - The button's open-state margin animates with `motion-transition( $move: margin-bottom, $easing: balanced )`, so the gap opens with the content.
- **Coupon chips** (`totals/discount`):
    - `usePresenceList( listRef, coupons, ( { code } ) => code )`, without collapse.
    - A removed chip keeps its place with `is-exiting`, is disabled, and plays `motion-exit( wc-shrink )`.
    - A chip pulses once (`is-newly-applied`: `scale` to `$wc-scale-pulse-peak` over `lg` balanced, inside `motion-safe`), only if the shopper just applied it. Track the codes from the cart store with `subscribe`: add when `getCouponBeingApplied()` returns a code, and forget codes that didn't make it into the cart. Not on mount: the cart unmounts the list while it has no coupons.
- **Totals rows** (Discount, Fees, Tax in the cart and checkout `*-order-summary-*/block.tsx`): `useTotalsRow( useStoreCart(), className, ( cart ) => shown )`, spreading `wrapperProps` onto `TotalsWrapper`.
- **Express payment buttons:** each button fades in (`motion-fade-in`) as it replaces its loading skeleton. Target `[id^="express-payment-method-"]`, since the skeleton's items have no id.
- **Snackbars** (`snackbar-list`):
    - On the transition classes: enter from `translate: 0 8px` to none over `lg` with the fade; exit to `translate: 0 4px` over `md` with the fade, all through `motion-transition`.
    - Pass `addEndListener={ onAnimationsFinished }` instead of a hard-coded `timeout`.
- **Store notices** (`store-notices-container`):
    - `usePresenceList( containerRef, notices, key, { collapse: true } )` inside a `NoticesContainer` that clones each notice with its motion class.
    - The banner's `!important` padding becomes `calc( #{ $gap } * var( --wc-collapse-scale, 1 ) ) !important`.
    - The container's margin animates with `motion-transition( $move: margin-block, $easing: balanced )` inside Cart and Checkout, so the space opens with the first notice.
    - The close button fades `opacity` with `motion-state`.

## Prices and loading

- **Place order** (`place-order-button`): the price is a `RollingPrice` with `animate={ ! cartIsLoading }`.
- **Order summary items** (`order-summary-item.tsx`): read the item through `useSettledOrderSummaryValue( cartItem )`, and pass `animate` to the prices.
- **Mobile summary bar:** the total is a `RollingPrice` that doesn't animate while loading.
- **Skeletons** (`delayed-content-with-skeleton`): all end together, with a 300ms minimum (see `useDelayedSkeleton`). Totals rows and the summary bar's total wait for that moment.

## Checkout

- **Order summary items:** `motion-stagger()` on the items. The quantity badge is positioned with `translate: 50% -50%` and enters with `motion-enter( wc-grow, $delay: motion-stagger-delay( 1 ) )`, one step after its item.
- **Shipping options and pickup locations:** `motion-stagger` on `.wc-block-components-radio-control__option` inside each block. The options are keyed by rate, so refreshing rates doesn't replay them, and they don't stagger inside a section that's entering or swapping.
- **Payment methods:** `.wc-block-checkout__payment-method .wc-block-components-radio-control` fades in (`motion-fade-in`) as it replaces its loading skeleton.
- **Legacy payment card label:** `motion-transition( $move: transform, $duration: md, $easing: balanced )`.
- **Field errors** (`validation-input-error`, public):
    - `usePresence( !! errorMessage, { data: errorMessage, enterOnMount: true, collapse: true } )`. The message is announced at once (`role="alert"`) and `aria-hidden` while it leaves.
    - Callers render the error component even when there's no message, so it can animate out. Examples: `payment-method-required-error.tsx`, and `validated-text-input.tsx` passing `''` when there's no error.
- **Sections the shopper shows or hides:**
    - `useSectionCollapse` in each block's frontend component, before its early `return null`, with `getElement` by id: billing (`billing-fields`), the order note's textarea, the create-account password field, and the address line 2 field together with its "+ Add" link (both by the input id).
    - Ship/Pickup's four sections (`shipping-fields`, `shipping-option`, `pickup-options`, `billing-fields`) use `{ easeHeight: true }`, so content that loads into them later eases.
- **Payment method fields** (`RadioControlAccordion`, public): `useSectionCollapse( selected, () => document.getElementById( `radio-control-${ id }-${ selected }__content` ) )`. The new method's content grows in as the old one's copy collapses.
- **Address card ⇄ form** (`address-wrapper`):
    - The card and form crossfade (`motion-state( opacity )`, with `motion-hold( visibility )` on the card, and the form `motion-fade-in`) while `.is-animated`.
    - The wrapper keeps its own ref and the last height. In a layout effect, when `isEditing` changed and `shouldAnimate` is set, it calls `animateHeightChange( wrapper, lastHeight )`.
    - Both the shipping and billing edit buttons set `shouldAnimate` before switching to editing.
- **Ship / Pickup selection** (`checkout-shipping-method-block/block.tsx` and `style.scss`):
    - On a change, render a `.wc-block-checkout__shipping-method-indicator` span over the newly selected option, with `--wc-shipping-method-indicator-offset` (from the old option's centre, or from the moving indicator's current rect when interrupted) and `--wc-shipping-method-indicator-stretch` (`1.12 0.893` along the travel).
    - Give it a new `key` per move, so the keyframes restart, and remove it when its animations finish.
    - The keyframes `wc-shipping-method-indicator-move` (`translate` from the offset) and `wc-shipping-method-indicator-stretch` (`scale`) run over `lg` balanced, inside `motion-safe`. While the indicator exists, the container gets `position: relative` and the selected option's own border and background go transparent (`:has( > .wc-block-checkout__shipping-method-indicator )`). The indicator is an absolutely positioned box with the selected style (a 2px `currentColor` border and the shared radius).
    - After a key press, don't render it.
- **Ship / Pickup sections** (`checkout-shipping-method-block/frontend.tsx`):
    - In `onChange`, before dispatching, set `swap.current = captureSwap( shippingMethodStep.parentElement, direction )`, unless the last input was the keyboard or the choice didn't change.
    - `direction` is the sign of Pickup's centre minus Ship's, negated when going to Ship.
    - A layout effect on `prefersCollection` calls `playSwap( swap.current )`.
- **Mobile order summary** (`checkout-order-summary-block/frontend.tsx`):
    - When `isOpen` changes, set `isOpening` (a ref) or `isClosing` (state), but only when the toggle is shown (`hasContainerWidth && ! isLarge`). Auto-closing as the shopper scrolls past stays instant: it sets `checkoutActionsTopBeforeMove` first, and its scroll correction expects the layout to change in one step.
    - The content stays inline (`is-open`, `inert`) while closing.
    - One layout effect, after the content has moved:
        - When opening: `collapseIn( host )`.
        - When closing: `collapseOut( host )`, then `onAnimationsFinished` → `setIsClosing( false )`.
        - Once it's closed: `resetCollapse( host )`, so a wider screen doesn't inherit a collapsed host.
    - Moving the content container between hosts restarts its CSS animations, so finish the finite ones after a move.
- **Order confirmation** (`order-confirmation/status`): the status blocks enter in sequence with `motion-enter( $delay: motion-stagger-delay() )`, each with a fixed `--wc-motion-index` slot.
- **Address suggestions** (`address-autocomplete`):
    - `usePresence( shown, { data: suggestions } )`, rendering while `isPresent`. Pass `containerRef` and `isExiting` to `Suggestions`, which sets `is-exiting` and `inert`.
    - The list enters with `@starting-style` (`opacity: 0`, and `translate: 0 -4px` inside `motion-safe`) and `motion-transition( $state: opacity, $move: translate )`.
    - `is-exiting` goes back to `-4px` with `$duration: md`. If suggestions come back while it closes, it turns around.
