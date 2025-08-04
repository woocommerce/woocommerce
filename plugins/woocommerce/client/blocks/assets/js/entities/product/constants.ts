/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Entity } from '../types';

export const PRODUCT_ENTITY: Entity = {
	name: 'product',
	kind: 'root',
	baseURL: '/wc/v3/products',
	label: __( 'Product', 'woocommerce' ),
	plural: __( 'Products', 'woocommerce' ),
	key: 'id',
	supportsPagination: true,
};
