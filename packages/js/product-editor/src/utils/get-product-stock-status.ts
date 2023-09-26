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
 * Product stock status colors.
 */
export enum PRODUCT_STOCK_STATUS_CLASSES {
	instock = 'green',
	onbackorder = 'yellow',
	outofstock = 'red',
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
		return PRODUCT_STOCK_STATUS_LABELS[
			product.stock_status as PRODUCT_STOCK_STATUS_KEYS
		];
	}

	return PRODUCT_STOCK_STATUS_LABELS.instock;
};

/**
 * Get the product stock status class.
 *
 * @param  product Product instance.
 * @return {PRODUCT_STOCK_STATUS_CLASSES} Product stock status class.
 */
export const getProductStockStatusClass = (
	product: PartialProduct | Partial< ProductVariation >
): string => {
	if ( product.manage_stock ) {
		const stockQuantity: number = product.stock_quantity || 0;
		if ( stockQuantity >= 10 ) {
			return PRODUCT_STOCK_STATUS_CLASSES.instock;
		}
		if ( stockQuantity < 10 && stockQuantity > 2 ) {
			return PRODUCT_STOCK_STATUS_CLASSES.onbackorder;
		}
		return PRODUCT_STOCK_STATUS_CLASSES.outofstock;
	}
	return product.stock_status
		? PRODUCT_STOCK_STATUS_CLASSES[
				product.stock_status as PRODUCT_STOCK_STATUS_KEYS
		  ]
		: '';
};
