/**
 * Product resolution — the pure primitive backing the unified `woocommerce`
 * store's `findItem`/`itemInContext` product-member resolution.
 *
 * A folder-internal module (not a public export surface of either store):
 * it hosts {@link resolveProduct}, the id/attribute resolution body the
 * root module (`index.ts`) delegates to for every product/variation lookup
 * — the same body that once backed the retired `woocommerce/products`
 * store's `findProduct` getter, since dissolved along with `products.ts`
 * (see `index.ts`'s `resolveProductMember`, and `draft-internals.ts`'s
 * `matchFamilyVariation`, both of which call it directly). Takes the
 * product/variation maps it resolves against as explicit arguments rather
 * than reaching a namespace by `store()` — unlike this folder's other
 * internal helper modules — so it stays a pure function of its inputs,
 * testable in isolation with plain object literals (see
 * `test/product-resolution.test.ts`) and reusable by any caller willing to
 * hand it its own maps unchanged, whatever shape its own state happens to
 * be. The shipped shape both current callers pass is the nested
 * `state.products.items` / `state.products.variations`.
 *
 * Value-imports only the existing attribute-matching util
 * ({@link attributeNamesMatch}); never `cart.ts` or `index.ts`.
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
 * Reproduces the retired `woocommerce/products` store's `findProduct`
 * getter's contract exactly (that store and getter are since dissolved; the
 * root module's `state.itemInContext`/`state.findItem` are the current
 * entry points), as a pure function of the two maps passed in: `id` naming
 * a known variation resolves that variation directly, ignoring
 * `selectedAttributes` entirely; otherwise `id` resolves against `items`
 * — a non-variable product (or a variable product with no non-empty
 * `selectedAttributes`) resolves unchanged, while a variable product with
 * a non-empty `selectedAttributes` resolves the matching variation (via
 * {@link attributeNamesMatch}, tolerating the Store API's label/slug
 * mismatches) or `null` when no variation matches, or the matched
 * variation's own entry is absent from `variations`. An unknown `id`
 * (present in neither map) resolves `null`.
 *
 * @param items                   The product map to resolve `id` against —
 *                                the shipped nested `state.products.items`,
 *                                as `index.ts` and `draft-internals.ts`
 *                                pass it.
 * @param variations              The variation map `id` may name directly,
 *                                or a matched variation may resolve through
 *                                — the shipped nested
 *                                `state.products.variations`.
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
