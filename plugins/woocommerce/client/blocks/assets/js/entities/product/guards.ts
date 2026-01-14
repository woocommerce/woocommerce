/**
 * External dependencies
 */
import { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { ExternalProductResponse, ProductEntityResponse } from './types';

export const isExternalProduct = (
	product: ProductEntityResponse
): product is ExternalProductResponse => {
	if ( 'type' in product && product.type === 'external' ) {
		return true;
	}
	return false;
};

export const isProductResponseItem = (
	product: ProductResponseItem | ProductEntityResponse | undefined
): product is ProductResponseItem => {
	return !! product && 'id' in product && product.id !== 0;
};
