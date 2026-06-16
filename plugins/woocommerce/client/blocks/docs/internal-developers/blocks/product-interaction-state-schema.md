# Proposal: Product interaction state in the `woocommerce` iAPI store

## Status

Draft for async discussion. Agree on schema before implementation.

## Problem

Add to Cart + Options currently owns quantity, selected attributes, and validation errors in local context. Blocks outside that boundary cannot reliably share those values, which forces parent-specific block behavior.

Use cases this should unlock:

-   Quantity in Product Collection.
-   Swatches in Product Collection.
-   Sticky or repeated purchase UI on single product pages.
-   Wishlist reading the selected variation outside Add to Cart + Options.

## Proposal

Use the existing locked/private `woocommerce` iAPI store for:

-   Product data: `products`, `productVariations`, and product selectors.
-   Product interaction state: shopper input for the current product context.
-   Cart data: committed or optimistic cart state.

Expose one interaction selector:

```typescript
state.productInteractionInContext;
```

Do not expose separate public objects for variation selection, quantity, validation, or shopper input. Those fields belong inside `productInteractionInContext`.

## Naming

Use `productInteractionInContext` as the working name.

Key distinction:

-   `productInContext`: resolved Store API product/variation data.
-   `productInteractionInContext`: shopper input for the current product context.
-   `cartItemInContext`: matching cart item, if any.

Rejected or weaker names:

-   `addToCartInContext`: too tied to cart submission.
-   `productConfigurationInContext`: good for attributes/add-ons, weaker for quantity/errors.
-   `cartItemDraftInContext`: wrong for grouped products, bundles, and composite payloads.
-   `productSelectionInContext`: quantity and validation errors are not just selection.
-   `currentProduct`: sounds global and hides context resolution.

## Store shape

Illustrative TypeScript only:

```typescript
type WooCommerceStoreState = {
	cart: CartState;

	products: Record< number, ProductResponseItem >;
	productVariations: Record< number, ProductResponseItem >;
	productInteractions: Record<
		number,
		Omit< ProductInteraction, 'quantityToAdd' >
	>;

	mainProductInContext: ProductResponseItem | null;
	productVariationInContext: ProductResponseItem | null;
	productInContext: ProductResponseItem | null;

	productInteractionInContext: ProductInteraction | null;
	cartItemInContext: CartItem | OptimisticCartItem | undefined;

	findProduct: ( args: {
		id: number;
		selectedAttributes?: SelectedAttribute[] | null;
	} ) => ProductResponseItem | null;

	findItemInCart: ( args: {
		id: number;
		key?: string;
		variation?: SelectedAttribute[];
	} ) => CartItem | OptimisticCartItem | undefined;
};
```

## Context model

Use the current `woocommerce` context to know which product the block is operating on.

```html
<div data-wp-context='woocommerce::{"productId":123}'>
	<!-- Descendant blocks use product 123. -->
</div>
```

`productInteractions` stores interaction state keyed by the current context's `productId`.

`productInteractionInContext` resolves `state.productInteractions[ context.productId ]`, not `state.productInteractions[ productInContext.id ]`. This matters for variable products: `productInContext` may be the selected variation, while the interaction still belongs to the parent product context.

## Product interaction shape

```typescript
type ProductInteraction = {
	productId: number;
	selectedAttributes: SelectedAttribute[];
	quantities: Record< number, number >;
	quantityToAdd: number;
	validationErrors: ProductInteractionError[];
	extensions?: Record< string, unknown >;
};

type ProductInteractionError = {
	code: string;
	group: string;
	message: string;
};
```

`quantityToAdd` is added by the `productInteractionInContext` getter. It is derived from `productInteractionInContext.quantities[ productInContext.id ]` and is not stored in `productInteractions`.

This gives consumers a stable binding target:

```html
<input data-wp-bind--value="state.productInteractionInContext.quantityToAdd" />
```

Notes:

-   `productId` is the main product for the current context and is used to find `productInteractionInContext`.
-   `selectedAttributes` belongs to the main/parent product in the current context and replaces the current Add to Cart + Options local selected attributes context.
-   `productVariationInContext` derives from `productId + selectedAttributes`.
-   `productInContext` remains product data: `productVariationInContext ?? mainProductInContext`.
-   `quantities` is storage keyed by purchasable product id: simple product id, selected variation id, or grouped child product id.
-   `quantityToAdd` is the consumer-facing quantity for the current `productInContext`.
-   `extensions` is reserved for future add-ons/bundles/composite payloads; not a public API yet.

## Selected attribute shape

Wishlist needs the taxonomy/raw slug, not only label and value.

```typescript
type SelectedAttribute = {
	attribute: string;
	value: string;
	raw_attribute?: string;
};
```

Open question: should `raw_attribute` be required, and should this reuse Store API `CartVariationItem` exactly?

## Variation resolution

Current flow:

```text
local selectedAttributes → findProduct() → variationId → productVariationInContext → productInContext
```

Proposed flow:

```text
main product id + productInteractionInContext.selectedAttributes → productVariationInContext → productInContext
```

This keeps selected attributes in shared store state while keeping `productInContext` read-oriented product data.

## Actions

The store only needs actions for mutating `productInteractionInContext`. Cart submission can keep using existing cart actions after reading product and interaction state.

Proposed minimum write surface:

```typescript
type WooCommerceActions = {
	setProductInteractionQuantity: ( args: {
		id: number; // Product, variation, or grouped child product ID.
		quantity: number;
	} ) => void;

	setProductInteractionAttribute: ( args: {
		attribute: SelectedAttribute;
	} ) => void;

	removeProductInteractionAttribute: ( args: { attribute: string } ) => void;

	addProductInteractionError: ( args: {
		error: ProductInteractionError;
	} ) => void;

	clearProductInteractionErrors: ( args?: { group?: string } ) => void;
};
```

Actions operate on the current `woocommerce` context.

## Boundary rules

-   Product data is Store API shaped and read-oriented.
-   Product interaction state is mutable shopper input.
-   Cart state is committed or optimistic cart data.
-   `productInContext` can derive from interaction state, but must not store interaction state.
-   Blocks should depend on store/context, not Add to Cart + Options ancestry.

## Open questions

-   Final name for `productInteractionInContext`.
-   Exact selected attribute shape.
-   Are we comfortable keying `productInteractions` by product id, so repeated UI for the same product shares interaction state?
-   Should duplicated product cards with the same product share interaction state or remain independent?
-   Should `productVariationInContext` keep supporting explicit `variationId` context as an override?

<details>
<summary>Product type examples</summary>

### Simple product

Quantity belongs to the simple product id.

```typescript
state.productInteractions[ 123 ] = {
	productId: 123,
	selectedAttributes: [],
	quantities: { 123: 2 },
	validationErrors: [],
};
```

### Variable product

Selected attributes belong to the parent product. Quantity belongs to the selected variation id.

```typescript
state.productInteractions[ 100 ] = {
	productId: 100,
	selectedAttributes: [
		{ attribute: 'Color', value: 'blue', raw_attribute: 'pa_color' },
	],
	quantities: { 101: 2 },
	validationErrors: [],
};
```

### Add to Wishlist for variable product

Wishlist can read the selected variation without being inside Add to Cart + Options.

```typescript
const product = state.productInContext;

if ( product ) {
	wishlist.add( { productId: product.id } );
}
```

### Grouped product

The parent product owns the interaction. Quantities belong to child product ids.

```typescript
state.productInteractions[ 200 ] = {
	productId: 200,
	selectedAttributes: [],
	quantities: { 201: 2, 202: 1 },
	validationErrors: [],
};
```

### Bundle or composite product

The parent product owns the interaction. Extension-specific selections live under `extensions`.

```typescript
state.productInteractions[ 300 ] = {
	productId: 300,
	selectedAttributes: [],
	quantities: { 300: 1 },
	validationErrors: [],
	extensions: {
		'woocommerce/product-bundles': {
			// Bundle-specific child selections, quantities, and validation data.
		},
	},
};
```

</details>
