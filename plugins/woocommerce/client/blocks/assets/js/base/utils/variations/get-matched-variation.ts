/**
 * External dependencies
 */
import { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import type { ProductData } from '@woocommerce/stores/woocommerce/cart';

export type AvailableVariation = {
	attributes: Record< string, string >;
	variation_id: number;
	price_html: string;
	is_in_stock: boolean;
};

export const getMatchedVariation = (
	availableVariations: ProductData[ 'variations' ],
	selectedAttributes: SelectedAttributes[]
) => {
	if (
		! availableVariations ||
		! Object.keys( availableVariations ).length ||
		! Array.isArray( selectedAttributes ) ||
		selectedAttributes.length === 0
	) {
		return null;
	}

	const matchingVariation = Object.entries( availableVariations ).find(
		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		( [ _, availableVariation ] ) => {
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
		}
	);

	if ( ! matchingVariation ) {
		return null;
	}

	return {
		...matchingVariation[ 1 ],
		variation_id: Number( matchingVariation[ 0 ] ),
	};
};
