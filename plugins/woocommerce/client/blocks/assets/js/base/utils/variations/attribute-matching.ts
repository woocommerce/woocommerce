/**
 * External dependencies
 */
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import type {
	ProductResponseItem,
	ProductResponseVariationsItem,
} from '@woocommerce/types';

/**
 * Get the attribute value from a variation's attributes array, matching by slug.
 *
 * The Store API includes the attribute slug (e.g., "attribute_pa_color") which
 * matches the PHP context's attribute name format exactly — no normalization needed.
 *
 * @param variation     The variation in Store API format.
 * @param attributeSlug The attribute slug to find (e.g., "attribute_pa_color").
 * @return The attribute value, or undefined if not found.
 */
export const getVariationAttributeValue = (
	variation: ProductResponseVariationsItem,
	attributeSlug: string
): string | undefined => {
	const attr = variation.attributes.find( ( a ) => a.slug === attributeSlug );
	return attr?.value;
};

/**
 * Find the matching variation from a product's variations based on selected attributes.
 *
 * Matches by comparing attribute slugs directly — both the PHP context and Store API
 * use the same slug format, so no normalization is needed.
 *
 * @param product            The product in Store API format.
 * @param selectedAttributes The selected attributes (using slug format).
 * @return The matching variation, or null if no match.
 */
export const findMatchingVariation = (
	product: ProductResponseItem,
	selectedAttributes: SelectedAttributes[]
): ProductResponseVariationsItem | null => {
	if ( ! product.variations?.length || ! selectedAttributes?.length ) {
		return null;
	}

	const matchedVariation = product.variations.find(
		( variation: ProductResponseVariationsItem ) => {
			return variation.attributes.every( ( attr ) => {
				const selectedAttr = selectedAttributes.find(
					( selected ) => selected.attribute === attr.slug
				);

				// If variation attribute is null, it accepts "Any" value.
				if ( attr.value === null ) {
					return (
						selectedAttr !== undefined &&
						selectedAttr.value !== null
					);
				}

				return selectedAttr?.value === attr.value;
			} );
		}
	);

	return matchedVariation ?? null;
};
