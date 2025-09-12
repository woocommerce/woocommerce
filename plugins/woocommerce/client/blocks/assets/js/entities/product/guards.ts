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
