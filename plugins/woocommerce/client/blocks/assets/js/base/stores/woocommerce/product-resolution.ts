/**
 * Product resolution — the pure primitive backing `woocommerce/products`'
 * `findProduct` getter.
 *
 * A folder-internal module (not a public export surface of either store):
 * it hosts {@link resolveProduct}, the id/attribute resolution body
 * `products.ts`'s `findProduct` getter delegates to verbatim. Takes the
 * product/variation maps it resolves against as explicit arguments rather
 * than reaching a namespace by `store()` — unlike this folder's other
 * internal helper modules — because the maps' own shape is what a future
 * root store module changes (nested `state.products.items` /
 * `state.products.variations`, in place of today's flat
 * `state.products` / `state.productVariations`); a namespace read would
 * hard-code today's flat shape into the primitive. Passing the maps in
 * lets every caller — `products.ts` today, any future caller reading a
 * differently-shaped store — hand this primitive its own maps unchanged.
 *
 * Value-imports only the existing attribute-matching util
 * ({@link attributeNamesMatch}); never `cart.ts` or `products.ts`.
 */

/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { SelectedAttributes } from './cart';
import { attributeNamesMatch } from '../../utils/variations/attribute-matching';

/**
 * Resolves a product or variation by id, optionally narrowing a variable
 * product to one of its variations via `selectedAttributes`.
 *
 * Reproduces `woocommerce/products`' `findProduct` getter's contract
 * exactly, as a pure function of the two maps passed in: `id` naming a
 * known variation resolves that variation directly, ignoring
 * `selectedAttributes` entirely; otherwise `id` resolves against `items`
 * — a non-variable product (or a variable product with no non-empty
 * `selectedAttributes`) resolves unchanged, while a variable product with
 * a non-empty `selectedAttributes` resolves the matching variation (via
 * {@link attributeNamesMatch}, tolerating the Store API's label/slug
 * mismatches) or `null` when no variation matches, or the matched
 * variation's own entry is absent from `variations`. An unknown `id`
 * (present in neither map) resolves `null`.
 *
 * @param items                   The product map to resolve `id` against
 *                                (`state.products` today; a future root
 *                                module's `state.products.items`).
 * @param variations              The variation map `id` may name directly,
 *                                or a matched variation may resolve through
 *                                (`state.productVariations` today; a future
 *                                root module's `state.products.variations`).
 * @param args                    Resolution arguments.
 * @param args.id                 The product or variation id to resolve.
 * @param args.selectedAttributes The attributes to narrow a variable
 *                                product's variations by, if any.
 * @return The resolved variation, the resolved product unchanged, or
 *         `null` when `id` names nothing in either map, or a variable
 *         product's attributes match no variation, or the matched
 *         variation is unpopulated in `variations`.
 */
export function resolveProduct(
	items: Record< number, ProductResponseItem >,
	variations: Record< number, ProductResponseItem >,
	{
		id,
		selectedAttributes,
	}: {
		id: number;
		selectedAttributes?: SelectedAttributes[] | null | undefined;
	}
): ProductResponseItem | null {
	const variation = variations[ id ];
	if ( variation ) {
		return variation;
	}

	const product = items[ id ];

	if ( ! product ) {
		return null;
	}

	if ( product.type !== 'variable' || ! selectedAttributes?.length ) {
		return product;
	}

	const matchedVariation = product.variations?.find( ( v ) =>
		v.attributes.every( ( attr ) => {
			const selectedAttr = selectedAttributes.find( ( selected ) =>
				attributeNamesMatch( attr.name, selected.attribute )
			);

			if ( attr.value === null ) {
				return (
					selectedAttr !== undefined && selectedAttr.value !== null
				);
			}

			return selectedAttr?.value === attr.value;
		} )
	);

	if ( ! matchedVariation ) {
		return null;
	}

	return variations[ matchedVariation.id ] ?? null;
}
