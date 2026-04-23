/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const filtersPreview = [
	{
		type: __( 'Color', 'woocommerce' ),
		value: 'blue',
		label: __( 'Blue', 'woocommerce' ),
	},
	{
		type: __( 'Color', 'woocommerce' ),
		value: 'red',
		label: __( 'Red', 'woocommerce' ),
	},
	{
		type: __( 'Size', 'woocommerce' ),
		value: 'large',
		label: __( 'Large', 'woocommerce' ),
	},
	{
		type: __( 'Status', 'woocommerce' ),
		value: 'instock',
		label: __( 'In stock', 'woocommerce' ),
	},
	{
		type: __( 'Status', 'woocommerce' ),
		value: 'onsale',
		label: __( 'On sale', 'woocommerce' ),
	},
];
