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
 * product and variation data, plus the single-product template's current
 * selection, all in Store API `ProductResponseItem` shape. The client never
 * writes to any of these three members.
 */
export type CatalogState = {
	/** Products keyed by product ID. */
	products: Record< number, ProductResponseItem >;
	/** Product variations keyed by variation ID. */
	productVariations: Record< number, ProductResponseItem >;
	/**
	 * The single-product template's current product and variation
	 * selection, seeded by `SingleProductTemplate`.
	 */
	template: {
		/** The product ID for the current single-product template. */
		productId: number;
		/** The selected attributes for the current variation, if any. */
		variation: TemplateVariationAttribute[];
	};
};

/**
 * Normalizes an attribute name for matching against a shopper's selection:
 * strips the `attribute_` / `attribute_pa_` prefix, replaces hyphens with
 * spaces, and lowercases the result.
 *
 * @param name The raw attribute name, e.g. `attribute_pa_color`.
 * @return The normalized name, e.g. `color`.
 */
export const normalizeAttributeName = ( name: string ): string =>
	name
		.replace( /^attribute_(pa_)?/, '' )
		.replace( /-/g, ' ' )
		.toLowerCase();

/**
 * Compares two attribute names for equality once both are normalized.
 *
 * @param a The first attribute name.
 * @param b The second attribute name.
 * @return `true` when the normalized names match.
 */
export const attributeNamesMatch = ( a: string, b: string ): boolean =>
	normalizeAttributeName( a ) === normalizeAttributeName( b );

/**
 * The catalog layer's initial state, merged into the `woocommerce` store.
 * Server loaders populate `products` and `productVariations`;
 * `SingleProductTemplate` seeds `template`.
 */
export const catalogState: CatalogState = {
	products: {},
	productVariations: {},
	template: {
		productId: 0,
		variation: [],
	},
};
