/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export const getDerivedProductType = ( product: Partial< Product > ) => {
	if ( ! window.wcAdminFeatures[ 'product-variation-management' ] ) {
		return 'simple';
	}

	const hasOptions = !! product.attributes?.find(
		( attribute ) => attribute.options.length && attribute.variation
	);

	if ( hasOptions ) {
		return 'variable';
	}

	return 'simple';
};
