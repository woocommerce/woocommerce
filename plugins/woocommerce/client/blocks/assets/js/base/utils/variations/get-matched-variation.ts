/**
 * External dependencies
 */
import type {
	SelectedAttributes,
	ProductData,
	VariationData,
} from '@woocommerce/stores/woocommerce/cart';

type VariationWithId = VariationData & { variation_id: number };

/**
 * Normalize attribute name by removing the "attribute_" prefix if present.
 * Context uses format like "attribute_pa_color" or "attribute_size",
 * while Store API uses "pa_color" or "size".
 *
 * @param name - The attribute name to normalize.
 * @return The normalized attribute name.
 */
const normalizeAttributeName = ( name: string ): string => {
	return name.replace( /^attribute_/, '' ).toLowerCase();
};

/**
 * Find the variation that matches the selected attributes.
 *
 * @param availableVariations - Record of variations from product data.
 * @param selectedAttributes  - Array of selected attribute name/value pairs.
 * @return The matching variation object with variation_id, or null if no match found.
 */
export const getMatchedVariation = (
	availableVariations: ProductData[ 'variations' ],
	selectedAttributes: SelectedAttributes[]
): VariationWithId | null => {
	if (
		! availableVariations ||
		! Object.keys( availableVariations ).length ||
		! Array.isArray( selectedAttributes ) ||
		selectedAttributes.length === 0
	) {
		return null;
	}

	// Find variation where all selected attributes match
	const matchedVariationId = Object.keys( availableVariations ).find(
		( variationId ) => {
			const variation = availableVariations[ Number( variationId ) ];
			const variationAttrs = variation.attributes;

			// Count total attribute slots in the variation (including "any" attributes)
			// A variation with { color: "blue", size: "" } has 2 attributes
			const variationAttrCount = Object.keys( variationAttrs ).length;

			// Only match if we have selected the same number of attributes
			// (don't match partial selections)
			if ( selectedAttributes.length !== variationAttrCount ) {
				return false;
			}

			// Check if every selected attribute matches the variation
			return selectedAttributes.every( ( selected ) => {
				// Variation attributes are stored as Record<string, string>
				// e.g., { "attribute_color": "blue" }
				const variationValue = variationAttrs[ selected.attribute ];

				// Normalize null to empty string (backend bug workaround - null should be '')
				const normalizedValue = variationValue ?? '';

				// Match if values are equal, or if variation accepts any value (empty string)
				return (
					selected.value.toLowerCase() ===
						normalizedValue.toLowerCase() ||
					( selected.value && normalizedValue === '' )
				);
			} );
		}
	);

	if ( ! matchedVariationId ) {
		return null;
	}

	// Return the full variation object with variation_id added
	const variation = availableVariations[ Number( matchedVariationId ) ];
	return {
		...variation,
		variation_id: Number( matchedVariationId ),
	};
};
