/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PartialProduct, ProductVariation } from '@woocommerce/data';

/**
 * Labels for product stock statuses.
 */
export enum PRODUCT_STOCK_STATUS_KEYS {
	instock = 'instock',
	onbackorder = 'onbackorder',
	outofstock = 'outofstock',
}

/**
 * Labels for product stock statuses.
 */
export const PRODUCT_STOCK_STATUS_LABELS = {
	[ PRODUCT_STOCK_STATUS_KEYS.instock ]: __( 'In stock', 'woocommerce' ),
	[ PRODUCT_STOCK_STATUS_KEYS.onbackorder ]: __(
		'On backorder',
		'woocommerce'
	),
	[ PRODUCT_STOCK_STATUS_KEYS.outofstock ]: __(
		'Out of stock',
		'woocommerce'
	),
};

/**
 * Get the product stock quantity or stock status label.
 *
 * @param  product Product instance.
 * @return {PRODUCT_STOCK_STATUS_KEYS|number} Product stock quantity or product status key.
 */
export const getProductStockStatus = (
	product: PartialProduct | Partial< ProductVariation >
): string | number => {
	if ( product.manage_stock ) {
		return product.stock_quantity || 0;
	}

	if ( product.stock_status ) {
		return PRODUCT_STOCK_STATUS_LABELS[ product.stock_status ];
	}

	return PRODUCT_STOCK_STATUS_LABELS.instock;
};
