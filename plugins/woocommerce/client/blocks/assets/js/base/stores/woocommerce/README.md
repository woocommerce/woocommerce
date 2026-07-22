# WooCommerce Interactivity API stores

This folder contains the Interactivity API (iAPI) stores that WooCommerce blocks use on the frontend. All stores here are **locked** (`lock: true`) and private by design — they are not intended for third-party extension, and removing or changing their state is **not** a breaking change. See the "Interactivity API Stores" section in `client/blocks/CLAUDE.md` and the [WordPress Private Stores reference](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference#private-stores).

Stores in this folder:

-   [`woocommerce/products`](#woocommerceproducts-store) — server-populated cache of product and variation data in Store API format.
-   [`woocommerce/cart`](#woocommercecart-store) — the read-only cart mirror plus a keyed global home for the draft cart items that back purchase UI (add-to-cart forms, buttons, the mini-cart), addressed by server-defined draft keys, with mutation batching for performance.
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

Shopper input is modeled as **draft cart items** that live in **global state, addressed by server-defined draft keys**. A draft is literally a Store API `cart/add-item` request payload — there is no mapping layer between what a form collects and what gets posted. `state.draftItems` is a keyed map of draft collections (`Record<DraftKey, DraftItem[]>`); each collection keeps the same product's input independent across the many places a purchase form can appear (a product template's main form, a sticky add-to-cart bar, a grouped child, a Product Collection card, a bundle slot). A container block isolates its own subtree by declaring an opaque, server-minted `draftKey` in its `woocommerce/cart` context; a surface wrapped in no container resolves the one reserved session-global collection. Surfaces that resolve the same key share drafts and stay in sync; surfaces under different keys are fully independent.

**Source files:**

-   JS: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/cart.ts`
-   Container blocks (each mints and declares a `woocommerce/cart` draft key): `plugins/woocommerce/src/Blocks/BlockTypes/ProductTemplate.php` (Product Collection loop items), `plugins/woocommerce/src/Blocks/BlockTypes/SingleProduct.php` (Single Product block)
-   Product Collection root (`queryId` on block context + router region, read by `ProductTemplate.php` to mint keys): `plugins/woocommerce/src/Blocks/BlockTypes/ProductCollection/Renderer.php`
-   Draft-seed filing: `plugins/woocommerce/src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php`, `.../AddToCartWithOptions/Utils.php`, `.../AddToCartWithOptions/GroupedProductItemSelector.php`
-   Cart-state seeding: `plugins/woocommerce/src/Blocks/Utils/BlocksSharedState.php`
-   Behavioral tests: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/test/cart.ts`

### When to use it

Use this store when an interactive block needs to record shopper purchase input before an add-to-cart (a quantity, a variation selection, a namespaced extension field), read the live/optimistic cart, or add, update, or remove cart lines. Because every read and write resolves the draft collection for the nearest declared key and the product in context, the same block code works whether it is rendered as a single product's main form, a Product Collection card, a mini-cart row, or a page-wide sticky bar.

### Architecture at a glance

```text
Two planes of state:

  state.cart          read-only mirror of the Store API /cart response
                      (with optimistic edits); the server owns line identity.

  state.draftItems    the keyed draft home: Record<DraftKey, DraftItem[]>.
                      One collection per key, at most one draft per product id
                      within it. A DraftItem IS a cart/add-item payload. Lives
                      alongside — never inside — state.cart, and is written only
                      by the shopper (never server-seeded).

Key resolution (one internal place — resolveDraftKey / resolveCollection):

  context.draftKey    a container's declared key, inherited by its subtree.
  GLOBAL_DRAFT_KEY    'woocommerce/global', the reserved fallback key.
  key         = context.draftKey ?? GLOBAL_DRAFT_KEY
  collection  = state.draftItems[key]      (undefined until first write)

Reads (never guess a line):        Writes:

  state.itemInContext  ─┐            direct mutation of a resolved draft:
  state.findItem(…)     ├─▶ Envelope   itemInContext.draft.quantity = 3
  state.inCartQuantity ─┘  { cartItem?, draft? }   (reactive, honored by addItem)
                                     actions.upsertDraftItem( partial, { id? } )
                                     actions.addItem( payload? )  ── posts drafts
                                     actions.updateItem / removeItem / refresh
```

### The draft-collection model

`state.draftItems` is a keyed map of draft collections. A `DraftItem` is exactly a `cart/add-item` payload; a `DraftKey` is an opaque address for one collection:

```ts
type DraftKey = string; // an opaque address for one collection (see below)

type DraftItem = {
    id: number; // product or variation id; also the per-collection uniqueness key
    quantity: number;
    variation?: SelectedAttributes[]; // for a variation draft
    [ extensionProp: string ]: unknown; // namespaced, e.g. 'my-plugin/gift-note'
};

state.draftItems: Record< DraftKey, DraftItem[] >;
```

Extension props ride at the payload root, namespaced (a key containing a `/`), exactly as the Store API accepts them — there is no separate wrapper for them. This is the read model's mirror image of `CartItem.extensions`, which reflects the server's response once a line exists.

**Keys are opaque.** A `DraftKey` is a plain string whose only contract is equality: the same key resolves the same collection, and nothing else is promised — no parseable format, no stability beyond a single browsing session. Core mints two formats (see [The container primitive](#the-container-primitive)), but a consumer never parses or constructs one; it only ever declares a key it was handed (or, for an extension, one it chose) and lets the store resolve it. Because a surface's key is identical across successive server renders of that surface, its drafts re-attach after region remounts and client-side navigations.

**One collection per key; at most one draft per product `id` within it.** `upsertDraftItem` merges into the draft whose `id` matches and appends otherwise. When a surface genuinely needs two independent drafts of the same product (two bundle slots offering the same child), its containers declare **two keys** rather than reaching for a second addressing concept — one draft per product per collection is the invariant, and the key is the only isolation boundary.

**The server never seeds `state.draftItems`.** Collections are created lazily, on a shopper's first write to a key; the map starts empty on a fresh page load. Drafts are client-only reactive state the server never mirrors into `state.cart`. This is what makes client-side (router-region) navigation draft-safe: the cart mirror reconciles with the server, while a shopper's in-progress draft edits live in a plane the server never overwrites — keyed so they re-attach wherever their surface re-renders.

### The container primitive

A container isolates its subtree by declaring an opaque, server-minted draft key in its `woocommerce/cart` context — a single `draftKey`:

```html
data-wp-context---draft-key='woocommerce/cart::{"draftKey":"<key>"}'
```

Any surface nested inside that container then resolves that key's collection; a surface with no such ancestor resolves the reserved session-global collection under `GLOBAL_DRAFT_KEY` (`'woocommerce/global'`). The `draftKey` key is what creates the boundary — other `woocommerce/cart` context keys (a mini-cart row's `id`/`key`) do **not**.

The two core containers WooCommerce ships mint their key server-side:

-   `ProductTemplate.php` — emits a `draftKey` bag on each Product Collection loop item (`<li>`), minting `collection/<queryId>/<productId>`, isolating every card in the grid. (`queryId` is a static block attribute unchanged by pagination, so the card's key is stable across successive renders.)
-   `SingleProduct.php` — emits a `draftKey` bag on the Single Product block wrapper, minting `single-product/<productId>/<n>`, where `<n>` is a per-request, per-product document-order occurrence counter. The counter is what keeps two Single Product blocks for the same product on one page mutually isolated.

An extension gets the same primitive from markup alone: it declares a namespaced key of its own (e.g. `data-wp-context---draft-key='woocommerce/cart::{"draftKey":"my-plugin/slot-1"}'`) on its container element, with zero core changes. Key formats are internal and unpromised; the only contract is equality.

The three-hyphen attribute name is required because `wp_interactivity_data_wp_context()` always emits an attribute literally named `data-wp-context`; an element that already carries a default context bag (here, the `woocommerce/products` product context) cannot carry a second one under the same attribute name — the HTML parser would keep the first and silently drop the second. `data-wp-context---<suffix>` is the supported way to add a second, namespaced context bag on one element (the same pattern the shopper-lists blocks use for `data-wp-context---notices`).

**Resolution lives in one place.** A module-private `resolveDraftKey()` in `cart.ts` implements `context.draftKey ?? GLOBAL_DRAFT_KEY` (the nearest declared key wins by the runtime's own context inheritance; a call from outside any directive degrades to `GLOBAL_DRAFT_KEY` rather than throwing), and a companion `resolveCollection( key )` returns `state.draftItems[ key ]` (tolerating a not-yet-created collection as empty). Every getter and action routes through them. **No consumer ever reads or writes a key, or writes that fallback conditional** — you read a resolved draft through the envelope (below) and write it back, and the store does the addressing.

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
-   `state.findItem( { id?, key?, filter? } )` — the explicit primitive behind `itemInContext`, for looking up a specific draft/line pair. It resolves the draft from the nearest declared key's collection, same as `itemInContext`.
-   `state.inCartQuantity` — the in-context product's in-cart quantity. A grouped product aggregates its children's own in-cart quantities (each resolved independently, by id); a variable product resolves through its currently selected variation (the same resolution as `itemInContext`); a simple product is its own paired line's quantity. It is `0` when nothing pairs or no product is in context.

**Pairing never guesses.** `cartItem` is populated only when the pairing ladder resolves to exactly one candidate line:

1.  **An explicit `key`** pairs exactly, with no further checks — the caller already knows precisely which line this is (a mini-cart row passes the `key` it reads from its own `woocommerce/cart` context).
2.  Otherwise, **product/variation identity** (using the resolved draft's own `variation`, when one exists) **plus a namespaced extension-prop comparison** against each candidate line's `extensions[<namespace>]` must resolve to exactly one line. A `filter` argument replaces this identity matching entirely, for extensions with their own notion of line identity.

Any remaining ambiguity — zero lines, or more than one line that cannot be told apart — leaves **`cartItem` `undefined`**. The server owns cart-line identity, so the client never guesses at it. Consumers must handle `cartItem === undefined`.

### Writing: drafts

Draft state is written **directly, or through the creation/merge convenience** — whichever is clearer at the call site:

-   **Direct mutation is first-class.** The `draft` an envelope hands back is the live reactive object from the resolved collection, not a copy. Mutating it — `itemInContext.draft.quantity = 3`, or `findItem( { id } ).draft` — is supported, notifies every surface that resolves the same key, and is honored by `addItem` posting (which reads the collection at call time).
-   `actions.upsertDraftItem( partial, { id? }? )` — the creation/merge convenience. It resolves the nearest key's draft collection, then merges `partial` into the draft whose `id` matches — `id` resolved from `options.id` when given, else `partial.id` — appending a new draft otherwise. **Creation composes the new draft from the surface's server-filed seed** (`{ ...seed, ...partial }`, read via `getServerState()`; see [The server half](#the-server-half)), so an untouched field falls back to its server-rendered default, and the collection is materialized lazily on this first write. It **rejects, leaving state unchanged**, when: no numeric target `id` can be resolved; `partial.id` disagrees with an already-resolved target `id` (an in-place identity change — remove the draft and add a new one instead); or a brand-new draft is created without a numeric `quantity` (from either `partial` or the seed). Each rejection is a dev-build `console.warn` and a silent no-op in production.

**Bystander discipline.** Because watch and init callbacks re-run on every surface that resolves a shared collection, only a genuine shopper edit — or a clamp of the shared value itself — may write to a draft. A sibling surface that the shopper never touched must not write its stale local default back over the shared draft (for example, a never-selected variation surface must not overwrite "nothing selected" onto a variation another surface resolved). When building a new surface that shares a collection, gate every draft write behind an actual user action.

### Adding to cart: `addItem`

`actions.addItem( payload? )` is polymorphic:

-   **`addItem()`** (no argument) resolves the in-context product via `woocommerce/products` and posts the resolved collection's draft(s) for it:
    -   a simple or variable product posts its own single draft (`itemInContext.draft`), **falling back to the surface's server-filed seed** (`draft ?? seed`) so an untouched form still posts its default;
    -   a grouped product posts every declared child's draft (children resolved one-directionally through the products store) whose `quantity` is greater than `0` — untouched children never post, because seeds are not consulted on this rung.

    Multiple children are posted as one auto-batched request set, not one request per child.
-   **`addItem( payload )`** posts the payload verbatim — extension props at its root included — bypassing key and product resolution entirely. This is the path an extension composing its own `cart/add-item` payload (a bundle carrying `wc-bundle-demo/children`, say) uses.

**Product-scoped posting is a guarantee, not a side effect.** `addItem` posts only the in-context product's draft (simple/variable), the grouped parent's declared children with `quantity > 0`, or an explicit payload — it **never iterates a collection**. This matters now that a session-global collection accumulates drafts from every page a shopper visited: an add from one surface can never leak an unrelated product's draft that happens to share the same key. When the resolution yields no draft (and no seed), `addItem` sends nothing.

Every posted item optimistically bumps a matching existing cart line's quantity in place (unless `sold_individually`) or is pushed as a new line, commits or rolls back through the mutation queue, and fires the legacy added-to-cart event once per call on success. A cycle whose requests all fail rolls the cart back to its pre-cycle snapshot and surfaces a `woocommerce/store-notices` notice.

> **Caveat — notice narrowing.** Form and button adds no longer pass a notice-suppression flag. An exact add stays notice-silent (the store proves the server total equals the pre-add total plus the posted delta and suppresses those lines), but a genuinely divergent server commit — a stock cap or a concurrent change — now surfaces a "quantity changed" notice where the previous code path was silent. This is a deliberate narrowing, not a regression in correctness.

The store also exposes the retained cart-line actions:

-   `actions.updateItem( { key, quantity } )` — sets a cart line's quantity to an absolute value via `update-item`; a no-op when no line matches `key`.
-   `actions.removeItem( key )` — removes a cart line by key.
-   `actions.refresh()` — re-fetches the server cart, bypassing the browser cache.
-   `actions.addCartItem( args, options? )` — the lower-level keyed/keyless add-or-update path, retained permanently because the shopper-lists blocks (not a target of this revision) consume it. New purchase UI should prefer `addItem` / `updateItem`.

### The server half

Server-side rendering mints each collection's key and files each surface's initial draft as server state, so every visible value is correct in the initial HTML before hydration. **Nothing on the server writes a draft**: containers declare keys, purchase surfaces file seeds, and the client resolves and materializes drafts from there.

#### Container boundaries

`ProductTemplate.php` (Product Collection loop items) and `SingleProduct.php` (Single Product block) each mint a key and emit the `data-wp-context---draft-key` bag documented under [The container primitive](#the-container-primitive). Each also **injects the same `draftKey` into its existing `render_block_context` filter**, so descendant purchase surfaces render with the container's key in their block context and can file their seeds under it. That is the entire server-side isolation mechanism: a `draftKey` context bag plus block-context propagation — no push/pop, no registration.

`ProductCollection/Renderer.php` contributes **nothing** to the cart store. `queryId` lives only on the Product Collection block's own context (via `providesContext`) and on the collection root's `data-wp-router-region` attribute; `ProductTemplate.php` reads it from block context to mint each card's key. The cart store never sees `queryId`.

#### Draft seeding

Each purchase surface files its initial `cart/add-item` payload as server state, under its collection's key. The form-level emitter (`AddToCartWithOptions.php`) and the shared quantity-stepper emitter (`Utils::make_quantity_input_interactive()`, reached by the quantity-selector and grouped-product child-row blocks) each call:

```php
wp_interactivity_state( 'woocommerce/cart', array(
    'draftSeeds' => array(
        $draft_key => array(
            $product_id => $seed_payload, // an initial cart/add-item payload
        ),
    ),
) );
```

`$draft_key` is read from the surface's block context (`$block->context['draftKey'] ?? 'woocommerce/global'`) — the key its container injected, or the reserved global key when no container wraps it. The three seed-emitting blocks (`add-to-cart-with-options` and its quantity-selector and grouped-product child-row selectors) declare `draftKey` in their `usesContext` so the injected key actually reaches their render context. Seeds accumulate across surfaces into one `draftSeeds` payload and print once.

On the client, seeds are read **only through `getServerState( 'woocommerce/cart' )?.draftSeeds`** — the runtime's intact, per-page, navigation-fresh copy — and consulted at exactly two points: `upsertDraftItem` composes a new draft from the seed on the shopper's first write, and `addItem` falls back to the seed for an untouched simple/variable surface. A seed is **never applied into a collection**, so a re-delivered seed (on a region re-render or client-side navigation) can never replace or inject into an edited draft — the two live in different places. The runtime also auto-merges the incoming server state into a `state.draftSeeds` copy, but that client-side copy is **inert**: the store never reads it (it would accumulate stale entries across navigations); `getServerState()` is the only seed source.

Grouped-product child rows seed at quantity `0` (each is optional), so an untouched grouped form posts none of them; a grouped parent seeds nothing at the form level (it has no single id to add). A directly-referenced variation carries its own `{ attribute, value }` pairs in its seed, so an untouched direct-variation surface posts a line the cart-line pairing ladder can match.

#### Cart-state seeding

`BlocksSharedState::load_cart_state()` seeds the read-only cart mirror (`state.cart`) and the REST URL (`state.restUrl`) into `woocommerce/cart` state. It seeds **no draft addressing** — no keys, no seeds, and no notice id — so the client's `state.draftItems` starts empty on a fresh load; every collection is established by a shopper write against a container-declared or global key.

### Draft lifecycle

Drafts live for the length of a browsing session by design — a property of where they live, not of any per-surface machinery:

-   They **survive region remounts.** A quantity edited on a Product Collection card persists across an enhanced-pagination away-and-back round trip: the card re-renders with the same server-minted key, global state was never touched, and render-time getter evaluation repaints the surviving draft on the first post-remount frame.
-   They **survive cross-page client-side navigation.** A draft edited on a purchase surface survives a genuine client-side (router-region) navigation to another page and back, for the lifetime of the continuous session. Store modules persist across the navigation; the incoming page's server state merges non-destructively and never carries drafts; a returning surface re-declares its same key and its drafts re-attach. Surfaces wrapped in no container share the one global collection across pages, so product A's unwrapped form on page B shows the edit made on page A.
-   They **reset on a hard reload.** All client state reinitializes; `state.draftItems` starts empty and seeds re-derive fresh. Drafts are client-side only — there is no persistence layer.

There is **no lifecycle machinery** behind this — no ledger, no restore protocol, no per-surface reconstruction. Survival is what the model yields on its own: global state that outlives region swaps and navigations, plus render-stable keys that re-address the same collection wherever a surface re-renders. Post-navigation display on every surface is exactly this generic yield. In particular, a **remounted variable-product card presents unconfigured attributes** with whatever quantity the surviving draft holds: attribute-selection UI state lives in products-namespace context that the remount discards, and no variation is re-resolved (that would be display-reconstruction machinery the model deliberately omits). Display and posting stay in agreement.

> **History.** An earlier iteration of this store stored drafts in context-held collections and kept a remounted Product Collection card's draft alive with Product-Collection-specific machinery: a module-private ledger keyed by a derived card identity, a register-or-restore init directive on every card, a render-time bridge in the resolver, a per-card `data-wp-init`, `seedDraftIfAbsent` with its `data-wp-context---draft-seed` bags, and the empty-collection `data-wp-context---draft-items` bags. That entire apparatus — and the `removeDraftItem` action alongside it — was deleted with no successor when drafts moved into keyed global state. None of it exists in the shipped store; it is recorded here only so readers migrating from that model know it is gone.

#### Residuals worth knowing

These are accepted, shipped behaviors of the current validation-grade surface:

-   **The extension seed contract.** Declaring only the `draftKey` context bag gives an extension correct **client-side** addressing — its subtree resolves its collection, direct mutation and `upsertDraftItem` work, and the extension can read `state.draftItems[ <its own key> ]`. But **wrapping a core seed-emitting surface additionally requires propagating the key through `render_block_context`** so that surface files its seed under the extension's key. Without that, an untouched wrapped surface has no seed under the resolved key and posts nothing — a safe no-data outcome, never wrong data.
-   **Cross-page Single Product instance collisions.** Two Single Product blocks for the same product on two different pages each mint `single-product/<productId>/1`, so under client-side navigation they share one collection. Within any single page, isolation is fully preserved (the occurrence counter distinguishes the instances); the collision is observable only across a client-side page navigation.
-   **Same-`(key, id)` seed filing is last-write-wins.** When one product is filed twice under one key (e.g. standalone and as a grouped child, both unwrapped on one page), the later filing wins — the same order-dependent ambiguity the single shared collection already carried.
-   **Hand-authored collections without a `queryId`** all mint under `collection/0/<productId>` and can therefore share drafts per product across two such collections. Enhanced pagination requires authored `queryId`s, so this affects only hand-rolled markup.
-   **Duplicate-id drafts are possible via direct `push`.** Now that direct mutation is first-class, appending a second draft with an existing `id` straight onto a collection bypasses the one-draft-per-`id` invariant (lookups then resolve first-match). This is bounded — no shipped consumer appends directly; creation flows through `upsertDraftItem`, which maintains the invariant.

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

The store is **not a public API** while the keyed draft model is being validated. Its members can change or disappear without notice, and removing or changing state here is not a breaking change. Do not extend it from third-party code.

Unlike the products store, the cart store has **no consent-gated PHP surface** — its server side is plain container-key and seed markup, so `universalLock` (JS, for the store lock) is the only consent string it involves. Copy it from this README or the source files rather than retyping.

### Patterns and pitfalls

-   **Read through the envelope; write the draft it hands you.** Bind display to `state.itemInContext` / `state.inCartQuantity`, and record input either by mutating the resolved draft directly (`itemInContext.draft.quantity = value`) or through `upsertDraftItem`. Never write a draft from a callback that a shopper did not trigger.
-   **Handle `cartItem === undefined`.** The pairing ladder returns `undefined` whenever it cannot identify exactly one line. Treat that as "no known line", not "not in cart".
-   **Let containers own isolation.** A consumer block resolves the nearest key automatically; it never reads, writes, or declares a key itself. Declaring a `draftKey` in `woocommerce/cart` context is the job of the container that wraps or repeats purchase UI — a core Product Collection card or Single Product block, or an extension's own container.
-   **Drafts survive client navigation, not a reload.** Draft edits persist across Interactivity-API region updates (such as collection pagination) and cross-page client-side navigations, but are discarded on a hard reload. Persisting only for the session is by design, not a gap.
-   **Do not extend this store from third-party code.** It is `lock: true` and private by design.
