# WooCommerce Interactivity API stores

This folder contains the Interactivity API (iAPI) stores that WooCommerce blocks use on the frontend. All stores here are **locked** (`lock: true`) and private by design — they are not intended for third-party extension, and removing or changing their state is **not** a breaking change. See the "Interactivity API Stores" section in `client/blocks/CLAUDE.md` and the [WordPress Private Stores reference](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference#private-stores).

Stores in this folder:

-   [`woocommerce`](#woocommerce-store) — the unified store for product/variation data, the read-only cart mirror, the shopper's editable draft cart items, and the one in-context envelope (`state.itemInContext`, with `state.findItem` as its explicit-addressing twin) that ties them together for the item the surrounding markup implies.
-   `woocommerce/shopper-lists` — wishlist and saved-for-later state for the shopper-lists blocks (unchanged; no dedicated section here).

---

## `woocommerce` store

A single, locked iAPI store that exposes WooCommerce product and variation data (Store API format), the read-only cart mirror, and the shopper's in-progress draft cart items, all under one namespace. Interactive blocks read it whenever the surrounding markup implies a single "current" item — a single product page, a product in a Product Collection loop, a grouped-product child, a variation inside a variable product — and whenever they need to record purchase input (a quantity, a variation selection, a namespaced extension field) or add, update, or remove cart lines. Because reads and writes resolve from context, the same block code works whether it renders as a single product's main form, a Product Collection card, a mini-cart row, or a page-wide sticky bar.

### Architecture at a glance

```text
PHP                                                   Client

ProductsStore::load_product() / load_variations() /   store< WooCommerce >( 'woocommerce', …,
  load_purchasable_child_products()                     { lock: universalLock } )
        │ populates
        ▼                                              state.products    { items, variations,
  wp_interactivity_state( 'woocommerce',                                    productId, variationId }
    [ 'products' => … ] )                              state.cart        { items, totals, … }
                                                        state.draftItems  { [draftKey]: DraftItem[] }
BlocksSharedState::load_cart_state()
        │ populates                                    state.itemInContext ─┐
        ▼                                              state.findItem(ref)  ├─▶ Envelope
  wp_interactivity_state( 'woocommerce',                                    │  { productId, variationId,
    [ 'cart' => … ] )                                                       │    draftKey, product, variation,
                                                                             │    baseProduct, draftItem, cartItem }

Addressing, one bag per element:
  data-wp-context='woocommerce::{ "productId": 123, "variationId": null, "draftKey": "collection/q1/123" }'
```

Three planes, one namespace:

1.  **Read-only server data** — `state.products` (the product/variation cache) and `state.cart` (the Store API `/cart` mirror, with optimistic edits applied). Both are populated from PHP; nothing client-side writes to either.
2.  **Client-editable input** — `state.draftItems`, the *only* editable home in the store, at the root. It is never nested inside `state.cart`: the cart mirror is wholesale-replaced on every commit, rollback, and refresh (`state.cart = serverState` / `= snapshot` / `= json` in `cart.ts`), so anything nested inside it would be clobbered on that hot path.
3.  **Entry points** — `state.itemInContext` (a getter) and `state.findItem` (a function), both returning the same lazy `Envelope`. See [The envelope](#the-envelope).

### Two script modules, one namespace

The store registers from two script modules under the same `'woocommerce'` namespace; the runtime deep-merges their partial registrations into one store, so a consumer never needs to know which module registered which member.

-   **`@woocommerce/stores/woocommerce`** (`index.ts`) — the slim root: the `state.products`/`state.draftItems` defaults, the `itemInContext` getter, `findItem`, and the shared types. It bundles this folder's internal helper modules (`cart-pairing.ts`, `product-resolution.ts`, `draft-internals.ts`) rather than importing the cart machinery module, so it never drags cart fetch/queue machinery onto a page that only displays a product.
-   **`@woocommerce/stores/woocommerce/cart`** (`cart.ts`) — the cart machinery: the read-only cart mirror's lifecycle, the cart actions (`addItem`, `updateItem`, `removeItem`, `refresh`, `addCartItem`), the mutation batcher, and legacy events.

Neither module value-imports the other — types flow type-only. A **display-only page** (a Product Collection card, a product gallery, a Product Button rendered outside a form) only ever needs `@woocommerce/stores/woocommerce`. On such a page `state.cart` is simply absent — the cart module never registered it — so every read that touches the cart mirror is guarded rather than assumed present (`state.cart?.items ?? []`, in `index.ts`); `itemInContext.cartItem` and `findItem(...).cartItem` degrade to `undefined`, while `draftItem` still works (the draft view and the write routine live in the root's bundled helpers).

A component that needs both loads the cart module **lazily**, only at the point it actually needs cart actions or the live mirror. The Product Button is the shipped example: it statically imports `@woocommerce/stores/woocommerce` for its envelope reads, and only `import( '@woocommerce/stores/woocommerce/cart' )`s inside its `addItem`/`refresh` actions — i.e. on click, not on render (`atomic/blocks/product-elements/button/frontend.ts`).

### State reference

One table for the store's five root members:

| Property | Type | Origin | Notes |
| --- | --- | --- | --- |
| `products.items` | `Record<number, ProductResponseItem>` | Populated from PHP (`ProductsStore`) | Parent and grouped-child products, keyed by product id. |
| `products.variations` | `Record<number, ProductResponseItem>` | Populated from PHP | Keyed by variation id. |
| `products.productId` / `products.variationId` | `number` / `number \| null` | Populated / addressing fallback | The page's own product/variation id — the internal global-addressing fallback consulted only when the `woocommerce::` context bag declares neither key. This is the only place these two names still exist, and they are nested here, never root-level bare names. |
| `cart` | Store API `/cart` response (+ optimistic edits) | Populated from PHP (`BlocksSharedState::load_cart_state()`), reconciled by the cart machinery module | The read-only mirror. Registered by `cart.ts`, not the root module — absent on a page that never loads it (see [Two script modules, one namespace](#two-script-modules-one-namespace)). |
| `draftItems` | `Record<DraftKey, DraftItem[]>` | Client-only | The one editable home, at the root. Never server-seeded; reload-reset. See [Writing: drafts](#writing-drafts). |
| `itemInContext` | `Envelope` (getter) | Derived | The one entry point: the envelope for the item the surrounding markup implies. Every member is a lazy accessor. See [The envelope](#the-envelope). |
| `findItem` | `(ref?: FindItemRef) => Envelope` | Function | `itemInContext`'s explicit-addressing twin — the same envelope shape, for an item that isn't necessarily the context item. |

`nonce` and `restUrl`, both former root members, are no longer store state:

-   `nonce` is a module-local variable in `cart.ts`, refreshed from each response's `Nonce` header and gated behind the existing `isNonceReady` promise — nothing outside that module ever read or wrote it as state.
-   `restUrl` moved to `getConfig( 'woocommerce' )` (alongside `currency`, `locale`, and `nonOptimisticProperties`), seeded by `BlocksSharedState::load_cart_state()` via `wp_interactivity_config()` and read fresh on every request that needs it (`cart.ts`'s `getRestUrl()`).

### The envelope

`itemInContext` and `findItem` both return the same shape:

```ts
type Envelope = {
	// addressing — resolves even when no product data is loaded
	productId: number | null;
	variationId: number | null;
	draftKey: DraftKey;
	// product data
	product: ProductResponseItem | null;
	variation: ProductResponseItem | null;
	baseProduct: ProductResponseItem | null;
	// shopper input
	draftItem?: DraftItem;
	// paired cart line
	cartItem?: CartItem | OptimisticCartItem;
};
```

Every member is a **lazy accessor**: reading `product` runs only product resolution; the pairing ladder that resolves `cartItem` runs only when `cartItem` is read; `draftItem` returns the cached, live view for its `(draftKey, id)` pair. The envelope object itself is rebuilt on every read — a new object, not a cached one; only `draftItem`'s own identity is stabilized, by the view cache in `draft-internals.ts`.

`product` keeps an **entry-point-divergent no-match contract** — the same member name answers differently depending on how it was reached:

| entry point | a variable product whose selection matches nothing |
| --- | --- |
| `itemInContext.product` | the **base product** — display never blanks; SSR renders base values, an empty hidden variation input, a hidden description. |
| `findItem({ id })` | variation-by-id direct when `id` names a variation; otherwise the product unchanged. |
| `findItem({ id, selectedAttributes })` | **`null`** — the existence probe that lets a caller distinguish "no match" from "the base product." |

`variation` is the resolved variation or `null` at every entry point, and is **read-only** — see [Removed and relocated members](#removed-and-relocated-members). `baseProduct` is always the family's base/parent product.

#### `findItem`'s four addressing forms

`findItem` replaces the old `findProduct` getter with one function covering four addressing forms, each returning the same lazy envelope:

1.  **`{ id }`** — any product, variation, or grouped-child id; the `findProduct` replacement for a product-only read. The Product Button's grouped in-cart aggregate sums exactly this form over a grouped product's children:

    ```ts
    product?.grouped_products.reduce(
    	( total, childId ) =>
    		total + ( state.findItem( { id: childId } ).cartItem?.quantity ?? 0 ),
    	0
    );
    ```

    (`atomic/blocks/product-elements/button/frontend.ts`)

2.  **`{ id, selectedAttributes }`** — narrows a variable product to the variation matching a caller-supplied attribute selection; the variation-existence probe. The variation selector uses it to tell "a variation matched" apart from "nothing matched":

    ```ts
    const result = wooState.findItem( { id: product.id, selectedAttributes } ).product;
    const matchedVariation = result && result.id !== product.id ? result : null;
    ```

    (`blocks/add-to-cart-with-options/variation-selector/frontend.ts`)

3.  **`{ key }`** — an explicit cart-line key, pairing exactly with no identity guessing, regardless of `id`. The mini-cart's own row resolves its line this way:

    ```ts
    const cartItem = woocommerceState.findItem( { id, key } ).cartItem;
    ```

    (`blocks/mini-cart/frontend.ts`) — once `key` is given it pairs exactly; `id` here only feeds the envelope's other members, never the pairing itself.

4.  **`{ filter }`** — caller-owned narrowing, replacing id-based identity matching entirely, for a caller with its own notion of line identity (an extension pairing against a payload shape core has no name for):

    ```ts
    state.findItem( { filter: ( item ) => item.id === productId } );
    ```

#### Pairing the cart line (`cartItem`)

`cartItem` is populated only when the resolution ladder narrows to exactly one candidate line:

1.  **An explicit `key`** pairs exactly, with no further checks — the caller already knows precisely which line this is.
2.  Otherwise, **product/variation identity** — using the resolved draft's *effective* attributes (its specified selection, completed from the matching variation's own meta wherever the draft leaves an attribute unspecified) plus a namespaced extension-prop comparison against each candidate line's `extensions[<namespace>]` — must resolve to exactly one line. A `filter` argument replaces this identity matching entirely.

Any remaining ambiguity — zero lines, or more than one that can't be told apart — leaves `cartItem` `undefined`. The server owns cart-line identity, so the client never guesses at it; consumers must handle `cartItem === undefined`.

### Context

A single context namespace, `woocommerce`, carries addressing only — never resolved data, never selection state:

```html
<!-- one bag, one namespace; every key optional and independently inherited/overridden -->
<li data-wp-context='woocommerce::{ "productId": 123, "variationId": null, "draftKey": "collection/q1/123" }'>
	<!-- a grouped child row deeper in the tree overrides productId only; draftKey keeps inheriting -->
	<div data-wp-context='woocommerce::{ "productId": 456, "variationId": null }'>…</div>
</li>
```

`productId`/`variationId` fall back to `state.products.productId`/`.variationId` when a container declares neither key; `draftKey` falls back to the reserved global key, `'woocommerce/global'`. Declaring a key at all — even `null` — always wins over the state fallback; only the *absence* of the key falls back.

The two core containers each emit **one** `data-wp-context` attribute carrying every key they need:

-   `ProductTemplate.php` — one bag per Product Collection loop item, with `productId`, `variationId: null`, and the card's own minted `draftKey`.
-   `SingleProduct.php` — one bag on the block's own wrapper, with the same three keys.

A grouped child row overrides only `productId` (and sets `variationId: null`) on its own local bag — `draftKey` keeps inheriting from its container (`AddToCartWithOptions/GroupedProductItemSelector.php`'s checkbox markup). An extension gets the same isolation from markup alone by declaring a `draftKey`-only bag, with zero core changes.

**Exception — one shipped consumer reads addressing directly.** The Add to Cart with Options form's grouped-product check needs to know whether the *enclosing form's own product* is grouped, not whatever product a nested context override currently points at. Because a grouped child row's own quantity input overrides `productId` to its own (simple) id for its per-child reads, a context-aware read (`itemInContext.baseProduct`) would resolve to the child on every keystroke inside a child row — never `'grouped'` — and permanently skip the whole-group validation the moment a shopper touches a child field. The form's `setQuantity` action therefore reads the raw page-level addressing directly:

```ts
const topLevelProduct = wooState.products.items[ wooState.products.productId ];
if ( topLevelProduct?.type === 'grouped' ) {
	actions.validateGroupedProductQuantity();
} else {
	actions.validateQuantity( productId, value );
}
```

(`blocks/add-to-cart-with-options/frontend.ts`) — this is the one place among the store's consumers that reads `state.products.*` directly instead of through the envelope, and it is deliberate, not an oversight.

### First paint (SSR mirror)

`ProductsStore::register_getters()` registers the envelope's product members as PHP closures nested **one level inside** `itemInContext` — not three flat top-level getters:

```php
wp_interactivity_state( 'woocommerce', array(
	'itemInContext' => array(
		'baseProduct' => function () { /* … */ },
		'variation'   => function () { /* … */ },
		'product'     => function () { /* … */ },
	),
) );
```

Each closure resolves from the unified `woocommerce::` context bag first, falls back to `state.products.productId`/`.variationId`, and is draft-ignoring — the server never resolves the shopper's client-only draft selection. Because they read `wp_interactivity_state()` at call time, they only need registering once per request, no matter how many products get loaded.

Six SSR-rendered directive bindings depend on these closures resolving correctly before hydration:

| binding | file | binding string |
| --- | --- | --- |
| SKU | `ProductSKU.php` | `data-wp-text="state.itemInContext.product.sku"` |
| stock-availability text | `ProductStockIndicator.php` | `data-wp-text="state.itemInContext.product.stock_availability.text"` |
| specification fields | `ProductSpecifications.php` | `data-wp-text="state.itemInContext.product.<api_field>"` |
| quantity minimum/maximum/step | `AddToCartWithOptions/QuantitySelector.php` | `data-wp-bind--min="woocommerce::state.itemInContext.product.add_to_cart.minimum"` (`.maximum`, `.multiple_of` for max/step) |
| hidden variation-id input | `AddToCartWithOptions/AddToCartWithOptions.php` | `data-wp-bind--value="woocommerce::state.itemInContext.variation.id"` |
| variation-description visibility | `AddToCartWithOptions/VariationDescription.php` | `data-wp-bind--hidden="woocommerce::!state.itemInContext.variation.description"` |

The SKU, stock, and specification bindings render inside an element whose own `data-wp-interactive` is already `woocommerce`, so they reference the path bare (`state.itemInContext...`); the quantity and hidden-input bindings sit inside `woocommerce/add-to-cart-with-options[-quantity-selector]` elements, so they spell out the namespace (`woocommerce::state...`). The variation-description binding crosses namespaces the other way: its own element's `data-wp-interactive` is `woocommerce/product-elements`, so its `woocommerce::` prefix is load-bearing, not decorative.

The mini-cart's own SSR path is namespace churn only — the path suffix is unchanged: `data-wp-each--cart-item="woocommerce::state.cart.items"` (`MiniCartProductsTableBlock.php`), same as it read under the old, separate cart namespace.

### Writing: drafts

A `DraftItem` is exactly a Store API `cart/add-item` payload — there is no mapping layer between what a purchase surface collects and what gets posted:

```ts
type DraftKey = string; // an opaque address for one collection

type DraftItem = {
	id: number; // product or variation id; the per-collection uniqueness key
	quantity?: number; // optional — an omitted quantity defaults server-side to the product minimum
	variation?: SelectedAttributes[]; // the SPECIFIED selection; [] means nothing specified
	[ extensionProp: string ]: unknown; // namespaced, e.g. 'my-plugin/gift-note'
};

state.draftItems: Record< DraftKey, DraftItem[] >;
```

Extension props ride at the payload root, namespaced (a key containing a `/`), exactly as the Store API accepts them. `state.draftItems` is never server-seeded: collections are created lazily, on a shopper's first write to a key, and hold at most one draft per product `id`. A container declares an opaque, server-minted `draftKey` in the single `woocommerce::` context bag to isolate its subtree (see [Context](#context)); a surface wrapped in no container resolves the reserved global key, `'woocommerce/global'`.

The `draftItem` an envelope hands back — `itemInContext.draftItem`, or `findItem( { id } ).draftItem` — is the store's **only** write surface. It is a live, per-`(draftKey, id)` view, not a plain object or a copy: reading it answers the live draft's values when one exists, else the surface's server-filed seed values (see [The server half](#the-server-half)) — a read never materializes anything, it only subscribes. There is no draft-writing action anywhere on the store: `upsertDraftItem` has been removed, and nothing action-shaped replaces it. First edit and hundredth edit are the same spelling: `itemInContext.draftItem.quantity = 3`, `itemInContext.draftItem.variation = attrs`.

Writing through the view does one of two things, depending on whether a draft already exists for the resolved id (or for another member of the same product family):

-   **Onto an existing draft, a merge.** The write mutates the live draft in place, notifies every surface that resolves the same key, and is honored by `addItem` posting (which reads the collection at call time).
-   **Onto an untouched surface, a materialize.** The first write composes a new draft from the surface's server-filed seed and files it into the collection in one atomic step, so an untouched field falls back to its server-rendered default exactly as if the shopper had never touched it.

Two write-specific rules apply regardless of which of the above happens:

-   **Writing `variation` migrates `id`.** The view re-resolves the family's matching variation against the newly written attributes and re-files the *same* draft under whichever id they resolve to — the matched variation's id, or the base product's own id when nothing matches — with `quantity` and any extension props riding along unchanged. This is what keeps at most one draft per product family on the write path.
-   **`id` cannot be written directly.** A draft's identity is store-managed — it follows `variation`, never a direct assignment — so an attempted `draftItem.id = …` is rejected: a dev-build `console.warn`, and a no-op, leaving state unchanged.

**`quantity` is optional.** A materializing write whose composed draft carries no numeric `quantity` still materializes, with a dev-build warning: a quantity-less draft is a valid `cart/add-item` payload, and the server defaults an omitted quantity to the product's own minimum purchase quantity. Nothing client-side synthesizes a quantity on the draft's behalf.

**There is no assignable counterpart to `variation`.** The envelope's `variation` member is read-only at both entry points; the only way to write a selection is through `draftItem.variation`. A caller holding only a variation id — with no attribute set at hand — writes through the id-direct rung instead: `findItem( { id: variationId } ).draftItem`, which resolves and merges onto the same family draft `draftItem.variation = attrs` would. (A previous, now-removed member let a caller assign a resolved variation object directly on a separate registration this revision unified away; its setter forwarded into the very same write routine, framed at the time as keeping two stores' selection state in sync. With one store, there is nothing left to sync — `itemInContext.variation` and `itemInContext.draftItem.variation` are two reads of the same resolution, not two independent pieces of state. See [Removed and relocated members](#removed-and-relocated-members).)

**Bystander discipline.** Because watch and init callbacks re-run on every surface that resolves a shared collection, only a genuine shopper edit — or a clamp of the shared value itself — may write to a draft. A sibling surface the shopper never touched must not write its stale local default back over the shared draft (for example, a never-selected variation surface must not overwrite "nothing selected" onto a variation another surface resolved). When building a new surface that shares a collection, gate every draft write behind an actual user action.

### Adding to cart: `addItem`

`actions.addItem( payload? )` is polymorphic:

-   **`addItem()`** (no argument) resolves the in-context product via `state.itemInContext` and posts the resolved collection's draft(s) for it:
    -   a simple or variable product posts its own live draft, **falling back to its *effective* seed** when no live draft exists yet — the family seed re-addressed to the in-context id, not just the surface's own id;
    -   a grouped product posts every declared child's draft — each child id read directly off the in-context product's own `grouped_products` list — whose `quantity` is greater than `0`; untouched children never post, because seeds are not consulted on this rung.

    Multiple children are posted as one auto-batched request set, not one request per child.
-   **`addItem( payload )`** posts the payload verbatim — extension props at its root included — bypassing key and product resolution entirely. This is the path an extension composing its own `cart/add-item` payload (a bundle carrying `wc-bundle-demo/children`, say) uses.

**Product-scoped posting is a guarantee, not a side effect.** `addItem` posts only the in-context product's draft (simple/variable), the grouped parent's declared children with `quantity > 0`, or an explicit payload — it **never iterates a collection**. When the resolution yields no draft (and no seed), `addItem` sends nothing.

Every posted item optimistically bumps a matching existing cart line's quantity in place (unless `sold_individually`) or is pushed as a new line, commits or rolls back through the mutation queue, and fires the legacy added-to-cart event once per call on success. A cycle whose requests all fail rolls the cart back to its pre-cycle snapshot and surfaces a `woocommerce/store-notices` notice.

> **Caveat — notice narrowing.** Form and button adds no longer pass a notice-suppression flag. An exact add stays notice-silent, but a genuinely divergent server commit — a stock cap or a concurrent change — now surfaces a "quantity changed" notice where the previous code path was silent. This is a deliberate narrowing, not a regression in correctness.

The store also exposes the retained cart-line actions:

-   `actions.updateItem( { key, quantity } )` — sets a cart line's quantity to an absolute value via `update-item`; a no-op when no line matches `key`.
-   `actions.removeItem( key )` — removes a cart line by key.
-   `actions.refresh()` — re-fetches the server cart, bypassing the browser cache.
-   `actions.addCartItem( args, options? )` — the lower-level keyed/keyless add-or-update path, retained permanently because the shopper-lists blocks (not a target of this revision) consume it. New purchase UI should prefer `addItem` / `updateItem`.

### The server half

Server-side rendering mints each collection's key and files each surface's initial draft as server state, so every visible value is correct in the initial HTML before hydration. **Nothing on the server writes a draft**: containers declare keys, purchase surfaces file seeds, and the client resolves and materializes drafts from there.

`ProductTemplate.php` (Product Collection loop items) and `SingleProduct.php` (Single Product block) each mint a key and emit the single `woocommerce::` context bag documented under [Context](#context), carrying `productId`/`variationId`/`draftKey` together. Each also injects the same `draftKey` into its existing `render_block_context` filter, so descendant purchase surfaces render with the container's key in their block context and can file their seeds under it. That is the entire server-side isolation mechanism: one context bag plus block-context propagation — no push/pop, no registration.

Each purchase surface files its initial `cart/add-item` payload as server state, under its collection's key, into the unified namespace's `draftSeeds`:

```php
wp_interactivity_state( 'woocommerce', array(
	'draftSeeds' => array(
		$draft_key => array(
			$product_id => $seed_payload, // an initial cart/add-item payload
		),
	),
) );
```

(`AddToCartWithOptions.php`'s form-level emitter, and `AddToCartWithOptions/Utils::make_quantity_input_interactive()`, reached by the quantity-selector and grouped-product child-row blocks.) `$draft_key` is read from the surface's block context (`$block->context['draftKey'] ?? 'woocommerce/global'`) — the key its container injected, or the reserved global key when no container wraps it. Seeds accumulate across surfaces into one `draftSeeds` payload and print once.

On the client, seeds are read only through `getServerState( 'woocommerce' )?.draftSeeds` — the runtime's intact, per-page, navigation-fresh copy — and consulted at exactly two points: the draft view materializes a new draft composed from the seed on the shopper's first write to an untouched surface, and `addItem` falls back to the *effective* seed for an untouched simple/variable surface with no live draft. A seed is never applied into a collection, so a re-delivered seed (on a region re-render or client-side navigation) can never replace or inject into an edited draft.

Grouped-product child rows seed at quantity `0` (each is optional), so an untouched grouped form posts none of them; a grouped parent seeds nothing at the form level (it has no single id to add). A directly-referenced variation carries its own `{ attribute, value }` pairs in its seed, so an untouched direct-variation surface posts a line the cart-line pairing ladder can match.

`BlocksSharedState::load_cart_state()` seeds the read-only cart mirror (`state.cart`) into the unified `woocommerce` namespace, and `restUrl` into the same namespace's config plane (`wp_interactivity_config( 'woocommerce', […] )`) alongside currency, locale, and `nonOptimisticProperties`. It seeds no draft addressing — no keys, no seeds — so the client's `state.draftItems` starts empty on a fresh load; every collection is established by a shopper write against a container-declared or global key.

### Draft lifecycle

Drafts live for the length of a browsing session by design — a property of where they live, not of any per-surface machinery:

-   They **survive region remounts.** A quantity edited on a Product Collection card persists across an enhanced-pagination away-and-back round trip: the card re-renders with the same server-minted key, global state was never touched, and render-time getter evaluation repaints the surviving draft on the first post-remount frame.
-   They **survive cross-page client-side navigation.** A draft edited on a purchase surface survives a genuine client-side (router-region) navigation to another page and back, for the lifetime of the continuous session. Store modules persist across the navigation; the incoming page's server state merges non-destructively and never carries drafts; a returning surface re-declares its same key and its drafts re-attach. Surfaces wrapped in no container share the one global collection across pages, so product A's unwrapped form on page B shows the edit made on page A.
-   They **reset on a hard reload.** All client state reinitializes; `state.draftItems` starts empty and seeds re-derive fresh. Drafts are client-side only — there is no persistence layer.

There is **no lifecycle machinery** behind this — no ledger, no restore protocol, no per-surface reconstruction. Survival is what the model yields on its own: global state that outlives region swaps and navigations, plus render-stable keys that re-address the same collection wherever a surface re-renders. In particular, a **remounted variable-product card re-presents its recorded attribute selection.** The card's attribute-selection UI state lives in `woocommerce` context, and the remount does discard that context — but `itemInContext.variation` derives from the surviving family draft *ahead of* that context (`index.ts`'s `resolveVariationInContext` checks the draft before falling back to addressing), so the matched variation resolves again on the first post-remount frame with no context to read from: the chips/dropdown re-check, and every reader derived from the resolved variation — price, SKU, stock, gallery, and hidden-input bindings — repaints to match. Nothing re-runs the original attribute-selection logic; the derivation simply reaches, from the draft alone, the same conclusion the discarded context used to hold. Display and posting stay in agreement, now in the shopper's favor: what re-presents is exactly what would be added.

> **History.** An earlier iteration of this store kept drafts in context-held collections and kept a remounted Product Collection card's draft alive with Product-Collection-specific machinery: a module-private ledger keyed by a derived card identity, a register-or-restore init directive on every card, a render-time bridge in the resolver, a per-card `data-wp-init`, `seedDraftIfAbsent` with its own context bags, and empty-collection context bags. That entire apparatus — and a `removeDraftItem` action alongside it — was deleted with no successor when drafts moved into keyed global state. None of it exists in the shipped store; it is recorded here only so readers migrating from that model know it is gone.

#### Residuals worth knowing

These are accepted, shipped behaviors of the current validation-grade surface:

-   **The extension seed contract.** Declaring only the `draftKey` context bag gives an extension correct **client-side** addressing — its subtree resolves its collection, direct mutation through the draft view works, and the extension can read `state.draftItems[ <its own key> ]`. But **wrapping a core seed-emitting surface additionally requires propagating the key through `render_block_context`** so that surface files its seed under the extension's key. Without that, an untouched wrapped surface has no seed under the resolved key and posts nothing — a safe no-data outcome, never wrong data.
-   **Cross-page Single Product instance collisions.** Two Single Product blocks for the same product on two different pages each mint `single-product/<productId>/1`, so under client-side navigation they share one collection. Within any single page, isolation is fully preserved (the occurrence counter distinguishes the instances); the collision is observable only across a client-side page navigation.
-   **Same-`(key, id)` seed filing is last-write-wins.** When one product is filed twice under one key (e.g. standalone and as a grouped child, both unwrapped on one page), the later filing wins — the same order-dependent ambiguity the single shared collection already carried.
-   **Hand-authored collections without a `queryId`** all mint under `collection/0/<productId>` and can therefore share drafts per product across two such collections. Enhanced pagination requires authored `queryId`s, so this affects only hand-rolled markup.
-   **Duplicate-id drafts are possible via direct `push`.** Now that direct mutation is first-class, appending a second draft with an existing `id` straight onto a collection bypasses the one-draft-per-`id` invariant (lookups then resolve first-match). This is bounded — no shipped consumer appends directly; every write that goes through the draft view resolves the existing (exact-id or family) draft first and merges onto it rather than appending, which is what maintains the invariant on the supported write path.

### Removed and relocated members

This revision retires several members with no aliasing window — the store is private, locked, and has no third-party consumers to migrate gently. Each one's replacement, if any, is below.

-   **`findProduct` is removed.** Every call site becomes a `findItem` form: `findProduct({ id })` → `findItem({ id }).product`; `findProduct({ id, selectedAttributes })` → `findItem({ id, selectedAttributes }).product`. See [`findItem`'s four addressing forms](#finditems-four-addressing-forms).
-   **`inCartQuantity` is removed, with no store-level replacement.** A simple or variable product's own in-cart quantity is `itemInContext.cartItem?.quantity ?? 0` — the removed getter's own else-branch, now read directly. The grouped aggregate the getter used to compute (summing every child's own paired line) now lives in the Product Button's own private store, as `groupedInCartQuantity` (`atomic/blocks/product-elements/button/frontend.ts`), built on `findItem({ id: child }).cartItem`:

    ```ts
    get groupedInCartQuantity(): number {
    	const product = state.itemInContext.product;
    	return (
    		product?.grouped_products.reduce(
    			( total, childId ) =>
    				total + ( state.findItem( { id: childId } ).cartItem?.quantity ?? 0 ),
    			0
    		) ?? 0
    	);
    }
    ```

    Read this as a **reference pattern**, not shared-store API: it is the Product Button's own getter, on its own namespace (`woocommerce/product-button`), because the button is presently the aggregate's only consumer. A second consumer materializing is what would justify promoting it into a bounded read on the shared store.
-   **The assignable resolved-variation setter is removed, not relocated.** See [Writing: drafts](#writing-drafts) for what replaces it and why: the envelope's `variation` member is read-only, with no assignable counterpart, and a caller holding only a variation id writes through `findItem({ id: variationId }).draftItem` instead. The setter was retired as unconsumed surface — no shipped caller used it, by census — not because an envelope-nested setter is infeasible.

### Private and locked

Like every store in this folder, `woocommerce` is registered with `lock: true` and consumed with the `universalLock` consent string:

```ts
import '@woocommerce/stores/woocommerce';
import type { WooCommerce } from '@woocommerce/stores/woocommerce';
// only where cart actions or state.cart are needed:
import '@woocommerce/stores/woocommerce/cart';
import type { Store as WooCommerceCart } from '@woocommerce/stores/woocommerce/cart';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state } = store< WooCommerce >( 'woocommerce', {}, { lock: universalLock } );

// Reading the in-context product's in-cart count:
const count = state.itemInContext.cartItem?.quantity ?? 0;

// Adding the in-context product's resolved-collection draft(s) to the cart
// (needs the cart module's actions):
const { actions } = store< WooCommerceCart >( 'woocommerce', {}, { lock: universalLock } );
await actions.addItem();
```

The store is **not a public API** while this model is being validated. Its members can change or disappear without notice, and removing or changing state here is not a breaking change. Do not extend it from third-party code.

The PHP loaders in `ProductsStore.php` are gated by their own, separate PHP consent string (`ProductsStore::$consent_statement`); the JS half involves only `universalLock`. Keep both in sync with the source files rather than retyping — see [Source files](#source-files).

### Patterns and pitfalls

-   **Read through the envelope; write the `draftItem` it hands you.** Bind display to `state.itemInContext` (or `state.findItem(...)`), and record input by mutating the resolved draft view directly — `itemInContext.draftItem.quantity = value` — the one way to write a draft. Never write a draft from a callback that a shopper did not trigger.
-   **Handle `cartItem === undefined`.** The pairing ladder returns `undefined` whenever it cannot identify exactly one line. Treat that as "no known line", not "not in cart".
-   **Let containers own isolation.** A consumer block resolves the nearest key automatically; it never reads, writes, or declares a key itself. Declaring `draftKey` in the `woocommerce` context bag is the job of the container that wraps or repeats purchase UI — a core Product Collection card or Single Product block, or an extension's own container.
-   **Local context beats state, key presence beats absence.** A `woocommerce::` bag's own `productId`/`variationId` override the page-level `state.products.*` fallback for every descendant — even a key explicitly set to `null` wins over the state fallback. Only the *absence* of the key falls back to state.
-   **Load only what you need.** A display-only surface needs only `@woocommerce/stores/woocommerce`; load `@woocommerce/stores/woocommerce/cart` lazily, at the point a component actually needs cart actions or `state.cart` — see [Two script modules, one namespace](#two-script-modules-one-namespace).
-   **Always load a product before you bind to it.** If `wc_interactivity_api_load_product()` was never called for the addressed `productId`, `state.itemInContext.baseProduct` resolves to `null` and directive bindings silently render empty.
-   **Drafts survive client navigation, not a reload.** Draft edits persist across Interactivity-API region updates (such as collection pagination) and cross-page client-side navigations, but are discarded on a hard reload. Persisting only for the session is by design, not a gap.
-   **Do not extend this store from third-party code.** It is `lock: true` and private by design.

### Source files

-   JS:
    -   `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/index.ts` — the root module: state defaults, the envelope, `findItem`.
    -   `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/cart.ts` — the cart machinery module: the cart mirror, the cart actions.
    -   `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/cart-pairing.ts` — the extracted cart-line pairing ladder (folder-internal).
    -   `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/product-resolution.ts` — the extracted product/variation resolution primitive (folder-internal).
    -   `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/draft-internals.ts` — the draft read/write plumbing shared by both modules (folder-internal).
-   PHP:
    -   `plugins/woocommerce/src/Blocks/SharedStores/ProductsStore.php` — loads product/variation data; registers the nested `itemInContext` SSR closures.
    -   `plugins/woocommerce/src/Blocks/Utils/BlocksSharedState.php` — seeds the cart mirror and the `restUrl` config.
    -   Container blocks (each mints and declares a `draftKey`): `plugins/woocommerce/src/Blocks/BlockTypes/ProductTemplate.php` (Product Collection loop items), `plugins/woocommerce/src/Blocks/BlockTypes/SingleProduct.php` (Single Product block).
    -   Draft-seed filing: `plugins/woocommerce/src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php`, `.../AddToCartWithOptions/Utils.php`, `.../AddToCartWithOptions/GroupedProductItemSelector.php`.
-   Behavioral tests: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/test/index.ts`, `.../test/cart.ts`, `.../test/cart-pairing.test.ts`, `.../test/product-resolution.test.ts`, `.../test/draft-internals.test.ts`.
