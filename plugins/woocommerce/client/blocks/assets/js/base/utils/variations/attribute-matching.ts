/**
 * External dependencies
 */
import type { ProductResponseVariationsItem } from '@woocommerce/types';

/**
 * Normalize attribute name by stripping the 'attribute_' or 'attribute_pa_' prefix
 * that WooCommerce adds for variation attributes, and replacing hyphens with spaces
 * so that slugs (e.g., "some-name") match labels (e.g., "some name").
 *
 * @param name The attribute name (e.g., 'attribute_color' or 'attribute_pa_color').
 * @return The normalized name (e.g., 'color').
 */
export const normalizeAttributeName = ( name: string ): string => {
	return name
		.replace( /^attribute_(pa_)?/, '' )
		.replace( /-/g, ' ' )
		.toLowerCase();
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
	return normalizeAttributeName( name1 ) === normalizeAttributeName( name2 );
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
	const attr = variation.attributes.find( ( a ) =>
		attributeNamesMatch( attributeName, a.name )
	);
	return attr?.value;
};
