/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * A single selected attribute of the current single-product template
 * variation, in the same `{ attribute, value }` shape the Store API uses for
 * cart-line variation attributes.
 */
export type TemplateVariationAttribute = {
	/** The attribute name, e.g. `attribute_pa_color`. */
	attribute: string;
	/** The selected value for that attribute. */
	value: string;
};

/**
 * The catalog layer of the unified `woocommerce` store: server-seeded
 * product and variation data in Store API `ProductResponseItem` shape, plus
 * the single-product template's current selection. The client never writes
 * to any of these three members.
 */
export type CatalogState = {
	/** Products keyed by product ID. */
	products: Record< number, ProductResponseItem >;
	/** Product variations keyed by variation ID. */
	productVariations: Record< number, ProductResponseItem >;
	/**
	 * The single-product template's current product and variation
	 * selection, seeded by `SingleProductTemplate`. Only that template seeds
	 * it, so it is absent on every other page — `scope.ts`'s identity
	 * resolution falls back to product id `0` and an empty variation when it
	 * is missing.
	 */
	template?: {
		/** The product ID for the current single-product template. */
		productId: number;
		/** The selected attributes for the current variation, if any. */
		variation: TemplateVariationAttribute[];
	};
};

/**
 * The catalog layer's initial state, merged into the `woocommerce` store.
 * PHP loaders (`ProductsStore::load_product`,
 * `load_purchasable_child_products`, `load_variations`) populate `products`
 * and `productVariations` as pages render; `SingleProductTemplate` seeds
 * `template`.
 *
 * `template` carries no initial default, the same as `cart.ts` never
 * declares one for `cart`: `store()` overrides server-seeded state with the
 * client's own, so a concrete literal here would replace the real value.
 */
export const catalogState: CatalogState = {
	products: {},
	productVariations: {},
};
