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

## Success criteria

What "good enough to pursue" means, grouped by who it has to be true for. (`[open]` = not met yet.)

1. **Shoppers don't notice (experience & performance)** — no UX downgrade; same flows and markup;
   cart performance kept with no visible slowdown.
2. **Extenders & themes keep working (extensibility & parity)** — feature parity with the legacy
   cart; all PHP hooks and filters keep firing; templates not edited (no version bumps, theme
   overrides keep working); accessibility kept; legacy cart jQuery events keep firing for
   extensions/analytics (**[open]** — `updated_wc_div`, `updated_cart_totals`, `wc_cart_emptied`
   still to do); PHP notices still surface (**[open]** — `wc_add_notice` is bypassed by Store API
   mutations); no-JS not regressed.
3. **Fits the iAPI architecture (engineering)** — reuse the shared `woocommerce` store for shared
   cart-domain mutations; keep classic-cart-only behavior in a thin `woocommerce/classic-cart`
   store; stay in sync with other iAPI blocks; flag-gated and reversible; no new cart variant.
4. **We can prove it (verification)** — cart e2e passes with the flag on (**[partly]** — removal
   tests fail; see the audit); phpcs, phpstan, eslint clean; module builds.

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
| `apply_coupon` | `data-wp-on--click` → action | `applyCoupon` (shared store, via batch queue) | `POST cart/apply-coupon` |
| `remove_coupon_clicked` | `data-wp-on--click` on remove-coupon link | `removeCoupon` (shared store, via batch queue) | `POST cart/remove-coupon` |
| `item_remove_clicked` | `data-wp-on--click` on `.product-remove > a` | `removeCartItem` (shared store) | `POST cart/remove-item` |
| `item_restore_clicked` (undo) | `data-wp-on--click` (open question — no Store API restore) | `restoreItem` (local; falls back to the legacy server URL + `navigate()`) | — |
| `shipping_method_selected` | `data-wp-on--change` on shipping radios | `selectShippingRate` (local classic-cart store; direct Store API) | `POST cart/select-shipping-rate` |
| `shipping_calculator_submit` | `data-wp-on--submit` on calculator form | `updateCustomer` (local classic-cart store; direct Store API) | `POST cart/update-customer` |
| `toggle_shipping` (slide panel) | pure UI: `data-wp-on--click` toggles context + `data-wp-class` | — | — |
| `update_wc_div` / `update_cart_totals_div` (HTML swap) | replaced by `router.navigate()` morph | — | — |
| listens `added_to_cart`, `wc_update_cart` | `setupCartSyncBridge` listens on document for native `wc-blocks_added_to_cart`/`wc-blocks_removed_from_cart` (other iAPI blocks) **and** jQuery `added_to_cart`/`wc_update_cart` → `refreshCartItems` + `navigate()` | `refreshCartItems` (exists) | `GET cart` |
| emits `updated_wc_div`, `updated_cart_totals`, `applied_coupon`, `removed_coupon`, `updated_shipping_method`, `wc_cart_emptied`, `item_removed_from_classic_cart` | re-emitted from iAPI actions via the legacy-events bridge | — | — |

**Store split (decided during the spike).** Cart-domain mutations that the Cart/Mini-Cart blocks
*also* use live in the shared `woocommerce` store: `applyCoupon` + `removeCoupon` were added there
(alongside the existing `addCartItem` / `removeCartItem` / `refreshCartItems`), routed through its
batch queue. Mutations the blocks *don't* use stay **local** to the `woocommerce/classic-cart`
store — `selectShippingRate` + `updateCustomer` call Store API directly, and `restoreItem` falls back
to the legacy server URL (no Store API restore endpoint). Rule of thumb: shared = what the blocks
share; local = classic-cart-only glue (the directive handlers, the re-render, busy state, the event
bridge).

The classic-cart store accesses the shared store **with** the lock
(`store('woocommerce', {}, { lock: universalLock })`) because it reads the shared store's locked
state (`restUrl` / `nonce`) for the direct shipping calls; reading locked state without the lock
silently breaks the mutations. Its own state is likewise locked so it stays mutable from actions.

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
- **Re-emit outbound jQuery events** from the iAPI actions so analytics and themes that listen on
  them keep working. Done so far: `applied_coupon`, `removed_coupon`, `updated_shipping_method`,
  and `item_removed_from_classic_cart`. Still to do: `updated_wc_div`, `updated_cart_totals`, and
  `wc_cart_emptied` (these were tied to the legacy HTML-swap mechanism, so they need firing after
  the router re-render / on the empty-cart transition).
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

### Decisions to make before flag-on

**Notices.** The main one. Cart notices fall in a gap between two systems, and our re-render
conflicts with both:

- PHP session notices (`wc_add_notice` → `woocommerce_output_all_notices`) are what the classic cart
  and extensions use. Our `navigate()` re-render runs `woocommerce_output_all_notices`, so a notice
  already in the session does show. The problem: Store API mutations don't add the transactional
  ones ("coupon applied", "item removed / Undo"); the Store API `NoticeHandler` only forwards
  *error* notices. So there's nothing to render.
- iAPI store-notices (`woocommerce/store-notices`) render notices client-side from a `notices` array
  held in a store-notices region's context. To use them in the classic cart we'd render that region
  and `addNotice` into it. But `navigate()` morphs the cart region from fresh server HTML, so a
  notice added client-side is wiped on the next re-render: the notice is there, then the navigate
  replaces the region and it's gone.

So the server doesn't create the transactional notices, and client-added ones don't survive the
re-render. What it would take: produce the notices server-side so the re-render includes them (the
mutation adds the `wc_add_notice`, or keep the legacy server endpoints for the notice-bearing flows
like undo); or render the store-notices region **outside** the router re-render region so the morph
doesn't wipe it, and drive it from the store; or extend the Store API `NoticeHandler` to carry
success/info notices and surface them through that persistent region. Undo/restore depends on this
(the Undo link rides on the "item removed" notice).

**Slower interface, and the double round-trip.** A main blocker. Every change is two server trips:
the Store API mutation, then a full `navigate()` re-render of the PHP cart. That's what preserves
extensibility (the hooks/filters re-run), but it trades away the iAPI benefit (re-fetch + morph
instead of reactive updates) and makes the cart noticeably slower than the block cart. It's also
error-prone under that latency: repeat clicks before the slow re-render lands fire duplicate requests
against an already-mutated item ("Cart item no longer exists"). Mitigations applied: a synchronous
reentrancy guard per action (classic-cart store), a busy overlay over the cart while a mutation is
pending (a coarse `.is-cart-updating` overlay — we kept this rather than disabling each control in
place, since polishing the pending-state UX is out of spike scope), and making the shared store's
`removeCartItem` idempotent (split out as a separate trunk PR, #66051). The root cost remains. Open question:
is the double round-trip the right trade for the cart, or do we invest in avoiding the navigate
(Store-API-rendered fragment, or selective `data-wp-text`) and offering iAPI extensibility points so
extensions move off the PHP hooks over time? Avoiding the navigate is real work and changes the
extensibility story.

Note on the hook surface: dropping the PHP refetch means we'd have to account for far more hooks than
the Cart/Checkout blocks support today — re-supporting every existing cart hook at the
iAPI level is a large effort. Team Billow has started scoping a **curated list of extensibility
points** instead; that's a smaller, more tractable scope than blanket hook support, and it's the path
that would let us drop the refetch without trying to preserve the full hook surface. This direction
looks more promising than re-rendering server HTML on every change.

**Extension surface and risk (not fully tested).** The cart has a large hook/filter surface and this
approach keeps all of it. The impact on extensions was not fully tested: only basic interaction
(Points and Rewards) on an otherwise clean store. We still need extensive testing on a store with
many extensions, where the risk is higher (conflicting hooks, markup assumptions, notices).

**Where the classic-cart behavior lives.** Some functionality the classic cart needs is missing from
the shared `woocommerce` store (shipping rate, customer address, undo/restore); we kept it in a
separate `woocommerce/classic-cart` store. Decision: keep a separate store for the legacy surface,
or fold these into the shared store as core features? The block is the default cart, so the shared
store is the canonical one; adding classic-only actions there has a cost.

### Finer risks

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
