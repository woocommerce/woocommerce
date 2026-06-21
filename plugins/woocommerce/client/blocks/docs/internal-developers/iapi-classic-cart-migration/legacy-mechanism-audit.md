# Legacy mechanism audit — no functionality lost

This audit walks every behaviour in the mechanisms we stop loading when
`experimental-iapi-cart` is on, and records where that behaviour is handled in the iAPI design.
It is the safety net for "we removed X — is X still covered?".

Status legend:

- **Covered** — a direct equivalent exists in the iAPI design.
- **Covered (note)** — covered, but with a behavioural difference or extra work to confirm.
- **Gap** — must be resolved before the flag can ship on.

## 1. `cart.js` (`client/legacy/js/frontend/cart.js`)

### Infrastructure helpers

| Behaviour | What it did | Handled by | Status |
| --- | --- | --- | --- |
| `get_url(endpoint)` | Built `wc-ajax` URLs from `wc_cart_params.wc_ajax_url` | Store API `restUrl` + nonce from `BlocksSharedState::load_cart_state()` hydration | Covered |
| `is_blocked` / `block` / `unblock` | jQuery blockUI overlay + `.processing` class during requests | iAPI pending state bound with `data-wp-class--is-loading` driven by the store's in-flight/optimistic state; CSS replaces blockUI | Covered (note) — confirm visual parity of the busy state |
| `remove_duplicate_notices` | De-duped notice nodes before showing | Notices come from the server re-render (`woocommerce_output_all_notices`), which already de-dupes; plus `store-notices` | Covered |
| `show_notice` / `show_coupon_error` | Injected notice / coupon-error HTML client-side | Server re-render emits notices after `navigate()`; coupon errors render in the coupon area server-side | Covered (note) — verify coupon error placement + `role="alert"` after morph |

### Re-render (HTML swap)

| Behaviour | What it did | Handled by | Status |
| --- | --- | --- | --- |
| `update_wc_div` — replace form + totals | Parsed response, swapped `.woocommerce-cart-form` and `.cart_totals` | `router.navigate(currentUrl)` morphs the `data-wp-router-region` | Covered |
| `update_wc_div` — empty cart transition | Replaced `.woocommerce` with the empty-cart markup, fired `wc_cart_emptied` | `navigate()` renders `cart-empty.php` (shortcode branch); `wc_cart_emptied` re-emitted | Covered (note) — confirm region wraps both filled + empty states |
| `update_wc_div` — checkout on same page | Reloaded / fired `update_checkout` | After morph, re-emit `update_checkout`; reload fallback if a `.woocommerce-checkout` is present | Covered (note) — exercise cart+checkout-on-one-page |
| `update_wc_div` — preserve coupon error/value | Restored `#coupon_code` value + error after swap | Server re-render returns the entered value + error; verify it round-trips | Covered (note) |
| `update_cart_totals_div` | Swapped `.cart_totals`, fired `updated_cart_totals` | `navigate()` morph; `updated_cart_totals` re-emitted | Covered |
| `$.scroll_to_notices` | Scrolled to `[role="alert"]` after a change | a11y step: after morph, move focus / scroll to the notice region | Covered (note) — implement in the morph callback |

### Cart interactions

| Behaviour | What it did | Handled by | Status |
| --- | --- | --- | --- |
| `init` delegated bindings | `$(document).on(...)` for all controls | Per-element iAPI directives injected into the markup (no delegation in iAPI) | Covered (note) — every interactive element must receive a directive |
| `input_changed` | Enabled the "Update cart" button | Qty applies on change → button is no-JS-only | Covered (note) — behaviour change, confirm acceptable |
| `input_keypress` (ENTER) | ENTER in qty submitted update | `data-wp-on--keydown` on the qty input (or form submit handler) | Covered |
| `submit_click` / `cart_submit` | Tracked which submit was clicked, routed update vs coupon | Separate directives per control; routing no longer needed | Covered |
| `update_cart` / `quantity_update` | Serialized form, POSTed, swapped HTML | `addCartItem` update path per changed row → `navigate()` | Covered |
| `update_cart_totals` (`get_cart_totals`) | Totals-only refresh | `navigate()` morph | Covered |
| `apply_coupon` | AJAX apply, notice, `applied_coupon`, then full refresh | `applyCoupon` (NEW) → `navigate()`; `applied_coupon` re-emitted | Covered (note) — new store action |
| `remove_coupon_clicked` | AJAX remove, notice, `removed_coupon`, clear field, refresh | `removeCoupon` (NEW) → `navigate()`; `removed_coupon` re-emitted | Covered (note) — new store action |
| `remove_coupon_error` | Cleared coupon error styling on input | Server re-render clears it; optional iAPI clear on input | Covered |
| `item_remove_clicked` | AJAX GET remove URL, refresh, `item_removed_from_classic_cart` | `removeCartItem` (exists) → `navigate()`; event re-emitted | Covered |
| `item_restore_clicked` (undo) | AJAX GET restore URL, refresh | No Store API restore endpoint — `restoreCartItem` re-add, or call the server URL then `navigate()` | **Gap** — decide approach |
| `on_keydown_remove_coupon` / `on_keydown_remove_item` | Space key activates `role="button"` links | `data-wp-on--keydown` directives, or convert to real `<button>` | Covered (note) — preserve keyboard a11y |

### Shipping

| Behaviour | What it did | Handled by | Status |
| --- | --- | --- | --- |
| `toggle_shipping` | Slide the calculator panel, set `aria-expanded` | iAPI UI: context bool + `data-wp-class` / `data-wp-bind--aria-expanded` | Covered |
| `country_to_state_changed` | Triggered select2 re-init | Re-emit the event after morph for select2-based fields | Covered (note) — verify with a select2 country field |
| `shipping_method_selected` | AJAX `update_shipping_method`, totals refresh, focus restore, `updated_shipping_method` | `selectShippingRate` (NEW) → `navigate()`; restore focus; event re-emitted | Covered (note) — new store action |
| `shipping_calculator_submit` | `calc_shipping` POST, full refresh | `updateCustomer` (NEW) → `navigate()` | Covered (note) — new store action |

## 2. `wc_cart_params` + AJAX nonces

| Behaviour | What it did | Handled by | Status |
| --- | --- | --- | --- |
| `wc_cart_params` config object | ajax_url, wc_ajax_url, coupon/shipping nonces for cart.js | Not enqueued when flag on; iAPI uses store `restUrl` + nonce | Covered (note) — extensions reading `wc_cart_params` on the cart page lose it; document |

## 3. Endpoints kept registered (back-compat — no loss)

These are unused by the iAPI path but stay registered, so dependent code keeps working.

| Mechanism | Status |
| --- | --- |
| `wc-ajax` `apply_coupon` / `remove_coupon` / `update_shipping_method` / `get_cart_totals` (`includes/class-wc-ajax.php`) | Retained — no loss |
| Their AJAX nonces | Retained — no loss |
| `<form>` POST → `WC_Form_Handler::update_cart_action()` | Retained as no-JS fallback — no loss |
| `woocommerce-cart-nonce`, `cart[{key}][qty]` serialization | Retained for no-JS — no loss |
| `calc_shipping` POST → `WC_Shortcode_Cart::calculate_shipping()` | Retained for no-JS — no loss |

## 4. Cart fragments

| Behaviour | What it did | Handled by | Status |
| --- | --- | --- | --- |
| `cart-fragments.js` + `get_refreshed_fragments` + `woocommerce_add_to_cart_fragments` + `woocommerce_cart_hash` cookie | Live-updated the classic Mini-Cart widget site-wide | Not used by the cart page (router re-render + store). Kept enqueued while the classic Mini-Cart coexists; retired once the iAPI Mini-Cart is on | Covered (note) — retained transitionally |

## Open gaps to resolve before flag-on

Status as of the 2026-06-21 spike pass (verified live against the Studio site):

1. **Busy-state visual parity** — DONE. Reactive `state.isProcessing` on the locked
   classic-cart store toggles `aria-busy` + an `is-cart-updating` class (inline CSS), replacing
   the blockUI overlay.
2. **`wc_cart_params` consumers** — DONE (documented in `dequeue_legacy_cart()` and the plan).
3. **No-JS form-submit prevention** — DONE. `preventFormSubmit` on the cart form (verified
   `defaultPrevented`); the native POST still works without JS.
4. **a11y after morph** — PARTIAL. `focusNotices()` moves focus to a server notice after each
   re-render, but see the key finding below.

### Key finding: Store API mutations bypass the legacy WC notice/undo system

Routing mutations through Store API (`removeCartItem`, `apply-coupon`, …) does **not** emit the
classic `wc_add_notice` messages that the legacy form/GET handlers produce. Consequences observed
live:

- **Undo / restore** — removing an item via Store API produces **no "item removed / Undo"
  notice**, so the `restore-item` link is never rendered. The `restoreItem` action + directive are
  in place but unreachable on this path. To keep undo: either replicate the notice + restore link
  server-side after a Store API removal, or keep using the legacy GET remove URL for removals.
- **Coupon / status messages** — "Coupon applied successfully", "Shipping costs updated", etc. do
  not appear (error messages from the Store API response would still need surfacing). `focusNotices`
  therefore usually has nothing to focus.

This is the single biggest open question for the interactivity-only path: decide how cart notices
are produced once mutations are JSON/Store-API driven rather than PHP-handler driven.

### Implementation note (store locking)

The classic-cart store must be registered **with a lock** (`{ lock: universalLock }`) for its own
`state` to be mutable from actions (mirrors the Mini-Cart). The shared `woocommerce` store is
accessed **without** a lock — passing one there breaks access to its public actions.

## Manual verification checklist (spike acceptance)

Run each with the flag **on**, then repeat with it **off** to confirm identical outcomes.

- [ ] Change a quantity → totals + Mini-Cart update; no full reload.
- [ ] Remove an item → row goes, totals update, undo notice appears.
- [ ] Undo a removed item → item restored.
- [ ] Apply a valid coupon → discount row + success notice.
- [ ] Apply an invalid coupon → coupon error shown under the field, announced.
- [ ] Remove a coupon → discount row gone.
- [ ] Select a shipping method → totals update.
- [ ] Submit the shipping calculator → totals update.
- [ ] Empty the cart (remove last item) → empty-cart view renders.
- [ ] Add to cart from another block on the page → cart + Mini-Cart refresh.
- [ ] A theme/extension listening on `updated_wc_div` / `updated_cart_totals` still reacts.
- [ ] `cart-fragments.js` still refreshes the classic Mini-Cart.
- [ ] No-JS (disable JS): form POST update, coupon, shipping calc all still work.
- [ ] Keyboard: remove item/coupon via Space/Enter; focus lands sensibly after each change.
- [ ] Cart + Checkout on the same page behaves correctly.
