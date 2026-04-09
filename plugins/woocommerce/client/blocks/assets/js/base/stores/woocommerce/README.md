# WooCommerce Interactivity API stores

This folder contains the Interactivity API (iAPI) stores that WooCommerce blocks use on the frontend. All stores here are **locked** (`lock: true`) and private by design — they are not intended for third-party extension, and removing or changing their state is **not** a breaking change. See the "Interactivity API Stores" section in `client/blocks/CLAUDE.md` and the [WordPress Private Stores reference](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference#private-stores).

Stores in this folder:

- [`woocommerce/products`](#woocommerceproducts-store) — server-hydrated cache of product and variation data in Store API format.
- `woocommerce/cart` — cart state and actions (with mutation batching for performance).
- `woocommerce/product-data` — per-block product data used by legacy product blocks.

---

## `woocommerce/products` store

A locked, server-hydrated iAPI store that exposes WooCommerce products and variations in Store API format (`ProductResponseItem`) to interactive blocks. PHP loaders populate the raw data during render; JS and PHP derived getters expose the "current" product for the surrounding context so that directives like `data-wp-text="state.productInContext.sku"` resolve correctly on both the server (SSR) and the client.

**Source files:**

- JS: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/products.ts`
- PHP: `plugins/woocommerce/src/Blocks/SharedStores/ProductsStore.php`
- PHP procedural wrappers: `plugins/woocommerce/includes/wc-interactivity-api-functions.php`
- Behavioral tests: `plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/test/products.test.ts`

### When to use it

Use this store when an interactive block needs to read product fields (price, SKU, stock, images, attributes, …) inside a directive, and the surrounding markup implies a single "current" product — a single product page, a product in a product-collection loop, a grouped-product child, a variation inside a variable product, etc.

### Architecture at a glance

```text
PHP                                                  Client
┌───────────────────────────────────┐               ┌────────────────────────────┐
│ ProductsStore::load_product()     │               │ store<ProductsStore>(      │
│ ProductsStore::load_variations()  │   hydrates    │   'woocommerce/products'   │
│ ProductsStore::load_purchasable_  │──────────────▶│ )                          │
│   child_products()                │               │                            │
└────────────┬──────────────────────┘               │ state.products             │
             │                                      │ state.productVariations    │
             ▼                                      │                            │
   wp_interactivity_state(                          │ Derived getters:           │
     'woocommerce/products',                        │ • state.product            │
     [ 'products' => ..., ... ]                     │ • state.selectedVariation  │
   )                                                │ • state.productInContext   │
                                                    └────────────┬───────────────┘
                                                                 │
 Selection (one of):                                             ▼
 • Global: wp_interactivity_state(..., [        Directives bound in markup:
     'productId' => N, 'variationId' => null   data-wp-interactive="woocommerce/products"
   ])                                          data-wp-text="state.productInContext.sku"
 • Per-element: data-wp-context=
     'woocommerce/products::{"productId":N}'
```

Two planes:

1. **Raw data** — `state.products` and `state.productVariations`, both keyed by ID. Populated from PHP.
2. **Selection** — `state.productId` / `state.variationId` identify the "current" product/variation. Can be set globally via `wp_interactivity_state`, or per-element via `data-wp-context`. **Per-element context takes precedence over global state.**

Derived getters mirror each other in JS (`products.ts`) and PHP (`ProductsStore::register_getters`) so that directive bindings resolve during SSR as well as on the client.

### State reference

| Property | Type | Origin | Notes |
| --- | --- | --- | --- |
| `products` | `Record<number, ProductResponseItem>` | Hydrated from PHP | Keyed by product ID. |
| `productVariations` | `Record<number, ProductResponseItem>` | Hydrated from PHP | Keyed by variation ID. |
| `productId` | `number` | Hydrated / per-element context | Current product ID. |
| `variationId` | `number \| null` | Hydrated / per-element context | Current variation ID, or `null`. |
| `product` | `ProductResponseItem \| null` | Derived | The top-level product for the current context. Always the parent product, **never** a variation. |
| `selectedVariation` | `ProductResponseItem \| null` | Derived | Currently selected variation, or `null` for simple/grouped/non-selected. |
| `productInContext` | `ProductResponseItem \| null` | Derived | `selectedVariation ?? product`. Bind to this in the common case. |
| `findProductVariation` | `({ id, selectedAttributes }) => ProductResponseItem \| null` | Function | Resolves a variable product + attributes to a concrete variation. |

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

There are two ways to point the store at a specific product. **Per-element context always wins over global state.** Choose based on how the consuming block is rendered.

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

#### Per-element (block-level)

Set `productId` / `variationId` on a wrapper element via `data-wp-context`. Use this whenever the same block type can appear multiple times on a page for different products (product loops, grouped product children, variations).

From `SingleProduct.php`:

```php
wc_interactivity_api_load_product(
    'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
    $product->get_id()
);

$interactivity_context = array(
    'productId'   => $product->get_id(),
    'variationId' => null,
);

$html = new \WP_HTML_Tag_Processor( $content );
if ( $html->next_tag( array( 'tag_name' => 'div' ) ) ) {
    $html->set_attribute( 'data-wp-interactive', $this->get_full_block_name() );
    $html->set_attribute(
        'data-wp-context',
        'woocommerce/products::' . wp_json_encode(
            $interactivity_context,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        )
    );
}
```

The `woocommerce/products::` prefix on `data-wp-context` is what binds that context object to the `woocommerce/products` store; the JS store's `getContext< ProductContext >( 'woocommerce/products' )` calls read from it.

### Reading product data in a block

Once state is populated and a current product is set, blocks read from it either through directives (SSR + client) or through a JS store reference.

#### From PHP / directives (SSR)

The derived getters are registered on the PHP side via `ProductsStore::register_getters()`, so bindings resolve during server render — no client hydration flash.

From `ProductSKU.php`:

```php
$interactive_attributes = $is_interactive
    ? 'data-wp-interactive="woocommerce/products" data-wp-text="state.productInContext.sku"'
    : '';
```

Any `ProductResponseItem` field can be bound the same way, e.g. `state.productInContext.price_html`, `state.productInContext.stock_availability.text`, `state.product.name`.

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

Use `state.findProductVariation({ id, selectedAttributes })` when you have a variable product ID and a set of selected attributes and want the concrete variation.

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

For variable products, `findProductVariation` returns `null` when no variation matches; for simple products it returns the product itself.

### Patterns and pitfalls

- **Always load before you bind.** If `wc_interactivity_api_load_product` was never called for the current `productId`, `state.product` resolves to `null` and directive bindings silently render empty.
- **Prefer `productInContext`** for "whatever is currently being shown". Use `product` / `selectedVariation` only when the distinction matters (e.g. rendering a variation-specific description vs. the parent title).
- **`data-wp-context` is per-element.** Use it whenever the same block type can appear multiple times on a page for different products.
- **Context beats state.** If a block is wrapped in a `data-wp-context="woocommerce/products::{ ... }"` element, its `productId` / `variationId` override any globally-set values for descendants of that element. See `test/products.test.ts` for the exact precedence rules — notably, a context that has `productId` but no `variationId` key does **not** fall back to the global `variationId`.
- **Keep the consent string in sync.** The literal string is defined in `ProductsStore::$consent_statement` (PHP) and `universalLock` (JS). They are intentionally different (loaders vs. store lock); copy-paste from this README or the source files.
- **Do not extend this store from third-party code.** It is `lock: true` and private by design; anything here can change or disappear without notice.
