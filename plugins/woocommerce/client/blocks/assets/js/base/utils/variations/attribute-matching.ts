/**
 * External dependencies
 */
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import type {
	ProductResponseItem,
	ProductResponseVariationsItem,
} from '@woocommerce/types';

/**
 * Normalize attribute name by stripping the 'attribute_' or 'attribute_pa_' prefix
 * that WooCommerce adds for variation attributes.
 *
 * @param name The attribute name (e.g., 'attribute_color' or 'attribute_pa_color').
 * @return The normalized name (e.g., 'color').
 */
export const normalizeAttributeName = ( name: string ): string => {
	return name.replace( /^attribute_(pa_)?/, '' );
};

/**
 * Check if two attribute names match, using case-insensitive comparison.
 *
 * This handles the mismatch between Store API labels (e.g., "Color") and
 * PHP context slugs (e.g., "attribute_pa_color").
 *
 * @param name1 First attribute name (may be label or slug format).
 * @param name2 Second attribute name (may be label or slug format).
 * @return True if the names match after normalization.
 */
export const attributeNamesMatch = (
	name1: string,
	name2: string
): boolean => {
	return (
		normalizeAttributeName( name1 ).toLowerCase() ===
		normalizeAttributeName( name2 ).toLowerCase()
	);
};

/**
 * Get the attribute value from a variation's attributes array.
 *
 * The Store API returns the attribute label (e.g., "Color") in the name field,
 * while the PHP context uses the attribute slug (e.g., "attribute_pa_color").
 * We do a case-insensitive comparison to match "color" with "Color".
 *
 * @param variation     The variation in Store API format.
 * @param attributeName The attribute name to find (may include 'attribute_' prefix).
 * @return The attribute value, or undefined if not found.
 */
export const getVariationAttributeValue = (
	variation: ProductResponseVariationsItem,
	attributeName: string
): string | undefined => {
	const normalizedName =
		normalizeAttributeName( attributeName ).toLowerCase();
	const attr = variation.attributes.find(
		( a ) => a.name.toLowerCase() === normalizedName
	);
	return attr?.value;
};

/**
 * Find the matching variation from a product's variations based on selected attributes.
 *
 * Uses case-insensitive comparison since Store API returns labels (e.g., "Color")
 * while PHP context uses slugs (e.g., "attribute_pa_color" → "color").
 *
 * @param product            The product in Store API format.
 * @param selectedAttributes The selected attributes.
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
				const attrNameLower = attr.name.toLowerCase();
				const selectedAttr = selectedAttributes.find(
					( selected ) =>
						normalizeAttributeName(
							selected.attribute
						).toLowerCase() === attrNameLower
				);

				// If variation attribute has empty value, it accepts "Any" value.
				if ( attr.value === '' ) {
					return (
						selectedAttr !== undefined && selectedAttr.value !== ''
					);
				}

				return selectedAttr?.value === attr.value;
			} );
		}
	);

	return matchedVariation ?? null;
};
