/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

/**
 * Get the product variation title for use in the header.
 *
 * @param  productVariation The product variation.
 * @return string
 */
export const getProductVariationTitle = (
	productVariation: Partial< ProductVariation >
) => {
	if ( ! productVariation?.attributes?.length ) {
		return '#' + productVariation.id;
	}

	return productVariation.attributes
		.map( ( attribute ) => {
			return attribute.option;
		} )
		.join( ', ' );
};
