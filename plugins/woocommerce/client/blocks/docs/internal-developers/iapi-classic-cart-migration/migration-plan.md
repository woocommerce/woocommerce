# Classic cart shortcode → Interactivity API migration (spike plan)

## Goal

Migrate the classic cart shortcode (`[woocommerce_cart]`) to the Interactivity API (iAPI)
using the **interactivity-only** approach:

- The cart stays **PHP-templated and hook-driven**. `cart.php` / `cart-totals.php` remain the
  renderer, so every `do_action` / `apply_filters` keeps firing.
- We replace only the **interaction + refresh layer** (today: jQuery + Ajax HTML swap in
  `cart.js`) with iAPI.
- A **feature flag** (`experimental-iapi-cart`) lets the legacy and iAPI implementations live
  side by side, exactly like `experimental-iapi-mini-cart`.

This mirrors the Mini-Cart precedent — **with one deliberate difference**:

> The Mini-Cart blockified its rendering (items/totals are rendered client-side by iAPI from
> store JSON). We do **not** do that. We reuse the Mini-Cart store's **mutation actions and
> event bridge**, but keep PHP as the renderer and re-render server HTML after each change.

## Architecture

Three moving parts:

1. **Mutations → the shared `woocommerce` store**
   (`client/blocks/assets/js/base/stores/woocommerce/cart.ts`, namespace `woocommerce`).
   It already exposes `addCartItem`, `removeCartItem`, `batchAddCartItems`, `refreshCartItems`
   backed by Store API (`/wc/store/v1/cart/*`) with optimistic updates and mutation batching.
   This gives instant Mini-Cart sync and a single source of truth.

2. **Re-render → the Interactivity Router**
   (`@wordpress/interactivity-router`). Wrap the cart in a `data-wp-router-region`; after a
   mutation settles, call `navigate(currentUrl)`. The server re-renders the templates (hooks +
   filters fire, notices included) and the router morphs the region in place. This is how we
   keep "PHP-templated and hook-driven".

3. **Interaction → iAPI directives** injected onto the server-rendered markup. iAPI has no event
   delegation, so each interactive element needs its own directive (see "Directive injection").

Flow for any change:

```text
user acts
  → optimistic store update (Mini-Cart badge moves instantly)
  → Store API mutation
  → on settle: router.navigate(currentUrl)
  → fresh server HTML morphs in
  → legacy jQuery events re-emitted for back-compat
```

Why re-render instead of reactive client bindings: per-item subtotal, the whole totals table,
coupon / fee / tax rows and every injected hook block are **filter-generated formatted HTML**.
Rebinding them to store state client-side means re-implementing those filters in JS — the
extensibility loss we are explicitly avoiding. Re-rendering keeps them server-authoritative.

## Where iAPI takes over (cart.js → iAPI map)

Source: `client/legacy/js/frontend/cart.js`.

| Today (cart.js) | iAPI replacement | Store action | Store API endpoint |
| --- | --- | --- | --- |
| `quantity_update` / `update_cart` | `data-wp-on--change` on qty input → update item, then `navigate()` | `addCartItem` (update path, exists) | `POST cart/update-item` |
| `input_changed` (enable "Update cart") | qty applies on change; "Update cart" becomes no-JS fallback | — | — |
| `apply_coupon` | `data-wp-on--submit` → action | `applyCoupon` (NEW) | `POST cart/apply-coupon` |
| `remove_coupon_clicked` | `data-wp-on--click` on remove-coupon link | `removeCoupon` (NEW) | `POST cart/remove-coupon` |
| `item_remove_clicked` | `data-wp-on--click` on `.product-remove > a` | `removeCartItem` (exists) | `POST cart/remove-item` |
| `item_restore_clicked` (undo) | `data-wp-on--click` (open question — no Store API restore) | `restoreCartItem` (NEW, TBD) | re-add via `add-item`, or keep server URL + `navigate()` |
| `shipping_method_selected` | `data-wp-on--change` on shipping radios | `selectShippingRate` (NEW) | `POST cart/select-shipping-rate` |
| `shipping_calculator_submit` | `data-wp-on--submit` on calculator form | `updateCustomer` (NEW) | `POST cart/update-customer` |
| `toggle_shipping` (slide panel) | pure UI: `data-wp-on--click` toggles context + `data-wp-class` | — | — |
| `update_wc_div` / `update_cart_totals_div` (HTML swap) | replaced by `router.navigate()` morph | — | — |
| listens `added_to_cart`, `wc_update_cart` | `setupCartSyncBridge` listens on document for native `wc-blocks_added_to_cart`/`wc-blocks_removed_from_cart` (other iAPI blocks) **and** jQuery `added_to_cart`/`wc_update_cart` → `refreshCartItems` + `navigate()` | `refreshCartItems` (exists) | `GET cart` |
| emits `updated_wc_div`, `updated_cart_totals`, `applied_coupon`, `removed_coupon`, `updated_shipping_method`, `wc_cart_emptied`, `item_removed_from_classic_cart` | re-emitted from iAPI actions via the legacy-events bridge | — | — |

Store work = add `applyCoupon`, `removeCoupon`, `selectShippingRate`, `updateCustomer`,
`restoreCartItem` to the shared store. Items / refresh already exist.

## Legacy mechanisms removed / retained

When the flag is on, the classic cart's own JS machinery is no longer needed. Three buckets.

### A. Fully replaced — not loaded when the flag is on

- **`cart.js` in its entirety** (`client/legacy/js/frontend/cart.js`):
    - the HTML-swap re-render (`update_wc_div` / `update_cart_totals_div`),
    - blockUI overlays (`$.block` / `$.unblock`, the `.processing` class),
    - client-side notice handling (`show_notice`, `remove_duplicate_notices`,
      `show_coupon_error`, `$.scroll_to_notices`),
    - the `clicked`-attribute submit detection and the ENTER-key qty handling.
- **`wc_cart_params`** (ajax_url + the apply/remove-coupon and update-shipping-method nonces) —
  iAPI uses the store's `restUrl` / nonce from hydration.
- The cart's **jQuery + blockUI** dependency for its own operation.
- The **`get_cart_totals` totals-only refresh** path.

### B. Replaced for the JS path, kept as the no-JS fallback

iAPI intercepts these with `event.preventDefault()` when JS is on; they still work without JS.

- The `<form class="woocommerce-cart-form">` POST and
  `WC_Form_Handler::update_cart_action()`.
- The `woocommerce-cart-nonce` field and the `cart[{key}][qty]` serialization.
- The shipping-calculator `calc_shipping` POST → `WC_Shortcode_Cart::calculate_shipping()`.
- The "Update cart" button (demoted to no-JS only).

### C. Unused by our path, but kept registered (back-compat)

Third parties call these directly, so we cannot remove them:

- The HTML-returning `wc-ajax` endpoints `apply_coupon`, `remove_coupon`,
  `update_shipping_method`, `get_cart_totals` (`includes/class-wc-ajax.php`).
- Their AJAX nonces.

### D. Cart fragments (special case)

`cart-fragments.js` + the `get_refreshed_fragments` AJAX + the
`woocommerce_add_to_cart_fragments` filter + the sessionStorage cache keyed by the
`woocommerce_cart_hash` cookie exist to live-update the **classic Mini-Cart widget** across the
whole site.

- Our cart-page work does **not** use them — the page re-renders via the router and state syncs
  through the store + Store API + events.
- They become fully redundant once the **iAPI Mini-Cart** is also enabled.
- Keep them enqueued while the classic / React Mini-Cart can still coexist on the page — that
  widget has no other update path.

See `legacy-mechanism-audit.md` for the per-mechanism coverage proof.

## Feature flag

Add `experimental-iapi-cart` next to `experimental-iapi-mini-cart` in
`client/admin/config/core.json` and `client/admin/config/development.json`, then it surfaces in
the generated feature config and is read with the same `Features::is_enabled()` gate the
Mini-Cart uses.

Force-off filter for parity:

```php
add_filter(
    'woocommerce_admin_get_feature_config',
    function ( $config ) {
        $config['experimental-iapi-cart'] = false;
        return $config;
    }
);
```

Gate points (all default to current `cart.js` behaviour when off):

- `WC_Shortcode_Cart::output()` — when on: inject directives, enqueue the iAPI module, seed
  store state.
- Script enqueue — when on: dequeue `wc-cart` (cart.js); keep `wc-cart-fragments`; enqueue
  `@wordpress/interactivity`, `@wordpress/interactivity-router`, and the new `woocommerce/cart`
  script module (registered through `AssetsController::register_script_modules()`).
- State hydration — when on: `BlocksSharedState::load_cart_state()` seeds the `woocommerce`
  store server-side.
- `WC_Form_Handler::update_cart_action()` — left intact as the no-JS POST fallback.

## Directive injection (templates stay untouched)

iAPI has no event delegation, so directives sit on each element. Recommended order:

1. **Output-buffer + `WP_HTML_Tag_Processor`** in `output()`: render the template normally, then
   post-process the HTML to add `data-wp-interactive="woocommerce"` + `data-wp-router-region` on
   the wrapper and bind directives on known selectors (`input.qty`, `.product-remove > a`,
   `button[name=apply_coupon]`, `a.woocommerce-remove-coupon`,
   `:input[name^=shipping_method]`, `form.woocommerce-shipping-calculator`). Survives theme
   template overrides as long as standard classes exist; touches zero template files.
2. **Complement with existing filters** where cleaner: `woocommerce_cart_item_quantity` and
   `woocommerce_cart_item_remove_link` already let us decorate per-row elements without parsing.

Avoid editing template files directly (version bumps + theme-override drift). The long-term fix
is rendering the cart through a block `render_callback`.

## Backward-compat + cross-block sync bridge

- **Inbound cart-sync (DONE, verified)** — `setupCartSyncBridge` (a `data-wp-init` callback on the
  wrapper) listens on `document` for **both**:
    - the native iAPI events `wc-blocks_added_to_cart` / `wc-blocks_removed_from_cart` — what other
      iAPI blocks emit via the shared store (e.g. Wishlist "move to cart", Mini-Cart, add-to-cart);
      this is what makes a mutation in another block refresh the classic cart;
    - the legacy jQuery `added_to_cart` / `wc_update_cart` events — classic add-to-cart + extensions.

  On either, it runs `refreshCartItems()` + router `navigate()`. It's guarded by
  `state.isProcessing` so the cart's own mutations (which also fire the native events via the shared
  store) don't double-navigate, and it returns a cleanup so listeners aren't duplicated across
  re-renders. Lesson: iAPI↔iAPI cart sync flows through the native `wc-blocks_*` events, not jQuery.
- **Re-emit outbound jQuery events** (`updated_wc_div`, `updated_cart_totals`, `applied_coupon`,
  `removed_coupon`, `updated_shipping_method`, `wc_cart_emptied`) from the iAPI actions so
  analytics, themes and `cart-fragments.js` keep working.
- **Fragments** — the store already calls `triggerAddedToCartEvent`; keep `cart-fragments.js`
  enqueued so the Mini-Cart hash/cookie refresh path is preserved.

## Phased plan

1. **Scaffold flag + module** — add `experimental-iapi-cart`, register an empty `woocommerce/cart`
   script module, gate enqueue/dequeue. Flag-off = unchanged; flag-on = cart.js gone + module
   loads.
2. **Wrapper + router** — inject `data-wp-interactive` + `data-wp-router-region`, seed store
   state, get `navigate()` re-rendering the region. Prove hooks/notices survive a re-render.
3. **Read-only sync** — wire the document `added_to_cart` listener → `refreshCartItems` +
   `navigate()`. Mini-Cart and cart page stay in sync.
4. **Mutations** — quantity, remove item (existing actions), then coupons, shipping method,
   shipping calculator. Restore/undo last.
5. **BC bridge** — re-emit all legacy events; verify cart-fragments + a sample extension react.
6. **Edge cases** — empty-cart transition, coupon error display, no-JS fallback, a11y
   (focus / `role=alert` after morph), checkout-on-same-page.
7. **Verdict writeup** — pros/cons/blockers + the BC-layer cost, feeding the team's "decide final
   approach" step.

## Open questions / risks

- **Undo/restore** — no Store API "restore last removed" endpoint; decide re-add vs keep-server-URL.
- **Double round-trip** — mutation (Store API) then `navigate()` (page GET). Acceptable for the
  spike; optimize later via a Store-API-rendered cart fragment or selective `data-wp-text`.
- **Directive injection brittleness** under heavy theme/template customization → long-term move
  to a block `render_callback`.
- **Two carts mutating state** — ensure cart.js is fully dequeued when the flag is on.
- **a11y after morph** — re-apply focus + `role="alert"` that cart.js handles today.
- **Quantity UX change** — applying qty on change (no "Update cart" click) is a behaviour change;
  confirm acceptable.

## iAPI primitives used

- `data-wp-interactive="woocommerce"` + `wp_interactivity_state()` — bind region to the store.
- `data-wp-router-region` + `@wordpress/interactivity-router` `navigate(url)` — hook-preserving
  re-render.
- `data-wp-on--change|click|submit`, `data-wp-on-document--<event>` — interactions.
- `data-wp-context`, `getContext()`, `getElement()` — per-row keys (cart item key, coupon code).
- `data-wp-bind--*`, `data-wp-class--*`, `data-wp-text` — small UI state.
- `data-wp-init` / `data-wp-watch` — set up the jQuery bridge and side effects.
- Async actions are generators (`function*`), `yield` the Store API call.
