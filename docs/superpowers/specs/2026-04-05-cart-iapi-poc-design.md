# Cart Block Interactivity API POC: Proceed-to-Checkout

## Goal

Validate that a single Cart inner block can be converted from React to the Interactivity API while coexisting with the remaining React-based Cart block. The proceed-to-checkout block is the target because it exercises state reads, user interaction, async observer patterns, and mobile-specific UI (sticky positioning) — enough complexity to prove the pattern without being a full migration.

## Context

The Cart block currently renders entirely via React. Inner blocks are server-rendered as HTML with `data-block-name` attributes, then `renderInnerBlocks()` maps them to registered React components at hydration time. The mini-cart block has already migrated to IAPI behind the `experimental-iapi-mini-cart` feature flag using a dual-track approach (PHP conditionally renders either React or IAPI markup).

Existing IAPI infrastructure in WooCommerce:
- Shared `woocommerce` store (`base/stores/woocommerce/cart.ts`) with cart state, mutation batching, optimistic updates
- `BlocksSharedState.php` hydrates cart data into `wp_interactivity_state('woocommerce', ...)`
- `EventEmitter` (`assets/js/events/event-emitter.ts`) powers `window.wc.blocksCheckoutEvents` with priority-based listeners and abort-on-error semantics

## Approach

**Approach A (chosen):** Standalone IAPI block within the existing React Cart. A feature flag gates the rendering path. A thin React pass-through component preserves server-rendered IAPI HTML inside the React-owned DOM tree.

**Approach B (noted for later):** Full bridge layer syncing React Cart context (`cartIsLoading`, `isCalculating`, event dispatch) into IAPI state. Becomes relevant when converting multiple inner blocks that need richer Cart context. Not needed for this single-block POC since the shared `woocommerce` store already exposes cart state.

## Design

### 1. Rendering Strategy

When the `experimental-iapi-cart` feature flag is **enabled**:

1. **PHP** (`ProceedToCheckoutBlock.php`): Renders the button as server-side HTML with `data-wp-interactive="woocommerce/proceed-to-checkout"` directives. Sets context via `wp_interactivity_data_wp_context()` with `checkoutUrl` and `buttonLabel`. Includes a sentinel `<div>` for the sticky intersection observer. Enqueues script module via `wp_enqueue_script_module()`. The `iapi-frontend.ts` file is built as a script module by the existing `webpack-config-interactive-blocks.js` config (same build pipeline as mini-cart and add-to-cart-with-options IAPI blocks).

2. **React pass-through component**: Registered via `registerCheckoutBlock()` so `renderInnerBlocks()` finds the block. Renders a single `<div ref={...}>` that preserves the server-rendered IAPI HTML. React sees one opaque container and won't re-render into it. This component also bridges the `CartEventsProvider` context — it calls `useCartEventsContext()` and is available because the parent Cart block's React tree still wraps everything.

3. **IAPI runtime**: Binds to the `data-wp-interactive` directives in the preserved HTML. The `woocommerce/proceed-to-checkout` store handles all interactivity.

When the feature flag is **disabled**: Current React behavior, zero changes.

### 2. Cart Event Emitter

A new `window.wc.blocksCartEvents` emitter, built on the existing `EventEmitter` infrastructure:

- **`onProceedToCheckout(callback, priority)`** — registers an observer. Returns unsubscribe function. Supports async callbacks. Abort-on-error semantics (stops processing if any observer returns error/fail response).
- Same contract as the current React hook `useCartEventsContext().onProceedToCheckout`.

**Backwards compatibility:** The existing React `CartEventsProvider` delegates to this shared emitter internally. `useCartEventsContext().onProceedToCheckout` registers on the shared emitter. `dispatchOnProceedToCheckout()` calls `emitWithAbort()` on the shared emitter. Extensions using the React hook see zero change.

**IAPI access:** The IAPI store imports the cart event emitter directly (no React bridge needed for dispatching events). The pass-through React component is not involved in event dispatch.

### 3. IAPI Store

Store namespace: `woocommerce/proceed-to-checkout` (locked, private).

**State (from PHP context):**
- `checkoutUrl` — resolved checkout page permalink
- `buttonLabel` — button text

**State (local):**
- `isLoading` — toggled on click, reset on pageshow

**State (derived):**
- `isDisabled` — the shared `woocommerce` IAPI store does not currently expose `cartIsLoading` or `isCalculating` flags (those live in React `@wordpress/data` stores). For the POC, we add an `isProcessing` boolean to the shared `woocommerce` store that is set `true` while the mutation batcher has pending requests. This gives the IAPI proceed-to-checkout block a signal to disable the button during cart operations.

**Actions:**
- `*handleClick` — generator function. Calls `emitWithAbort()` on the cart event emitter. If any observer returns error/fail, aborts. Otherwise sets `isLoading = true` and navigates. The button renders as an `<a>` tag with `href` (preserving anchor semantics for middle-click/cmd-click). The click handler uses `data-wp-on--click` which calls `event.preventDefault()` first, runs observers, then navigates via `window.location.href` on success — same pattern as the current React implementation.

**Callbacks:**
- `onPageShow` — listens for browser `pageshow` event to reset `isLoading` (Safari back-button fix)
- `initStickyObserver` — sets up `IntersectionObserver` on a sentinel element to toggle sticky class on mobile viewports. Also computes `document.body` background color and applies it as inline style on the sticky container (matching current React behavior).

### 4. Feature Flag

Name: `experimental-iapi-cart`

Registered in the same system as `experimental-iapi-mini-cart`. Controls:
- PHP render path in `ProceedToCheckoutBlock.php`
- Whether `interactivity` support is enabled on the block type
- Which frontend script is loaded (script module vs traditional)

## File Changes

### New Files

| File | Purpose |
|------|---------|
| `assets/js/events/cart-events.ts` | Cart event emitter instance (mirrors `checkout-events.ts`) |
| `assets/js/blocks/cart/inner-blocks/proceed-to-checkout-block/iapi-frontend.ts` | IAPI store definition |

### Modified Files

| File | Change |
|------|--------|
| `assets/js/events/index.ts` | Export cart events |
| `bin/webpack-helpers.js` | Add `@woocommerce/blocks-cart-events` external mapping |
| `bin/webpack-entries.js` | Add `blocksCartEvents` entry point |
| `assets/js/base/context/providers/cart-checkout/cart-events/index.tsx` | Delegate to shared cart event emitter |
| `assets/js/blocks/cart/inner-blocks/proceed-to-checkout-block/frontend.tsx` | Swap to pass-through component when IAPI flag enabled |
| `assets/js/blocks/cart/inner-blocks/register-components.ts` | Register pass-through variant |
| `src/Blocks/BlockTypes/ProceedToCheckoutBlock.php` | Add IAPI render path behind feature flag |
| Feature flag registration (same location as `experimental-iapi-mini-cart`: `includes/react-admin/feature-config.php` and `client/admin/config/core.json`) | Register `experimental-iapi-cart` flag |
| `base/stores/woocommerce/cart.ts` | Add `isProcessing` state derived from mutation batcher |

### Unchanged Files

- `block.json`, `edit.tsx`, `attributes.tsx`, `constants.tsx` — editor side unchanged
- Cart block parent (`block.js`, `frontend.js`) — no changes
- `block.json` for proceed-to-checkout — no changes to block metadata

## Success Criteria

With `experimental-iapi-cart` enabled:

1. Proceed-to-checkout button renders server-side and is interactive via IAPI
2. Sticky behavior works on mobile (< 782px viewport)
3. Extensions registering `onProceedToCheckout` via the React hook still fire and can block navigation
4. Loading spinner shows during navigation
5. Button disables when cart is loading
6. No visible difference to the shopper compared to flag-off behavior

## Follow-Up Work

Items explicitly deferred from this POC:

- **`applyCheckoutFilter` support**: The React-side checkout filter registry (`proceedToCheckoutButtonLabel`, `proceedToCheckoutButtonLink`) is not available in IAPI. Needs either a PHP-side filter or new IAPI-compatible filter mechanism. Will address after POC validates.
- **Approach B bridge layer**: Syncing richer React Cart context (e.g., `isCalculating` from the checkout store) into IAPI state. Becomes relevant when converting inner blocks that depend on checkout-specific state beyond what the shared `woocommerce` store provides.
- **Full Cart migration**: Converting all 17 remaining inner blocks and the parent Cart block to IAPI, eliminating React entirely (following the mini-cart precedent).
- **Cart events emitter as canonical API**: After full migration, the React hook becomes a permanent thin wrapper over the emitter. Non-React extensions get first-class access.
