/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PRODUCT_VARIATION_TITLE_LIMIT } from './constants';

/**
 * Get the product variation title for use in the header.
 *
 * @param productVariation The product variation.
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

/**
 * Get the truncated product variation title.
 *
 * @param productVariation The product variation.
 * @return string
 */
export const getTruncatedProductVariationTitle = (
	productVariation: Partial< ProductVariation >
) => {
	const title = getProductVariationTitle( productVariation );

	if ( title.length > PRODUCT_VARIATION_TITLE_LIMIT ) {
		return title.substring( 0, PRODUCT_VARIATION_TITLE_LIMIT ) + '…';
	}

	return title;
};
