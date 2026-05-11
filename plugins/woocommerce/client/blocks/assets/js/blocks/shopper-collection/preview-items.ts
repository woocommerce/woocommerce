/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Shared mock data for the editor preview of the Shopper Collection block
 * and its inner blocks. The header inner block derives its preview count
 * from `PREVIEW_ITEMS.length` so the two views stay aligned automatically.
 */
export const PREVIEW_ITEMS = [
	{
		key: 'preview-1',
		name: __( 'Sample product one', 'woocommerce' ),
		variation: __( 'Size: M', 'woocommerce' ),
		price: '$19.99',
		quantity: __( 'Qty: 2', 'woocommerce' ),
	},
	{
		key: 'preview-2',
		name: __( 'Sample product two', 'woocommerce' ),
		variation: __( 'Color: Blue', 'woocommerce' ),
		price: '$29.99',
		quantity: __( 'Qty: 1', 'woocommerce' ),
	},
	{
		key: 'preview-3',
		name: __( 'Sample product three', 'woocommerce' ),
		variation: '',
		price: '$9.99',
		quantity: __( 'Qty: 3', 'woocommerce' ),
	},
	{
		key: 'preview-4',
		name: __( 'Sample product four', 'woocommerce' ),
		variation: __( 'Size: L', 'woocommerce' ),
		price: '$24.99',
		quantity: __( 'Qty: 1', 'woocommerce' ),
	},
	{
		key: 'preview-5',
		name: __( 'Sample product five', 'woocommerce' ),
		variation: '',
		price: '$14.99',
		quantity: __( 'Qty: 2', 'woocommerce' ),
	},
	{
		key: 'preview-6',
		name: __( 'Sample product six', 'woocommerce' ),
		variation: __( 'Color: Red', 'woocommerce' ),
		price: '$39.99',
		quantity: __( 'Qty: 1', 'woocommerce' ),
	},
];
