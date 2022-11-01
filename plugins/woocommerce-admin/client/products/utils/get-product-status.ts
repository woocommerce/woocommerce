/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PartialProduct } from '@woocommerce/data';

/**
 * Get the product status for use in the header.
 *
 * @param  product Product instance.
 * @return string
 */
export const getProductStatus = ( product: PartialProduct | undefined ) => {
	if ( ! product ) {
		return __( 'Unsaved', 'woocommerce' );
	}

	if ( product.status === 'draft' ) {
		return __( 'Draft', 'woocommerce' );
	}

	if ( product.stock_status === 'instock' ) {
		return __( 'In stock', 'woocommerce' );
	}

	return __( 'Out of stock', 'woocommerce' );
};
