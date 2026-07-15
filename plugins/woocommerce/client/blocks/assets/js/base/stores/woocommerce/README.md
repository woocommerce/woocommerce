# WooCommerce Interactivity API stores

This folder contains the Interactivity API (iAPI) stores that WooCommerce blocks use on the frontend. All stores here are **locked** (`lock: true`) and private by design — they are not intended for third-party extension, and removing or changing their state is **not** a breaking change. See the "Interactivity API Stores" section in `client/blocks/CLAUDE.md` and the [WordPress Private Stores reference](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference#private-stores).

Stores in this folder:

-   [`woocommerce/products`](#woocommerceproducts-store) — server-populated cache of product and variation data in Store API format.
-   [`woocommerce/cart`](#woocommercecart-store) — the read-only cart mirror plus scope-keyed draft cart items that back purchase UI (add-to-cart forms, buttons, the mini-cart), with mutation batching for performance.
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

Shopper input is modeled as **draft cart items** organized by **scope**. A draft is literally a Store API `cart/add-item` request payload — there is no mapping layer between what a form collects and what gets posted. Scope is how the store keeps the same product's input independent across the many places a purchase form can appear on one page (a product template's main form, a sticky add-to-cart bar, a grouped child, a Product Collection card, a bundle slot). Surfaces that share a scope share drafts and stay in sync; surfaces in different scopes are fully independent.

**Source files:**

-   JS: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/cart.ts`
-   PHP scope service: `plugins/woocommerce/src/Blocks/SharedStores/CartStore.php`
-   Scope establishers: `plugins/woocommerce/src/Blocks/BlockTypes/ProductTemplate.php` (Product Collection loop items), `plugins/woocommerce/src/Blocks/BlockTypes/SingleProduct.php` (Single Product block)
-   Draft seeding: `plugins/woocommerce/src/Blocks/BlockTypes/AddToCartWithOptions/Utils.php`
-   Behavioral tests: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/test/cart.ts`

### When to use it

Use this store when an interactive block needs to record shopper purchase input before an add-to-cart (a quantity, a variation selection, a namespaced extension field), read the live/optimistic cart, or add, update, or remove cart lines. Because reads and writes resolve through the in-context scope and product, the same block code works whether it is rendered as a single product's main form, a Product Collection card, a mini-cart row, or a page-wide sticky bar.

### Architecture at a glance

```text
Two planes of state:

  state.cart          read-only mirror of the Store API /cart response
                      (with optimistic edits); the server owns line identity.

  state.draftItems    editable, client-only, keyed by scope:
                      Record< Scope, DraftItem[] >, at most one draft per
                      product id per scope. A DraftItem IS a cart/add-item
                      payload. Lives alongside — never inside — state.cart.

Scope resolution:

  state.pageScope     server-seeded once per request (page/<queried-object-id>).
  context.scope       emitted by a scope-overriding container for its subtree.
  state.currentScope  = context.scope ?? pageScope   (the one place this rule lives)

Reads (never guess a line):        Writes (actions only):

  state.itemInContext  ─┐            actions.upsertDraftItem( partial, { scope? } )
  state.findItem(…)     ├─▶ Envelope   actions.removeDraftItem( { id, scope? } )
  state.inCartQuantity ─┘  { cartItem?, draft? }
                                     actions.addItem( payload? )  ── posts drafts
                                     actions.updateItem / removeItem / refresh
```

### The scoped-draft model

`state.draftItems` is `Record< Scope, DraftItem[] >`: an object keyed by scope, each scope holding an array of drafts. A `DraftItem` is exactly a `cart/add-item` payload:

```ts
type DraftItem = {
    id: number; // product or variation id; also the per-scope uniqueness key
    quantity: number;
    variation?: SelectedAttributes[]; // for a variation draft
    [ extensionProp: string ]: unknown; // namespaced, e.g. 'my-plugin/gift-note'
};
```

Extension props ride at the payload root, namespaced (a key containing a `/`), exactly as the Store API accepts them — there is no separate wrapper for them. This is the read model's mirror image of `CartItem.extensions`, which reflects the server's response once a line exists.

A scope holds **at most one draft per product `id`**: `upsertDraftItem` merges into the draft whose `id` matches and appends otherwise. When a surface genuinely needs two independent drafts of the same product (two bundle slots offering the same child), it establishes two sub-scopes rather than reaching for a second key concept — one draft per product per scope is the invariant, and scope is the only addressing axis.

Drafts are **client-only reactive state the server never mirrors into `state.cart`**. This is what makes client-side (router-region) navigation draft-safe: the cart mirror reconciles with the server, while a shopper's in-progress draft edits live in a plane the server never overwrites.

### The scope model

A `Scope` is an **opaque, deterministic, namespaced string**. The only property the store guarantees is that equal strings denote the same scope; no code parses a scope's internals. Determinism matters: because a scope id derives from stable structural identity rather than a per-render random value, a router-region re-render of the same page reproduces the same scope and never orphans a live draft.

Two pieces of state resolve "which scope am I in":

-   `state.pageScope` — the page-wide scope, **server-seeded once per request** via `wp_interactivity_state( 'woocommerce/cart', … )`. It is deliberately given no client-side initial value: the store definition deep-merges over the server-provided state during registration, so a client default would overwrite the seeded scope. On a page where the server seeds no cart state, `pageScope` stays `undefined` and `currentScope` degrades to an (invalid) empty scope.
-   `state.currentScope` — the scope to read from or write to when a consumer passes none. It resolves `context.scope ?? pageScope`: the `scope` carried in the shared `woocommerce` context namespace (set by a scope-overriding container for its subtree) when present, else `pageScope`. **This getter is the single place that implements `context.scope ?? pageScope`** — no other code, client or server, re-implements that conditional. Reading it outside a directive's execution (no active Interactivity scope) degrades to `pageScope` rather than throwing.

The scope ids actually minted on the branch, all deterministic and namespaced:

| Scope id shape                     | Minted by                              | Establishes                              |
| ---------------------------------- | -------------------------------------- | ---------------------------------------- |
| `page/<queried-object-id>`         | `CartStore.php`                        | The page-wide scope (the floor).         |
| `collection/<queryId>/<productId>` | `ProductTemplate.php`                  | Each Product Collection loop item.       |
| `single-product/<productId>/<n>`   | `SingleProduct.php`                    | A Single Product block (`<n>` = per-render occurrence counter, so two blocks for the same product get distinct scopes). |
| `<plugin>/<anything>`              | Extensions (e.g. `wc-bundle-demo/…`)   | Any surface an extension isolates, including bundle sub-scopes. |

**Consumer blocks never set scope.** They read it from context through `currentScope` (or pass an explicit `scope` only when deliberately targeting another one, as a bundle composing sub-scopes does). Establishing scope is the job of the container that wraps or repeats purchase UI.

### Reading: the in-context envelope

Reads return an `Envelope`, which pairs a scope's draft with the matching cart line:

```ts
type Envelope = {
    cartItem?: CartItem | OptimisticCartItem | undefined;
    draft?: DraftItem | undefined;
};
```

There is no `isInCart` (or any other) member — no consumer needed the "in the cart, but no single identifiable line" tri-state, so the envelope carries only `cartItem` and `draft`.

-   `state.itemInContext` — the envelope for the product the surrounding markup implies, resolved through the `woocommerce/products` store's `productInContext`. Its `currentScope` draft is paired with its cart line. When no product is in context (nothing rendered), it returns an empty envelope.
-   `state.findItem( { scope?, id?, key?, filter? } )` — the explicit primitive behind `itemInContext`, for looking up a specific draft/line pair.
-   `state.inCartQuantity` — the in-context product's in-cart quantity. A grouped product aggregates its children's own in-cart quantities (each resolved independently, by id); a variable product resolves through its currently selected variation (the same resolution as `itemInContext`); a simple product is its own paired line's quantity. It is `0` when nothing pairs or no product is in context.

**Pairing never guesses.** `cartItem` is populated only when the pairing ladder resolves to exactly one candidate line:

1.  **A context-known line `key`** pairs exactly, with no further checks — the caller already knows precisely which line this is (a mini-cart row, or a surface that emits `key` in the shared `woocommerce` context).
2.  Otherwise, **product/variation identity** (using the resolved draft's own `variation`, when one exists) **plus a namespaced extension-prop comparison** against each candidate line's `extensions[<namespace>]` must resolve to exactly one line. A `filter` argument replaces this identity matching entirely, for extensions with their own notion of line identity.

Any remaining ambiguity — zero lines, or more than one line that cannot be told apart — leaves **`cartItem` `undefined`**. The server owns cart-line identity, so the client never guesses at it. Consumers must handle `cartItem === undefined`.

### Writing: actions only

Draft state is written **exclusively through actions** — the envelope's `draft` is read-only by convention, and no block mutates `state.draftItems` directly. Two actions are the entire write path:

-   `actions.upsertDraftItem( partial, { scope?, id? }? )` — creates or updates a draft. It resolves the target scope (defaulting to `currentScope`), then merges `partial` into the scope's draft whose `id` matches — `id` resolved from `options.id` when given, else `partial.id` — appending a new draft otherwise. The scope's bucket is created on first write. It **rejects, leaving state unchanged**, when: the resolved scope is not a valid non-empty string; no numeric target `id` can be resolved; `partial.id` disagrees with an already-resolved target `id` (an in-place identity change — remove the draft and add a new one instead); or a brand-new draft is created without a numeric `quantity`. Each rejection is a dev-build `console.warn` and a silent no-op in production.
-   `actions.removeDraftItem( { id?, scope? }? )` — removes a scope's draft for the given product and prunes the scope's bucket once it is empty. It rejects (state unchanged) on an invalid scope or a non-numeric `id`; naming a product/scope with no matching draft is a silent no-op.

**Bystander discipline.** Because watch and init callbacks re-run on every surface that shares a scope, only a genuine shopper edit — or a clamp of the shared value itself — may write to a scope's draft. A sibling surface that the shopper never touched must not write its stale local default back over the shared draft (for example, a never-selected variation surface must not overwrite "nothing selected" onto a variation another surface resolved). When building a new surface that shares a scope, gate every draft write behind an actual user action.

### Adding to cart: `addItem`

`actions.addItem( payload? )` is polymorphic:

-   **`addItem()`** (no argument) resolves the in-context product via `woocommerce/products` and posts `currentScope`'s draft(s) for it: a simple or variable product's own single draft (`itemInContext.draft`), or, for a grouped product, every child's draft (children resolved one-directionally through the products store) whose `quantity` is greater than `0`. Multiple children are posted as one auto-batched request set, not one request per child. It never posts another scope's or another product's draft, and sends nothing when the resolution yields no draft.
-   **`addItem( payload )`** posts the payload verbatim — extension props at its root included — bypassing scope and product resolution entirely. This is the path an extension composing its own `cart/add-item` payload (a bundle carrying `wc-bundle-demo/children`, say) uses.

Every posted item optimistically bumps a matching existing cart line's quantity in place (unless `sold_individually`) or is pushed as a new line, commits or rolls back through the mutation queue, and fires the legacy added-to-cart event once per call on success. A cycle whose requests all fail rolls the cart back to its pre-cycle snapshot and surfaces a `woocommerce/store-notices` notice.

> **Caveat — notice narrowing.** Form and button adds no longer pass a notice-suppression flag. An exact add stays notice-silent (the store proves the server total equals the pre-add total plus the posted delta and suppresses those lines), but a genuinely divergent server commit — a stock cap or a concurrent change — now surfaces a "quantity changed" notice where the previous code path was silent. This is a deliberate narrowing, not a regression in correctness.

The store also exposes the retained cart-line actions:

-   `actions.updateItem( { key, quantity } )` — sets a cart line's quantity to an absolute value via `update-item`; a no-op when no line matches `key`.
-   `actions.removeItem( key )` — removes a cart line by key.
-   `actions.refresh()` — re-fetches the server cart, bypassing the browser cache.
-   `actions.addCartItem( args, options? )` — the lower-level keyed/keyless add-or-update path, retained permanently because the out-of-scope shopper-lists blocks consume it. New purchase UI should prefer `addItem` / `updateItem`.

### The server half

Server-side rendering seeds both scope and initial drafts, so every visible value is correct in the initial HTML before hydration.

#### The scope service

`SharedStores/CartStore.php` mints and tracks scopes. Its static methods are consent-gated with the experimental-API statement:

```php
'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce'
```

-   `CartStore::mint_page_scope( $consent )` — mints `page/<queried-object-id>` once per request and seeds it into `woocommerce/cart` state via `wp_interactivity_state`. Memoized: repeated calls return the same value without re-seeding.
-   `CartStore::push_scope( $consent, $scope )` / `CartStore::pop_scope( $consent )` — a render-time scope stack. A container that overrides scope pushes its scope before rendering its inner blocks and pops it afterward. The page scope is the floor and is not on the stack, so `pop_scope()` is a no-op once the stack is empty.
-   `CartStore::get_current_scope( $consent )` — the innermost pushed scope, or the page scope when nothing is pushed. It is the PHP symmetric of the client `state.currentScope` getter, so purchase-UI PHP nested inside a container resolves the same scope the client will.

#### The two core establishers

`ProductTemplate.php` (Product Collection loop items) and `SingleProduct.php` (Single Product block) each mint their scope, push it around their inner-block render, and emit it as context. `SingleProduct` mints in `update_context()` and pops on the `render_block_woocommerce/single-product` filter once the block and its inner blocks have rendered.

Both emit the scope through a **three-hyphen context bag**, `data-wp-context---scope`, in the shared `woocommerce` namespace:

```html
data-wp-context---scope='woocommerce::{"scope":"collection/1/123"}'
```

The three-hyphen form exists because `wp_interactivity_data_wp_context()` always emits an attribute literally named `data-wp-context`; an element that already carries a default context bag (here, the `woocommerce/products` product context) cannot carry a second one under the same attribute name — the HTML parser would keep the first and silently drop the second. `data-wp-context---<suffix>` is the supported way to add a second, namespaced context bag on one element (the same pattern the shopper-lists blocks use for `data-wp-context---notices`).

#### Initialize-if-absent draft seeding

A purchase surface seeds its initial draft the same way. `AddToCartWithOptions/Utils.php` emits the initial `cart/add-item` payload as a `woocommerce/cart` draft-seed context bag and wires an init directive:

```html
data-wp-context---draft-seed='woocommerce/cart::{"draftSeed":{"id":123,"quantity":1}}'
data-wp-init--seed-draft='woocommerce/cart::actions.seedDraftIfAbsent'
```

`actions.seedDraftIfAbsent()` reads the **server-rendered** context — `getServerContext< { draftSeed?: DraftItem } >( 'woocommerce/cart' )?.draftSeed`, immune to the reactive proxy's client-side edits — resolves `currentScope`, and copies the seed into that scope's bucket **only when the scope holds no draft for the seed's product `id`**. This initialize-if-absent rule is what lets a router-region re-render's seed read run harmlessly: it can never clobber a shopper's in-progress edit, because a present draft is left untouched.

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

// Adding the in-context product's current-scope draft(s) to the cart:
await actions.addItem();
```

The store is **not a public API** while the scoped-draft model is being validated. Its members can change or disappear without notice, and removing or changing state here is not a breaking change. Do not extend it from third-party code.

As with the products store, the two consent strings differ by role and must be kept in sync with the source: `CartStore::$consent_statement` (PHP, for the scope service) and `universalLock` (JS, for the store lock). Copy them from this README or the source files rather than retyping.

### Patterns and pitfalls

-   **Read through the envelope; write through the actions.** Bind display to `state.itemInContext` / `state.inCartQuantity` and record input with `upsertDraftItem` / `removeDraftItem`. Never mutate `state.draftItems` directly, and never write a draft from a callback that a shopper did not trigger.
-   **Handle `cartItem === undefined`.** The pairing ladder returns `undefined` whenever it cannot identify exactly one line. Treat that as "no known line", not "not in cart".
-   **Let containers own scope.** A consumer block reads `currentScope`; it does not set `pageScope` or emit a `scope` context. Pass an explicit `scope` only when deliberately targeting another one (e.g. bundle sub-scopes).
-   **Drafts survive client navigation, not a reload.** Draft edits persist across Interactivity-API region updates (such as collection pagination) but are discarded on a full page load. Session-scoped persistence is by design, not a gap.
-   **Remounted cards can present as unconfigured.** After an enhanced-pagination remount, a Product Collection card's variation resolution — which lives in client context the remount discards — can present as unconfigured even though the draft still persists in the ledger. Display and action stay in agreement, and the behavior matches the pre-change branch; re-associating scope drafts with product context at mount is future work.
-   **Do not extend this store from third-party code.** It is `lock: true` and private by design.
