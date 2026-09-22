# WooCommerce Interactivity API stores

This folder holds one Interactivity API (iAPI) store, registered under the namespace `woocommerce` with the script module id `@woocommerce/stores/woocommerce`. `index.ts` calls `store()` exactly once; everything else in this folder — the catalog layer, the product scope envelope, and the cart plane — is assembled into that single call from sibling files, one concern per file.

The store registers with a private-store acknowledgement string:

```ts
const storeConsent =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state, actions } = store< WooCommerceStore >(
	'woocommerce',
	{ state: initialState, actions: cartActions },
	{ lock: storeConsent }
);
```

That string marks the store private by WooCommerce's own convention, not by an Interactivity API lock: the string is the API's built-in "universal unlock", so passing it leaves no lock registered for the `woocommerce` namespace, and any script that reuses the same string can call `store( 'woocommerce', {}, { lock } )` and resolve the same state and actions. Nothing about that is a loophole to build on — the store is not intended for third-party extension, nothing here is promised to third parties, and its state and actions can change or disappear without notice. Reusing the acknowledgement string only proves the caller has read this warning, not that the store is safe to depend on.

This folder also registers a second, unrelated store: `woocommerce/shopper-lists` (`shopper-lists.ts`), which holds shopper wishlist and saved-for-later state. It is out of scope for this file; see its own source for details.

## Compatibility

The `woocommerce/products` Interactivity API namespace, and the `@woocommerce/stores/woocommerce/cart` and `@woocommerce/stores/woocommerce/products` script module ids, no longer exist and have no aliases. Code that still imports either module id, or that reads state under the `woocommerce/products` namespace, stops working and has to move to the single `woocommerce` namespace and `@woocommerce/stores/woocommerce` module id this file describes.

## Source map

Client (`client/blocks/assets/js/base/stores/woocommerce/`):

-   `index.ts` — the store's one `store()` registration; assembles the other files' state and actions into the `woocommerce` namespace and binds each sibling module to the returned state reference.
-   `catalog.ts` — the catalog layer: `products`, `productVariations`, `template`.
-   `scope.ts` — the product scope envelope: `productScopes`, `productScope`, `findProductScope`.
-   `cart-actions.ts` — the cart plane: `cart` and the four actions (`addCartItem`, `updateCartItem`, `removeCartItem`, `refreshCart`).
-   `notices.ts` — cart-line matching helpers and the info/error notices the cart actions raise from a mutation's server response.
-   `mutation-batcher.ts` — the microtask-based queue that coalesces concurrent cart requests into one `wc/store/v1/batch` request per cycle and reconciles the result.
-   `legacy-events.ts` — the `wc-blocks_added_to_cart` DOM event dispatcher, consumed outside this store.
-   `types.ts` — the store's exported TypeScript surface (`WooCommerceStore` and the types re-exported from the files above).
-   `test/` — behavioral tests for the above.

Server:

-   `src/Blocks/SharedStores/ProductsStore.php` — hydrates `products` and `productVariations` from the Store API, and registers the server-side `productScope` closure that mirrors `scope.ts` for SSR.
-   `src/Blocks/SharedStores/ProductScopes.php` — names the scope elements this store's state is read against, and tracks the "place" stack that gives a form its scope.
-   `includes/wc-interactivity-api-functions.php` — the three procedural loader functions.

## The catalog layer

`state.products` and `state.productVariations` are both keyed by id and hold data in Store API `ProductResponseItem` shape. Only the server writes to them — through the three loader functions below.

`state.template` additionally carries the single-product template's current selection:

```ts
template?: {
	productId: number;
	variation: TemplateVariationAttribute[];
};

// TemplateVariationAttribute, exported from catalog.ts, is the
// `{ attribute, value }` shape used everywhere a "selected variation
// attributes" list appears in this store.
type TemplateVariationAttribute = {
	attribute: string;
	value: string;
};
```

Only `SingleProductTemplate` seeds `template` (on `template_redirect`, once per request, for the single-product template only), so it is absent on every other page — a product-collection loop, a grouped product's children, or any block outside that one template. `state.productScope`'s resolution (below) falls back to it last, after a scope's own record and its declared context.

### Loading data (PHP)

All three loaders require a consent statement, since they are experimental APIs:

```php
'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce'
```

They are idempotent: calling one again for the same id (or the same variation parent) is cheap and does not issue a second REST lookup.

-   `wc_interactivity_api_load_product( $consent_statement, $product_id )` loads one product's Store API representation into `state.products`, keyed by its id.
-   `wc_interactivity_api_load_purchasable_child_products( $consent_statement, $parent_id )` loads every purchasable child of a grouped product into `state.products`, keyed by id. It uses the Store API `include[]` filter rather than `parent[]`, because a grouped product's children are standalone products, not variations; a child whose `is_purchasable` is `false` is left out.
-   `wc_interactivity_api_load_variations( $consent_statement, $parent_id )` loads every variation of a variable product into `state.productVariations`, keyed by variation id. Variations for a given parent are fetched once per request.

## Product scopes

A scope element is any element that declares the `woocommerce` namespace context. That context can carry up to four keys:

| Context key   | Type                             | Declared by                                                                                                                                    |
| ------------- | --------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `productId`   | `number`                          | Every core scope element, together with `variation` and `scopeName`.                                                                             |
| `variation`   | `{ attribute: string; value: string }[]` | Every core scope element, together with `productId` and `scopeName`.                                                                       |
| `scopeName`   | `string`                          | Every core scope element, together with `productId` and `variation`.                                                                              |
| `cartItemKey` | `string`                          | No core scope element. A script passes it directly to `findProductScope` when it already knows a cart line's key — see "The server side and first paint" for which blocks are core scope elements, and "`state.findProductScope`" below. |

A write to any context key lands on the nearest ancestor element that declares that key, so `productId`, `variation` and `scopeName` are declared together on one element rather than split across ancestors — otherwise a write meant for one would silently land on the wrong ancestor for another.

### `state.productScope`

`state.productScope` is the envelope for the reading element: the `woocommerce` context declared on it or its nearest scope-declaring ancestor.

```ts
type ProductScopeEnvelope = {
	readonly scopeName: string | null;
	productId: number;
	variation: { attribute: string; value: string }[];
	readonly draftCartItem: DraftCartItem | undefined;
	readonly baseProduct: ProductResponseItem | null;
	readonly productVariation: ProductResponseItem | null;
	readonly product: ProductResponseItem | null;
	readonly cartItem: CartItem | OptimisticCartItem | null;
};
```

| Member             | Writable | Meaning                                                                                                                                                          |
| ------------------ | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `scopeName`         | no       | The name addressing this scope's record under `state.productScopes` — the reading element's declared `scopeName`, or `'_default'` when it declares none.        |
| `productId`         | yes      | The scope's resolved product id.                                                                                                                                  |
| `variation`         | yes      | The scope's resolved selected variation attributes.                                                                                                               |
| `draftCartItem`     | no       | The scope's draft cart item (below). Always present for `productScope`.                                                                                          |
| `baseProduct`       | no       | The scope's top-level product, never a variation.                                                                                                                 |
| `productVariation`  | no       | The variation matching the scope's selected attributes, or `null` when none matches (or none is selected).                                                       |
| `product`           | no       | `productVariation` when one resolved, otherwise `baseProduct`. Bind to this in the common case: "whatever is currently shown".                                   |
| `cartItem`          | no       | The committed or in-flight cart line the scope corresponds to, or `null`. Matched by `cartItemKey` when the context declares one, otherwise by product and variation, the same way `addCartItem` matches an existing line. |

`productId` and `variation` resolve, in order:

1.  The scope's own record under `state.productScopes` (its `draftCartItem.id` / `draftCartItem.variation`), when one exists and carries that field.
2.  The declared `woocommerce` context (the reading element's own, or its nearest scope-declaring ancestor's).
3.  `state.template`, falling back to product id `0` and an empty variation when even that is absent.

`baseProduct`, `productVariation`, `product` and `cartItem` are derived from whichever `productId` / `variation` that resolution lands on; they are never resolved independently.

### `state.productScopes` and writing a scope

`state.productScopes` holds every scope's client-only record, keyed by scope name:

```ts
type ProductScopesState = Record< string, { draftCartItem?: DraftCartItemRecord } >;

type DraftCartItemRecord = {
	id?: number;
	variation?: { attribute: string; value: string }[];
	[ extensionKey: string ]: unknown;
};
```

Every scope sharing the same `scopeName` — whether that name comes from the reading element's own context or an ancestor's — reads and writes the same record.

Writing `state.productScope.productId` or `.variation` (or, equivalently, `draftCartItem.id` / `draftCartItem.variation`) updates **both** the scope's record and its declared `woocommerce` context, so a directive bound to `context.productId` on the same element stays in sync. Writing any other key under `draftCartItem` — including an extension prop a third-party block adds — updates the record only. Either kind of write creates the record first if it does not exist yet, snapshotting the scope's currently resolved `productId` and `variation` into it so the new record starts with a complete identity even when only one field was written.

`draftCartItem` is a `Proxy`, not a plain object:

```ts
type DraftCartItem = {
	id: number | undefined;
	variation: { attribute: string; value: string }[] | undefined;
	quantity: number;
	[ extensionKey: string ]: unknown;
};
```

Reading or writing `id` / `variation` through it goes through the identity resolution and writes above. `quantity` reads `1` until the shopper (or a script) sets it — there is no default in the record itself. Any other key reads from and writes to the record directly.

### `state.findProductScope( ref )`

```ts
findProductScope: ( ref: {
	productId?: number;
	variation?: { attribute: string; value: string }[];
	scopeName?: string;
	cartItemKey?: string;
} ) => ProductScopeEnvelope;
```

Builds a product scope envelope from a caller-supplied ref instead of the reading element's context. It is script-only: a directive calls a function with no arguments, so `findProductScope` has to be called from an action, callback, or getter — never bound to a directive directly. `blocks/mini-cart/frontend.ts` uses it to resolve a cart line already known by key:

```ts
const cartItem = woocommerceState.findProductScope( {
	cartItemKey: key,
} ).cartItem;
```

`findProductScope` differs from `productScope` in two ways:

-   `baseProduct`, `productVariation`, `product` and `cartItem` always resolve from the ref's own `productId` / `variation` — never from a `state.productScopes` record, even when the ref also carries a `scopeName`.
-   `scopeName`: a ref carrying `scopeName` addresses that name whether or not a record with that name exists yet. A ref with no `scopeName` falls back to the single record under `state.productScopes` whose draft id matches the ref's product id, and only when exactly one record matches — zero or several matches resolve to `scopeName: null` and `draftCartItem: undefined`.

## The cart plane

`state.cart` is the store's own view of the cart, not a mirror of the server's:

```ts
type CartActionsState = {
	cart: Omit< Cart, 'items' > & {
		items: ( OptimisticCartItem | CartItem )[];
		totals: CartResponseTotals;
	};
};
```

Its `items` array can hold a mix of server-confirmed `CartItem`s and lines the client itself wrote before the server answered. Consumers only ever read `state.cart` — the four actions below, and the server's own responses, are what write it.

### Optimistic change and rollback

Each action updates `state.cart.items` synchronously, before its request is even sent, so the UI reflects a mutation immediately:

-   `addCartItem` bumps a matching existing line's `quantity` in place, or pushes a new line when none matches.
-   `updateCartItem` sets a matching line's `quantity` in place.
-   `removeCartItem` filters the line out of the array.

A line whose product is sold individually (`sold_individually: true`) is the one exception: `addCartItem` and `updateCartItem` both skip the in-place bump for it, leaving its quantity exactly as the server last confirmed until the server itself responds.

A pushed-fresh line — the one `addCartItem` creates when no existing line matches — is an `OptimisticCartItem`:

```ts
type OptimisticCartItem = {
	key?: string | undefined;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
	type: string;
};
```

It carries **no `key`** until the server confirms it and the confirmed `CartItem` replaces it — a consumer cannot key off `item.key` on a line it has just added. A line that was *bumped in place* rather than freshly pushed — by `addCartItem` finding a match, or by `updateCartItem` — is a different case: it is already an existing `CartItem` being mutated, so it keeps its `key` throughout.

Reconciliation happens once per mutation batch cycle, not once per request: if **any** request in the cycle succeeded, the queue commits the last successful server cart it saw; only when **every** request in the cycle failed does it roll the whole cart back to the snapshot taken before the cycle's first optimistic write.

### The four actions

| Action                                | Arguments                                                                                                     | Returns                       |
| -------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | ------------------------------ |
| `addCartItem( payload?, options? )`    | `payload`: an `AddCartItemPayload`, a DOM `Event`, or omitted — see "The two call forms" below. `options.showCartUpdatesNotices`: defaults to `true`. | `Promise< AddCartItemOutcome >` |
| `updateCartItem( { key, quantity } )`  | `key`: the cart line's key. `quantity`: the line's new **absolute** quantity.                                     | `Promise< void >`              |
| `removeCartItem( key )`                | `key`: the cart line's key.                                                                                       | `Promise< void >`              |
| `refreshCart()`                        | none                                                                                                               | `Promise< void >`              |

`AddCartItemPayload`, field by field:

| Field           | Type                                       | Required | Meaning                                                                                                                                       |
| ---------------- | ------------------------------------------- | -------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`             | `number`                                    | yes      | The product id.                                                                                                                                |
| `variation`      | `{ attribute: string; value: string }[]`    | no       | The selected variation attributes.                                                                                                             |
| `quantity`       | `number`                                    | no       | The **delta** `add-item` adds to a matching existing line, or the new line's quantity when none matches. Defaults to `1` when omitted. Never an absolute quantity — contrast with `updateCartItem`'s `quantity` above, which is always absolute. |
| any other key    | `unknown`                                   | no       | An extension prop, posted to the Store API `add-item` endpoint exactly as given — the pass-through that lets a form carry extra fields.       |

`AddCartItemOptions` is `{ showCartUpdatesNotices?: boolean }`. When `true` (the default), a successful add may surface the auto-update/auto-removal info notices the server's response implies.

`refreshCart()` re-fetches the cart from the Store API and replaces `state.cart` with the response. It is a no-op while a mutation cycle is already processing (that cycle applies its own server state when it settles) or while a previous refresh is still in flight, and it retries with a growing delay when the request itself fails. The store triggers one automatically once, right after it registers, and again whenever the legacy `@wordpress/data` cart store dispatches its own sync event.

### The two call forms of `addCartItem`

**With a payload** — `addCartItem( payload, options? )`, where `payload` is an `AddCartItemPayload` — posts the payload as given and removes no scope record.

**With no payload** — `addCartItem()`, `addCartItem( undefined, options )`, or a directive-bound call, which the Interactivity runtime invokes with the triggering DOM `Event` as its first argument and which the action treats identically to no payload — posts the reading element's `state.productScope.draftCartItem` and, when the server accepts it, removes that scope's record from `state.productScopes`.

Every core call site uses the payload form, spreading the scope's own draft record into a payload of its own so the shopper's typed selection and quantity are preserved after the call: `atomic/blocks/product-elements/button/frontend.ts`, `blocks/add-to-cart-with-options/frontend.ts`, and `blocks/add-to-cart-with-options/grouped-product-selector/frontend.ts`. Two other surfaces build a payload from a list entry instead of a scope's draft: `blocks/wishlist/frontend.ts` and `blocks/saved-for-later/frontend.ts`, moving a shopper's saved item back into the cart.

```ts
const record = wooState.productScopes[ scopeName ]?.draftCartItem;
const { variation } = wooState.productScope;

yield wooActions.addCartItem(
	{
		...record,
		id: product.id,
		variation,
		quantity: context.quantityToAdd,
	},
	{ showCartUpdatesNotices: false }
);
```

### The outcome contract

```ts
type AddCartItemError = {
	code?: string;
	message: string;
};

type AddCartItemOutcome =
	| { success: true }
	| { success: false; error: AddCartItemError };
```

`addCartItem` resolves `{ success: true }` or `{ success: false, error }` for every outcome — including a server rejection or a transport failure — and never rejects. `error.code` carries the server's per-item error code (e.g. `woocommerce_rest_product_out_of_stock`) when there is one, or the batcher's own `unknown_error` fallback; it is absent on a whole-batch or transport failure. `error.message` is always present.

Attribution is per call, even when several calls are coalesced into one `wc/store/v1/batch` request: each call's outcome reflects only its own product's result, never a shared or last-write-wins value from a sibling call in the same batch.

```ts
const outcome = ( yield cartActions.addCartItem( {
	id: listItem.id,
	quantity: 1,
} ) ) as AddCartItemOutcome;

if ( ! outcome.success ) {
	return;
}
```

### Once-per-cycle cross-cutting effects

Every mutation with at least one successful request in its cycle fires up to three cross-cutting effects, exactly once per cycle rather than once per request:

-   the cross-store sync event (`wc-blocks_store_sync_required`, `detail.type: 'from_iAPI'`), which drives the legacy `@wordpress/data` cart store's resync;
-   the legacy `wc-blocks_added_to_cart` DOM event (`legacy-events.ts`);
-   the screen-reader "added to cart" announcement, made only when the store is configured with an `addedToCartText` message.

Every action that sends a cart request attaches `meta: { quantityChanges, origin }` describing that one mutation. `origin` is `'add'` for both `addCartItem` and `updateCartItem` — regardless of whether the mutation actually hit the Store API's `add-item` or `update-item` endpoint — and `'remove'` for `removeCartItem`. Once a cycle settles, its successful entries decide what fires:

-   No successful entry (every mutation in the cycle failed) — nothing fires.
-   At least one successful entry, of any origin — the sync event fires once, with `quantityChanges` merged from every successful entry.
-   At least one successful entry with `origin: 'add'` — the legacy event and the announcement also fire, in addition to the sync event (the legacy event dispatches first, preserving the order older code relied on). A remove-only cycle, however many concurrent removals it batched, never fires the legacy event or the announcement.

Per-item error notices, and each action's own info-notice update, are untouched by this mechanism: they stay per-action and per-item, raised directly from each action's own request handling.

## The server side and first paint

PHP registers a `productScope` closure (`ProductsStore::register_getters()`) that mirrors `scope.ts`'s resolution order — a scope's `productScopes` record, then its declared `woocommerce` context, then `state.template` — so a directive such as `data-wp-text="state.productScope.product.name"` resolves during server-side rendering, before any client-side hydration runs. A `productScopes` record seeded server-side (for example by a form declaring an initial `draftCartItem`) is honoured on that first paint the same way it is on the client afterward.

### Naming a scope: `ProductScopes`

`Automattic\WooCommerce\Blocks\SharedStores\ProductScopes` derives every scope element's name from the values that identify it — the same values on every render of the same element, so the name is stable across requests. An occurrence number is appended only from the second time those exact values are seen in a request, so two otherwise-identical elements (for example the same product repeated in a loop) still get distinct names.

*Places* are the scope elements whose inner blocks establish where a form renders — a Single Product block, or a Product Collection loop item. `ProductScopes` tracks them as a stack, so "the current place" is always the innermost one still open. Its public entry points, the ones a contributor adding a new kind of scope element reaches for:

-   `enter_place()`, called before a place's inner blocks render, and `leave_place()`, called after — bracketing the render the way `SingleProduct` and `ProductTemplate` already do (`enter_place()` runs from the `render_block_context` filter callback that fires before the inner blocks; `leave_place()` runs once they have rendered).
-   `current_place()` reads the innermost open place, for naming something relative to it.
-   `name_scope_element()` names a scope element that is not a place and not a form (e.g. a legacy Products block loop item).
-   `name_form()` names a form and reports whether that form declares its own scope (see below).
-   `get_grouped_child_scope_name()` derives a grouped product's child row name from its form's name.
-   `get_scope_context()` builds the `{ productId, variation, scopeName }` array a scope element declares as its `woocommerce` context.
-   `get_scope_variation()` reads the `{ attribute, value }` entries a product (when it is itself a variation) contributes to that context.
-   `set_current_form_name()`, `get_current_form_name()` and `clear_current_form_name()` make the enclosing form's name available to the blocks it renders through `do_blocks()`, and clear it once that render finishes.
-   `reset()` clears all of the above between requests; tests call it, a scope element does not.

Nothing here is persisted across requests.

### Which blocks are scope elements

-   `SingleProduct` — the single-product block; a place.
-   `ProductTemplate` — a Product Collection loop item; a place.
-   `ProductQuery` — the legacy Products block's loop items, given their scope directly rather than as a place.
-   `AddToCartWithOptions` — the add-to-cart form itself.
-   `GroupedProductItem` — a grouped product's child row.

### A form's scope

`AddToCartWithOptions::render()` names its form with `ProductScopes::name_form( [ current_place(), $product_id ] )`. The first form rendered for a given product in a given place shares that place's own scope — it declares no `woocommerce` context of its own, and directives inside it resolve `state.productScope` against the place's context. Every later form for the same product in the same place declares its own `{ productId, variation, scopeName }` context directly on its `<form>` element instead, because a repeated product cannot keep sharing the place's single scope. An element can declare context for only one Interactivity API namespace, so when the form declares its own `woocommerce` context this way, the block's own (non-`woocommerce`) context moves to a wrapping `<div>` around the form.

A grouped product's child rows each get their own scope name, `get_grouped_child_scope_name( $form_name, $child_product_id )` — the form's own name with the child's product id appended.

Every DOM id inside an Add to Cart with Options form traces back to the same name: the form's own quantity input and its variation attribute groups (and their options) key off the form's own name directly — `quantity_{form name}`, `wc_product_attribute_{form name}_{attribute slug}`, and so on — while a grouped child row's quantity input and label key off that child row's own scope name instead, `quantity_{form name}:{child product id}`. Either way, the id is unique on a page carrying more than one form and identical across re-renders of the same form.

## Patterns and pitfalls

-   **Load a product before binding to it.** If no loader has populated `state.products` (or `state.productVariations`) for the product a scope resolves to, `state.productScope.product` and its siblings resolve `null`, and directive bindings render empty rather than erroring.
-   **Prefer `state.productScope.product`** for "whatever is currently shown". Reach for `baseProduct` or `productVariation` only when the distinction between the parent and the selected variation actually matters.
-   **Declare `productId`, `variation` and `scopeName` together, on one element.** A context write lands on the nearest ancestor declaring that key, so splitting the three across ancestors sends a write to the wrong one.
-   **The loaders' consent statement and the store's acknowledgement string are not the same string, and are not interchangeable.** One tells the loaders you accept an experimental PHP API may change; the other tells the Interactivity API you accept a private store's state may change. Copy each one from its own source rather than from memory.
-   **Do not extend this store from third-party code.** It is private by WooCommerce's own convention and can change or disappear without notice, whatever the Interactivity API's own lock mechanism does or does not enforce.
-   **A draft's `id` is not always the id to post.** `draftCartItem.id` is the scope's `productId` — for a variable product with a variation selected, that is the *parent's* id, not the variation's. Posting a draft exactly as it stands sends the server into its own variation-matching, which core surfaces avoid by posting the already-resolved product's id (`state.productScope.product.id`) instead.
