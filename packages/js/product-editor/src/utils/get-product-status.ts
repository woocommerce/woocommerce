/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PartialProduct } from '@woocommerce/data';

/**
 * Labels for product statuses.
 */
export enum PRODUCT_STATUS_KEYS {
	unsaved = 'unsaved',
	draft = 'draft',
	instock = 'instock',
	outofstock = 'outofstock',
}

/**
 * Labels for product statuses.
 */
export const PRODUCT_STATUS_LABELS = {
	[ PRODUCT_STATUS_KEYS.unsaved ]: __( 'Unsaved', 'woocommerce' ),
	[ PRODUCT_STATUS_KEYS.draft ]: __( 'Draft', 'woocommerce' ),
	[ PRODUCT_STATUS_KEYS.instock ]: __( 'In stock', 'woocommerce' ),
	[ PRODUCT_STATUS_KEYS.outofstock ]: __( 'Out of stock', 'woocommerce' ),
};

/**
 * Get the product status for use in the header.
 *
 * @param  product Product instance.
 * @return {PRODUCT_STATUS_KEYS} Product status key.
 */
export const getProductStatus = (
	product: PartialProduct | undefined
): PRODUCT_STATUS_KEYS => {
	if ( ! product ) {
		return PRODUCT_STATUS_KEYS.unsaved;
	}

	if ( product.status === 'draft' ) {
		return PRODUCT_STATUS_KEYS.draft;
	}

	if ( product.stock_status === 'instock' ) {
		return PRODUCT_STATUS_KEYS.instock;
	}

	return PRODUCT_STATUS_KEYS.outofstock;
};
