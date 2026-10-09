/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * The catalog layer of the unified `woocommerce` store: server-seeded
 * product and variation data in Store API `ProductResponseItem` shape.
 * `ProductsStore` (PHP) is the only writer; the client never writes to
 * either member.
 */
export type CatalogState = {
	/** Products keyed by product ID. */
	products: Record< number, ProductResponseItem >;
	/** Product variations keyed by variation ID. */
	productVariations: Record< number, ProductResponseItem >;
};

/**
 * The catalog layer's initial state, merged into the `woocommerce` store.
 * PHP loaders (`ProductsStore::load_product`,
 * `load_purchasable_child_products`, `load_variations`) populate `products`
 * and `productVariations` as pages render.
 */
export const catalogState: CatalogState = {
	products: {},
	productVariations: {},
};
