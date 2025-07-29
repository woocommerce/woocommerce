/**
 * External dependencies
 */
import { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

export type AvailableVariation = {
	attributes: Record< string, string >;
	variation_id: number;
	price_html: string;
	is_in_stock: boolean;
};

export const getMatchedVariation = (
	availableVariations: AvailableVariation[],
	selectedAttributes: SelectedAttributes[]
) => {
	if (
		! Array.isArray( availableVariations ) ||
		! Array.isArray( selectedAttributes ) ||
		availableVariations.length === 0 ||
		selectedAttributes.length === 0
	) {
		return null;
	}
	return (
		availableVariations.find( ( availableVariation ) => {
			return Object.entries( availableVariation.attributes ).every(
				( [ attributeName, attributeValue ] ) => {
					const attributeMatched = selectedAttributes.some(
						( variationAttribute ) => {
							const isSameAttribute =
								variationAttribute.attribute === attributeName;
							if ( ! isSameAttribute ) {
								return false;
							}

							return (
								variationAttribute.value === attributeValue ||
								( variationAttribute.value &&
									attributeValue === '' )
							);
						}
					);

					return attributeMatched;
				}
			);
		} ) || null
	);
};
