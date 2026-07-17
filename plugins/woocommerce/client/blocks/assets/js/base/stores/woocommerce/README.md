# WooCommerce Interactivity API stores

This folder contains the Interactivity API (iAPI) stores that WooCommerce blocks use on the frontend. All stores here are **locked** (`lock: true`) and private by design — they are not intended for third-party extension, and removing or changing their state is **not** a breaking change. See the "Interactivity API Stores" section in `client/blocks/CLAUDE.md` and the [WordPress Private Stores reference](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference#private-stores).

Stores in this folder:

-   [`woocommerce/products`](#woocommerceproducts-store) — server-populated cache of product and variation data in Store API format.
-   [`woocommerce/cart`](#woocommercecart-store) — the read-only cart mirror plus context-held draft collections that back purchase UI (add-to-cart forms, buttons, the mini-cart), with mutation batching for performance.
-   `woocommerce/shopper-lists` — wishlist and saved-for-later state for the shopper-lists blocks (unchanged; no dedicated section here).

---

## `woocommerce/products` store

A locked, server-populated iAPI store that exposes WooCommerce products and variations in Store API format (`ProductResponseItem`) to interactive blocks. PHP loaders populate the raw data during render; JS and PHP derived getters expose the "current" product for the surrounding context so that directives like `data-wp-text="state.productInContext.sku"` resolve correctly on both the server (SSR) and the client.

**Source files:**

-   JS: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/products.ts`
-   PHP: `plugins/woocommerce/src/Blocks/SharedStores/ProductsStore.php`
-   PHP procedural wrappers: `plugins/woocommerce/includes/wc-interactivity-api-functions.php`
-   Behavioral tests: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/test/products.test.ts`

### When to use it

Use this store when an interactive block needs to read product fields (price, SKU, stock, images, attributes, …) inside a directive, and the surrounding markup implies a single "current" product — a single product page, a product in a product-collection loop, a grouped-product child, a variation inside a variable product, etc.

### Architecture at a glance

```text
PHP                                                  Client
┌───────────────────────────────────┐               ┌───────────────────────────────────┐
│ ProductsStore::load_product()     │               │ store<ProductsStore>(             │
│ ProductsStore::load_variations()  │  populates    │   'woocommerce/products'          │
│ ProductsStore::load_purchasable_  │──────────────▶│ )                                 │
│   child_products()                │               │                                   │
└────────────┬──────────────────────┘               │ state.products                    │
             │                                      │ state.productVariations           │
             ▼                                      │                                   │
   wp_interactivity_state(                          │ Derived getters:                  │
     'woocommerce/products',                        │ • state.baseProductInContext      │
     [ 'products' => ..., ... ]                     │ • state.productVariationInContext │
   )                                                │ • state.productInContext          │
                                                    └─────────────────┬─────────────────┘
                                                                 │
 Selection (one of):                                             ▼
 • Global: wp_interactivity_state(..., [        Directives bound in markup:
     'productId' => N, 'variationId' => null   data-wp-interactive="woocommerce/products"
   ])                                          data-wp-text="state.productInContext.sku"
 • Local context: data-wp-context=
     'woocommerce/products::{"productId":N}'
```

Two planes:

1. **Raw data** — `state.products` and `state.productVariations`, both keyed by ID. Populated from PHP.
2. **Selection** — `state.productId` / `state.variationId` identify the "current" product/variation. Can be set globally via `wp_interactivity_state`, or via local context with `data-wp-context`. **Local context takes precedence over global state.**

Derived getters mirror each other in JS (`products.ts`) and PHP (`ProductsStore::register_getters`) so that directive bindings resolve during SSR as well as on the client.

### State reference

| Property                    | Type                                                          | Origin                    | Notes                                                                                                                                                                                                                         |
| --------------------------- | ------------------------------------------------------------- | ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `products`                  | `Record<number, ProductResponseItem>`                         | Populated from PHP        | Keyed by product ID.                                                                                                                                                                                                          |
| `productVariations`         | `Record<number, ProductResponseItem>`                         | Populated from PHP        | Keyed by variation ID.                                                                                                                                                                                                        |
| `productId`                 | `number`                                                      | Populated / local context | Current product ID.                                                                                                                                                                                                           |
| `variationId`               | `number \| null`                                              | Populated / local context | Current variation ID, or `null`.                                                                                                                                                                                              |
| `baseProductInContext`      | `ProductResponseItem \| null`                                 | Derived                   | The top-level product for the current context. Always the parent product, **never** a variation.                                                                                                                              |
| `productVariationInContext` | `ProductResponseItem \| null`                                 | Derived                   | Currently selected variation, or `null` for simple/grouped/non-selected.                                                                                                                                                      |
| `productInContext`          | `ProductResponseItem \| null`                                 | Derived                   | `productVariationInContext ?? baseProductInContext`. Bind to this in the common case.                                                                                                                                         |
| `findProduct`               | `({ id, selectedAttributes }) => ProductResponseItem \| null` | Function                  | If `id` is a variation ID, returns it directly. For variable products with `selectedAttributes`, resolves to the matching variation. For any other product type (simple, grouped, external, etc.), returns the product as-is. |

### Populating state (PHP)

All loaders require a consent statement (they are experimental APIs). The literal to pass is:

```php
'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce'
```

Loaders are idempotent — calling them multiple times for the same ID (or the same variation parent) is cheap.

#### Load a single product

Use `wc_interactivity_api_load_product( $consent, $product_id )`.

From `SingleProduct` block (`src/Blocks/BlockTypes/SingleProduct.php`):

```php
// Load product into the shared products store.
wc_interactivity_api_load_product(
    'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
    $product->get_id()
);
```

#### Load purchasable child products of a grouped product

Use `wc_interactivity_api_load_purchasable_child_products( $consent, $parent_id )`. This uses the Store API `include[]` filter (not `parent[]`) because grouped-product children are standalone products, not variations. Only products whose `is_purchasable` is `true` are added to state.

#### Load variations of a variable product

Use `wc_interactivity_api_load_variations( $consent, $parent_id )`. This fetches `/wc/store/v1/products?parent[]=<id>&type=variation` and populates `state.productVariations`, keyed by variation ID. Variations for a given parent are only loaded once per request.

### Setting the "current" product

There are two ways to point the store at a specific product. **Local context always wins over global state.** Choose based on how the consuming block is rendered.

#### Globally (template-level)

Set `productId` / `variationId` on the store once for the page. Used when there is exactly one product on the page — e.g. the single product template.

From `SingleProductTemplate.php`:

```php
$product = wc_get_product( $post->ID );
if ( $product ) {
    $consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

    // Load the product data into the products store so derived
    // state closures can resolve it during server-side rendering.
    ProductsStore::load_product( $consent, $product->get_id() );

    wp_interactivity_state(
        'woocommerce/products',
        array(
            'productId'   => $product->get_id(),
            'variationId' => null,
        )
    );
}
```

#### Local context (block-level)

Set `productId` / `variationId` on a wrapper element via `data-wp-context`. Use this whenever the same block type can appear multiple times on a page for different products (product loops, grouped product children, variations).

Use `wp_interactivity_data_wp_context()` to generate the properly encoded attribute. From `SingleProduct.php`:

```php
wc_interactivity_api_load_product(
    'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
    $product->get_id()
);

$context = array(
    'productId'   => $product->get_id(),
    'variationId' => null,
);

printf(
    '<div data-wp-interactive="woocommerce/single-product" %s>%s</div>',
    wp_interactivity_data_wp_context( $context, 'woocommerce/products' ),
    $content
);
```

The second argument to `wp_interactivity_data_wp_context` (`'woocommerce/products'`) namespaces the context to the `woocommerce/products` store; the JS store's `getContext< ProductContext >( 'woocommerce/products' )` calls read from it.

### Reading product data in a block

Once state is populated and a current product is set, blocks read from it either through directives (SSR + client) or through a JS store reference.

#### From PHP / directives (SSR)

The derived getters are registered on the PHP side via `ProductsStore::register_getters()`, so bindings resolve during server render — no client-side flash during hydration.

From `ProductSKU.php`:

```php
$interactive_attributes = $is_interactive
    ? 'data-wp-interactive="woocommerce/products" data-wp-text="state.productInContext.sku"'
    : '';
```

Any `ProductResponseItem` field can be bound the same way, e.g. `state.productInContext.price_html`, `state.productInContext.stock_availability.text`, `state.baseProductInContext.name`.

#### From JS (client)

Import the store for its side effects and reference it with the `ProductsStore` type.

From `atomic/blocks/product-elements/button/frontend.ts`:

```ts
import { store } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

// Later, in a getter or action:
const product = productsState.productInContext;
if ( ! product ) {
	return;
}
// product.id, product.sku, product.prices.price, ...
```

#### Resolving a variation by attributes

Use `state.findProduct({ id, selectedAttributes })` when you have a product or variation ID. If the ID is a variation, it returns it directly. For variable products with `selectedAttributes`, it resolves to the matching variation. For any other product type (simple, grouped, external, etc.), it returns the product as-is.

From `base/utils/variations/does-cart-item-match-attributes.ts`:

```ts
const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

const parentProductId = productsState.productVariations[ cartItem.id ]?.parent;
const productAttributes =
	productsState.products[ parentProductId ]?.attributes ?? [];
```

For variable products, `findProduct` returns `null` when no variation matches the given attributes. For simple, grouped, external, or any other non-variable product type, it returns the product itself.

### Patterns and pitfalls

-   **Always load before you bind.** If `wc_interactivity_api_load_product` was never called for the current `productId`, `state.baseProductInContext` resolves to `null` and directive bindings silently render empty.
-   **Prefer `productInContext`** for "whatever is currently being shown". Use `baseProductInContext` / `productVariationInContext` only when the distinction matters (e.g. rendering a variation-specific description vs. the parent title).
-   **`data-wp-context` sets local context.** Use it whenever the same block type can appear multiple times on a page for different products.
-   **Local context beats state.** If a block is wrapped in a `data-wp-context="woocommerce/products::{ ... }"` element, its `productId` / `variationId` override any globally-set values for descendants of that element. See `test/products.test.ts` for the exact precedence rules — notably, a context that has `productId` but no `variationId` key does **not** fall back to the global `variationId`.
-   **Keep the consent string in sync.** The literal string is defined in `ProductsStore::$consent_statement` (PHP) and `universalLock` (JS). They are intentionally different (loaders vs. store lock); copy-paste from this README or the source files.
-   **Do not extend this store from third-party code.** It is `lock: true` and private by design; anything here can change or disappear without notice.

---

## `woocommerce/cart` store

A locked, private iAPI store that holds two things: a **read-only mirror of the server cart** (`state.cart`, the Store API `/cart` response with optimistic edits applied) and the **draft cart items** that back purchase UI — the quantities, variation selections, and extension data a shopper is configuring before they add to cart.

Shopper input is modeled as **draft cart items** held in **draft collections**. A draft is literally a Store API `cart/add-item` request payload — there is no mapping layer between what a form collects and what gets posted. A collection is how the store keeps the same product's input independent across the many places a purchase form can appear on one page (a product template's main form, a sticky add-to-cart bar, a grouped child, a Product Collection card, a bundle slot). `state.draftItems` is the page-wide collection; a container block isolates its own subtree by initializing a `draftItems` array in its `woocommerce/cart` context. Surfaces that resolve the same collection share drafts and stay in sync; surfaces under different collections are fully independent.

**Source files:**

-   JS: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/cart.ts`
-   Collection containers (each declares an empty `woocommerce/cart` collection): `plugins/woocommerce/src/Blocks/BlockTypes/ProductTemplate.php` (Product Collection loop items), `plugins/woocommerce/src/Blocks/BlockTypes/SingleProduct.php` (Single Product block)
-   Collection-root context (`queryId`): `plugins/woocommerce/src/Blocks/BlockTypes/ProductCollection/Renderer.php`
-   Draft seeding: `plugins/woocommerce/src/Blocks/BlockTypes/AddToCartWithOptions/Utils.php`
-   Cart-state seeding: `plugins/woocommerce/src/Blocks/Utils/BlocksSharedState.php`
-   Behavioral tests: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/test/cart.ts`

### When to use it

Use this store when an interactive block needs to record shopper purchase input before an add-to-cart (a quantity, a variation selection, a namespaced extension field), read the live/optimistic cart, or add, update, or remove cart lines. Because reads and writes resolve through the in-context draft collection and product, the same block code works whether it is rendered as a single product's main form, a Product Collection card, a mini-cart row, or a page-wide sticky bar.

### Architecture at a glance

```text
Two planes of state:

  state.cart          read-only mirror of the Store API /cart response
                      (with optimistic edits); the server owns line identity.

  state.draftItems    the page-wide draft collection: DraftItem[], at most one
                      draft per product id. A DraftItem IS a cart/add-item
                      payload. Lives alongside — never inside — state.cart.

Collection resolution (one internal place — resolveDraftItems):

  context.draftItems  a container's own collection, when it initialized one.
  state.draftItems    the page-wide collection, the fallback.
  resolved  = context.draftItems ?? state.draftItems

Reads (never guess a line):        Writes:

  state.itemInContext  ─┐            direct mutation of a resolved draft:
  state.findItem(…)     ├─▶ Envelope   itemInContext.draft.quantity = 3
  state.inCartQuantity ─┘  { cartItem?, draft? }   (reactive, honored by addItem)
                                     actions.upsertDraftItem( partial, { id? } )
                                     actions.removeDraftItem( { id? } )
                                     actions.addItem( payload? )  ── posts drafts
                                     actions.updateItem / removeItem / refresh
```

### The draft-collection model

`state.draftItems` is a flat `DraftItem[]` — the page-wide collection. A `DraftItem` is exactly a `cart/add-item` payload:

```ts
type DraftItem = {
    id: number; // product or variation id; also the per-collection uniqueness key
    quantity: number;
    variation?: SelectedAttributes[]; // for a variation draft
    [ extensionProp: string ]: unknown; // namespaced, e.g. 'my-plugin/gift-note'
};
```

Extension props ride at the payload root, namespaced (a key containing a `/`), exactly as the Store API accepts them — there is no separate wrapper for them. This is the read model's mirror image of `CartItem.extensions`, which reflects the server's response once a line exists.

A collection holds **at most one draft per product `id`**: `upsertDraftItem` merges into the draft whose `id` matches and appends otherwise. When a surface genuinely needs two independent drafts of the same product (two bundle slots offering the same child), it initializes **two collections** rather than reaching for a second addressing concept — one draft per product per collection is the invariant, and the collection is the only isolation boundary.

Drafts are **client-only reactive state the server never mirrors into `state.cart`**. This is what makes client-side (router-region) navigation draft-safe: the cart mirror reconciles with the server, while a shopper's in-progress draft edits live in a plane the server never overwrites.

### The container primitive

A container isolates its subtree by initializing an empty draft collection in its `woocommerce/cart` context — plain markup, with no id to mint and no service to call:

```html
data-wp-context---draft-items='woocommerce/cart::{"draftItems":[]}'
```

Any surface nested inside that container then resolves that collection; a surface with no such ancestor resolves the page-wide `state.draftItems`. The `draftItems` key is what creates the boundary — other `woocommerce/cart` context keys (`draftSeed`, a mini-cart row's `id`/`key`) do **not**.

The two core containers WooCommerce ships are:

-   `ProductTemplate.php` — emits the empty-collection bag on each Product Collection loop item (`<li>`), isolating every card in the grid.
-   `SingleProduct.php` — emits the same bag on the Single Product block wrapper, isolating that block from the page-wide surfaces.

The three-hyphen attribute name is required because `wp_interactivity_data_wp_context()` always emits an attribute literally named `data-wp-context`; an element that already carries a default context bag (here, the `woocommerce/products` product context) cannot carry a second one under the same attribute name — the HTML parser would keep the first and silently drop the second. `data-wp-context---<suffix>` is the supported way to add a second, namespaced context bag on one element (the same pattern the shopper-lists blocks use for `data-wp-context---notices`).

**Resolution lives in one place.** A module-private `resolveDraftItems()` in `cart.ts` implements `context.draftItems ?? state.draftItems` (nearest context wins by the runtime's own inheritance; a call from outside any directive degrades to `state.draftItems` rather than throwing). Every getter and action routes through it. **No consumer ever writes that fallback conditional** — you read a resolved draft through the envelope (below), and a container's own subtree code may read the collection it initialized straight from its `woocommerce/cart` context.

### Reading: the in-context envelope

Reads return an `Envelope`, which pairs the resolved collection's draft with the matching cart line:

```ts
type Envelope = {
    cartItem?: CartItem | OptimisticCartItem | undefined;
    draft?: DraftItem | undefined;
};
```

There is no `isInCart` (or any other) member — no consumer needed the "in the cart, but no single identifiable line" tri-state, so the envelope carries only `cartItem` and `draft`.

-   `state.itemInContext` — the envelope for the product the surrounding markup implies, resolved through the `woocommerce/products` store's `productInContext`. The resolved collection's draft is paired with its cart line. When no product is in context (nothing rendered), it returns an empty envelope.
-   `state.findItem( { id?, key?, filter? } )` — the explicit primitive behind `itemInContext`, for looking up a specific draft/line pair. It resolves the draft from the nearest collection, same as `itemInContext`.
-   `state.inCartQuantity` — the in-context product's in-cart quantity. A grouped product aggregates its children's own in-cart quantities (each resolved independently, by id); a variable product resolves through its currently selected variation (the same resolution as `itemInContext`); a simple product is its own paired line's quantity. It is `0` when nothing pairs or no product is in context.

**Pairing never guesses.** `cartItem` is populated only when the pairing ladder resolves to exactly one candidate line:

1.  **An explicit `key`** pairs exactly, with no further checks — the caller already knows precisely which line this is (a mini-cart row passes the `key` it reads from its own `woocommerce/cart` context).
2.  Otherwise, **product/variation identity** (using the resolved draft's own `variation`, when one exists) **plus a namespaced extension-prop comparison** against each candidate line's `extensions[<namespace>]` must resolve to exactly one line. A `filter` argument replaces this identity matching entirely, for extensions with their own notion of line identity.

Any remaining ambiguity — zero lines, or more than one line that cannot be told apart — leaves **`cartItem` `undefined`**. The server owns cart-line identity, so the client never guesses at it. Consumers must handle `cartItem === undefined`.

### Writing: drafts

Draft state is written **directly, or through the two conveniences** — whichever is clearer at the call site:

-   **Direct mutation is first-class.** The `draft` an envelope hands back is the live reactive object from the resolved collection, not a copy. Mutating it — `itemInContext.draft.quantity = 3`, or `findItem( { id } ).draft` — is supported, notifies every surface that resolves the same collection, and is honored by `addItem` posting (which reads the collection at call time). The same is true of the resolved collection itself; a container's own subtree code may mutate the collection it initialized.
-   `actions.upsertDraftItem( partial, { id? }? )` — the creation/merge convenience. It resolves the nearest draft collection, then merges `partial` into the draft whose `id` matches — `id` resolved from `options.id` when given, else `partial.id` — appending a new draft otherwise. It **rejects, leaving state unchanged**, when: no numeric target `id` can be resolved; `partial.id` disagrees with an already-resolved target `id` (an in-place identity change — remove the draft and add a new one instead); or a brand-new draft is created without a numeric `quantity`. Each rejection is a dev-build `console.warn` and a silent no-op in production.
-   `actions.removeDraftItem( { id? }? )` — removes the resolved collection's draft for the given product. It rejects (state unchanged) on a non-numeric `id`; naming a product with no matching draft in the resolved collection is a silent no-op.

> This revision keeps `upsertDraftItem` / `removeDraftItem` as conveniences, but the surface no longer treats drafts as read-only or writes as "actions only": the write policy leaves room for a planned future revision that drops `upsertDraftItem` in favor of editing `itemInContext.draft` directly.

**Bystander discipline.** Because watch and init callbacks re-run on every surface that resolves a shared collection, only a genuine shopper edit — or a clamp of the shared value itself — may write to a draft. A sibling surface that the shopper never touched must not write its stale local default back over the shared draft (for example, a never-selected variation surface must not overwrite "nothing selected" onto a variation another surface resolved). When building a new surface that shares a collection, gate every draft write behind an actual user action.

### Adding to cart: `addItem`

`actions.addItem( payload? )` is polymorphic:

-   **`addItem()`** (no argument) resolves the in-context product via `woocommerce/products` and posts the resolved collection's draft(s) for it: a simple or variable product's own single draft (`itemInContext.draft`), or, for a grouped product, every child's draft (children resolved one-directionally through the products store) whose `quantity` is greater than `0`. Multiple children are posted as one auto-batched request set, not one request per child. It never posts another collection's or another product's draft, and sends nothing when the resolution yields no draft.
-   **`addItem( payload )`** posts the payload verbatim — extension props at its root included — bypassing collection and product resolution entirely. This is the path an extension composing its own `cart/add-item` payload (a bundle carrying `wc-bundle-demo/children`, say) uses.

Every posted item optimistically bumps a matching existing cart line's quantity in place (unless `sold_individually`) or is pushed as a new line, commits or rolls back through the mutation queue, and fires the legacy added-to-cart event once per call on success. A cycle whose requests all fail rolls the cart back to its pre-cycle snapshot and surfaces a `woocommerce/store-notices` notice.

> **Caveat — notice narrowing.** Form and button adds no longer pass a notice-suppression flag. An exact add stays notice-silent (the store proves the server total equals the pre-add total plus the posted delta and suppresses those lines), but a genuinely divergent server commit — a stock cap or a concurrent change — now surfaces a "quantity changed" notice where the previous code path was silent. This is a deliberate narrowing, not a regression in correctness.

The store also exposes the retained cart-line actions:

-   `actions.updateItem( { key, quantity } )` — sets a cart line's quantity to an absolute value via `update-item`; a no-op when no line matches `key`.
-   `actions.removeItem( key )` — removes a cart line by key.
-   `actions.refresh()` — re-fetches the server cart, bypassing the browser cache.
-   `actions.addCartItem( args, options? )` — the lower-level keyed/keyless add-or-update path, retained permanently because the shopper-lists blocks (not a target of this revision) consume it. New purchase UI should prefer `addItem` / `updateItem`.

### The server half

Server-side rendering declares collection boundaries and seeds initial drafts, so every visible value is correct in the initial HTML before hydration. **Nothing on the server addresses a draft**: the context tree in the delivered markup is the whole story.

#### Container boundaries

`ProductTemplate.php` (Product Collection loop items) and `SingleProduct.php` (Single Product block) each emit the empty-collection bag documented under [The container primitive](#the-container-primitive). That is the entire server-side isolation mechanism: an empty `data-wp-context---draft-items` bag, no minting, no push/pop, no registration.

`ProductCollection/Renderer.php` adds a `queryId` to the collection root's own `woocommerce/product-collection` context bag (the same value already exposed in that element's `data-wp-router-region` attribute). This is product-collection domain data, not a cart concept; the cart store's internal draft-lifecycle machinery reads it to keep a card's edited draft alive across an enhanced-pagination remount (see [Draft lifecycle](#draft-lifecycle)).

#### Initialize-if-absent draft seeding

A purchase surface seeds its initial draft as a context bag consumed by an init directive. `AddToCartWithOptions/Utils.php` emits the initial `cart/add-item` payload as a `woocommerce/cart` draft-seed bag and wires the init:

```html
data-wp-context---draft-seed='woocommerce/cart::{"draftSeed":{"id":123,"quantity":1}}'
data-wp-init--seed-draft='woocommerce/cart::actions.seedDraftIfAbsent'
```

`actions.seedDraftIfAbsent()` reads the **server-rendered** context — `getServerContext< { draftSeed?: DraftItem } >( 'woocommerce/cart' )?.draftSeed`, immune to the reactive proxy's client-side edits — resolves the nearest draft collection, and copies the seed into it **only when that collection holds no draft for the seed's product `id`**. This initialize-if-absent rule is what lets a router-region re-render's seed read run harmlessly: it can never clobber a shopper's in-progress edit, because a present draft is left untouched.

#### Cart-state seeding

`BlocksSharedState::load_cart_state()` seeds the read-only cart mirror (`state.cart`), the REST URL, and the notice id into `woocommerce/cart` state. It seeds **no page-wide addressing state** — the collection tree is established entirely by container markup, and the page-wide `state.draftItems` starts empty on the client.

### Draft lifecycle

Drafts live for the length of a page session by design:

-   They **survive client-side (router-region) navigation**, including a Product Collection enhanced-pagination away-and-back round trip: the page-wide collection lives in client-only module state the server never overwrites, and an edited card's draft is preserved across the card's unmount/remount.
-   They **reset on a full page reload** — module state and context both reinitialize.

The machinery that makes a remounted Product Collection card's draft survive is **internal to the store and unreachable by any consumer, even one holding lock consent**. It is three cooperating pieces, none of which is a member of the store surface:

-   a **module-private ledger** (a plain module variable, never a store member) that holds each collection card's live collection, keyed by an internally derived `(queryId, productId)` identity;
-   a **register-or-restore init** the loop item's server markup resolves by name (`data-wp-init="woocommerce/cart::actions.registerOrRestoreDraftCollection"`), which registers a card's collection on first render and restores it from the ledger on a remount;
-   a **render-time bridge** inside the resolver, so the first post-remount paint already shows the restored draft.

`registerOrRestoreDraftCollection` appears on `actions` **only** so the server-emitted directive string can resolve it; it is not part of the documented surface, derives its own card identity from context, and is a no-op wherever that identity cannot be derived. Extensions should not call it.

#### Residuals worth knowing

These are accepted, shipped behaviors of the current validation-grade surface:

-   **Extension containers inside router regions get isolation but not remount survival.** The remount-survival machinery is wired only for Product Collection loop items — the surface the lifecycle guarantee names. An extension's own container inside a collection (or a Single Product block nested inside a collection card) isolates its subtree correctly, but its draft is not preserved across an enhanced-pagination remount; such a surface owns its own persistence.
-   **Duplicate-id drafts are possible via direct `push`.** Now that direct mutation is first-class, appending a second draft with an existing `id` straight onto a collection bypasses the one-draft-per-`id` invariant (lookups then resolve first-match). This is bounded — no shipped consumer appends directly; creation flows through `upsertDraftItem`, which maintains the invariant — and is a documented residual until a future revision designs direct creation.
-   **A raw `context.draftItems` reader can see one pre-reconciliation frame after a card remount.** Core getter-driven surfaces are bridged and paint correctly on the first post-remount render; only an extension binding that reads `context.draftItems` raw can observe a single stale frame, for one effect-cycle on one navigation edge.
-   **A remounted variable-product card presents its server-seeded default.** A variable product's variation selection lives in client context the remount discards, so a remounted card resolves against — and displays — its server-seeded default rather than the prior selection. Display and action stay in agreement.

### Private and locked

Like every store in this folder, `woocommerce/cart` is registered with `lock: true` and consumed with the `universalLock` consent string:

```ts
import '@woocommerce/stores/woocommerce/cart';
import type { Store as CartStore } from '@woocommerce/stores/woocommerce/cart';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
    'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state, actions } = store< CartStore >(
    'woocommerce/cart',
    {},
    { lock: universalLock }
);

// Reading the in-context product's in-cart count (from Product Button):
const count = state.inCartQuantity;

// Adding the in-context product's resolved-collection draft(s) to the cart:
await actions.addItem();
```

The store is **not a public API** while the draft-collection model is being validated. Its members can change or disappear without notice, and removing or changing state here is not a breaking change. Do not extend it from third-party code.

Unlike the products store, the cart store has **no consent-gated PHP surface** — its server side is plain container and seed markup, so `universalLock` (JS, for the store lock) is the only consent string it involves. Copy it from this README or the source files rather than retyping.

### Patterns and pitfalls

-   **Read through the envelope; write the draft it hands you.** Bind display to `state.itemInContext` / `state.inCartQuantity`, and record input either by mutating the resolved draft directly (`itemInContext.draft.quantity = value`) or through `upsertDraftItem` / `removeDraftItem`. Never write a draft from a callback that a shopper did not trigger.
-   **Handle `cartItem === undefined`.** The pairing ladder returns `undefined` whenever it cannot identify exactly one line. Treat that as "no known line", not "not in cart".
-   **Let containers own isolation.** A consumer block resolves the nearest collection automatically; it does not initialize `draftItems` itself. Initializing an empty collection in `woocommerce/cart` context is the job of the container that wraps or repeats purchase UI.
-   **Drafts survive client navigation, not a reload.** Draft edits persist across Interactivity-API region updates (such as collection pagination) but are discarded on a full page load. Persisting only for the session is by design, not a gap.
-   **Do not extend this store from third-party code.** It is `lock: true` and private by design.
