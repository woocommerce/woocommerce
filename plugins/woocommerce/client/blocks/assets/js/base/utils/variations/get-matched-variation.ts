/**
 * External dependencies
 */
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/**
 * Store API variation format from parent product's variations field.
 */
type StoreAPIVariation = {
	id: number;
	attributes: Array< { name: string; value: string } >;
};

/**
 * Find the variation that matches the selected attributes.
 *
 * @param availableVariations - Array of variations from Store API (parent product's variations field).
 * @param selectedAttributes  - Array of selected attribute name/value pairs.
 * @return The matching variation ID, or null if no match found.
 */
export const getMatchedVariation = (
	availableVariations: StoreAPIVariation[],
	selectedAttributes: SelectedAttributes[]
): number | null => {
	if (
		! availableVariations ||
		! availableVariations.length ||
		! Array.isArray( selectedAttributes ) ||
		selectedAttributes.length === 0
	) {
		return null;
	}

	const matchingVariation = availableVariations.find( ( variation ) => {
		return variation.attributes.every( ( variationAttr ) => {
			const selectedAttr = selectedAttributes.find(
				( selected ) =>
					selected.attribute.toLowerCase() ===
					variationAttr.name.toLowerCase()
			);

			if ( ! selectedAttr ) {
				return false;
			}

			// Match if values are equal, or if variation accepts any value (empty string)
			return (
				selectedAttr.value.toLowerCase() ===
					variationAttr.value.toLowerCase() ||
				( selectedAttr.value && variationAttr.value === '' )
			);
		} );
	} );

	return matchingVariation?.id ?? null;
};
