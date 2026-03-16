/**
 * External dependencies
 */
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import type {
	ProductResponseItem,
	ProductResponseVariationsItem,
} from '@woocommerce/types';

/**
 * A mapping of attribute slugs (e.g., "attribute_pa_color") to their
 * Store API label names (e.g., "Color"). This is provided by the PHP context
 * and allows reliable matching without heuristic normalization.
 */
export type AttributeSlugToLabel = Record< string, string >;

/**
 * Get the attribute value from a variation's attributes array, using the
 * slug-to-label mapping to translate between PHP context slugs and Store API
 * attribute names.
 *
 * @param variation     The variation in Store API format.
 * @param attributeSlug The attribute slug (e.g., "attribute_pa_color").
 * @param slugToLabel   Mapping of attribute slugs to Store API label names.
 * @return The attribute value, or undefined if not found.
 */
export const getVariationAttributeValue = (
	variation: ProductResponseVariationsItem,
	attributeSlug: string,
	slugToLabel: AttributeSlugToLabel
): string | undefined => {
	const label = slugToLabel[ attributeSlug ];
	if ( ! label ) {
		return undefined;
	}
	const attr = variation.attributes.find( ( a ) => a.name === label );
	return attr?.value;
};

/**
 * Find the matching variation from a product's variations based on selected
 * attributes, using the slug-to-label mapping for reliable matching.
 *
 * @param product            The product in Store API format.
 * @param selectedAttributes The selected attributes (using slug format).
 * @param slugToLabel        Mapping of attribute slugs to Store API label names.
 * @return The matching variation, or null if no match.
 */
export const findMatchingVariation = (
	product: ProductResponseItem,
	selectedAttributes: SelectedAttributes[],
	slugToLabel: AttributeSlugToLabel
): ProductResponseVariationsItem | null => {
	if ( ! product.variations?.length || ! selectedAttributes?.length ) {
		return null;
	}

	const matchedVariation = product.variations.find(
		( variation: ProductResponseVariationsItem ) => {
			return variation.attributes.every( ( attr ) => {
				const selectedAttr = selectedAttributes.find(
					( selected ) =>
						slugToLabel[ selected.attribute ] === attr.name
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
