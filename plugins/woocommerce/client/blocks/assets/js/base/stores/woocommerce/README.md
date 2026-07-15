# WooCommerce Interactivity API stores

This folder contains the Interactivity API (iAPI) stores that WooCommerce blocks use on the frontend. All stores here are **locked** (`lock: true`) and private by design — they are not intended for third-party extension, and removing or changing their state is **not** a breaking change. See the "Interactivity API Stores" section in `client/blocks/CLAUDE.md` and the [WordPress Private Stores reference](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference#private-stores).

Stores in this folder:

-   [`woocommerce/products`](#woocommerceproducts-store) — server-populated cache of product and variation data in Store API format.
-   [`woocommerce/cart`](#woocommercecart-store) — cart state and actions (with mutation batching for performance). `addCartItem` resolves with a structured per-call outcome (`AddCartItemOutcome`) instead of `void` — see below.

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
     'woocommerce/products',                        │ • state.mainProductInContext      │
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
| `mainProductInContext`      | `ProductResponseItem \| null`                                 | Derived                   | The top-level product for the current context. Always the parent product, **never** a variation.                                                                                                                              |
| `productVariationInContext` | `ProductResponseItem \| null`                                 | Derived                   | Currently selected variation, or `null` for simple/grouped/non-selected.                                                                                                                                                      |
| `productInContext`          | `ProductResponseItem \| null`                                 | Derived                   | `productVariationInContext ?? mainProductInContext`. Bind to this in the common case.                                                                                                                                         |
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

Any `ProductResponseItem` field can be bound the same way, e.g. `state.productInContext.price_html`, `state.productInContext.stock_availability.text`, `state.mainProductInContext.name`.

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

-   **Always load before you bind.** If `wc_interactivity_api_load_product` was never called for the current `productId`, `state.mainProductInContext` resolves to `null` and directive bindings silently render empty.
-   **Prefer `productInContext`** for "whatever is currently being shown". Use `mainProductInContext` / `productVariationInContext` only when the distinction matters (e.g. rendering a variation-specific description vs. the parent title).
-   **`data-wp-context` sets local context.** Use it whenever the same block type can appear multiple times on a page for different products.
-   **Local context beats state.** If a block is wrapped in a `data-wp-context="woocommerce/products::{ ... }"` element, its `productId` / `variationId` override any globally-set values for descendants of that element. See `test/products.test.ts` for the exact precedence rules — notably, a context that has `productId` but no `variationId` key does **not** fall back to the global `variationId`.
-   **Keep the consent string in sync.** The literal string is defined in `ProductsStore::$consent_statement` (PHP) and `universalLock` (JS). They are intentionally different (loaders vs. store lock); copy-paste from this README or the source files.
-   **Do not extend this store from third-party code.** It is `lock: true` and private by design; anything here can change or disappear without notice.

---

## `woocommerce/cart` store

The `woocommerce` Interactivity API store (`base/stores/woocommerce/cart.ts`) holds cart state and actions, with mutation batching for performance. This section only covers the `addCartItem` outcome contract below — it is not a write-up of the store's full state shape, its other actions, or the mutation batcher's internals.

### The `addCartItem` outcome contract

`addCartItem` returns `Promise< AddCartItemOutcome >` — a structured per-call outcome — instead of `Promise< void >`:

```ts
export type AddCartItemError = {
	/** Server error code for a per-item rejection (e.g. `woocommerce_rest_product_out_of_stock`),
	 *  or the batcher's `unknown_error` fallback. Absent on whole-batch/transport failures. */
	code?: string;
	/** Human-readable failure description. Always present. */
	message: string;
};

export type AddCartItemOutcome =
	| { success: true }
	| { success: false; error: AddCartItemError };
```

Both types are exported from the cart store module and reach consumers through the existing type-only import path:

```ts
import type { AddCartItemOutcome } from '@woocommerce/stores/woocommerce/cart';
```

**`addCartItem` stays fire-and-forget.** It never rejects because of a request or server failure — every request path, whether the Store API accepts or rejects it, resolves with an outcome instead of throwing. The action still throws synchronously for its two programmer-error guards (both `quantity` and `quantityToAdd` supplied together, or a keyless call — no `key` — that passes an absolute `quantity` instead of a `quantityToAdd` delta) — those are argument-validation bugs, unrelated to the request outcome below.

Read the outcome from a consuming generator:

```ts
const outcome = ( yield cartActions.addCartItem( {
	id,
	quantityToAdd,
} ) ) as AddCartItemOutcome;

if ( ! outcome.success ) {
	// outcome.error.code   — may be absent (e.g. transport/whole-batch failures)
	// outcome.error.message — always present
	return;
}
```

**`success: true` means the Store API accepted the request — nothing more needs checking.** That includes a brand-new standalone cart line (e.g. a bundle/booking/add-on "meta" line) as much as an incremented existing line, and a silently server-normalized quantity (capped to a maximum, bumped to a minimum/multiple, or forced to one for a sold-individually product) as much as an exact one. A caller never needs to inspect or diff cart lines to interpret the outcome.

**Attribution is per-call, never per-batch.** When several `addCartItem` calls are dispatched within the same tick and coalesced into one `wc/store/v1/batch` request, or issued one after another in a loop, each call's outcome reflects only that call's own product result — never a shared or last-write-wins value from a sibling call, even when calls in the same batch land different outcomes (one product accepted, another rejected).

**Both call shapes share the same request path.** A keyless call (no `key`, posts to `add-item`) and a keyed call (`key` supplied, posts to `update-item`) both resolve through the same single request and outcome capture — there is no separate contract for either shape.

**`removeCartItem` and `batchAddCartItems` do not share this contract.** Both remain typed `Promise<void>` and swallow their own errors the way `addCartItem` used to. Don't assume parity when touching either of them — extending this contract to other cart actions is a separate decision, not something the store does today.

For a worked example, see `onClickMoveToCart` in `saved-for-later/frontend.ts` and `onClickAddToCart` in `wishlist/frontend.ts` — both read the outcome and gate a destructive list-removal on `outcome.success`.
