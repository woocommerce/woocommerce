/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const filtersPreview = [
	{
		type: __( 'Color', 'woocommerce' ),
		value: 'blue',
		activeLabel: __( 'Blue', 'woocommerce' ),
	},
	{
		type: __( 'Color', 'woocommerce' ),
		value: 'red',
		activeLabel: __( 'Red', 'woocommerce' ),
	},
	{
		type: __( 'Size', 'woocommerce' ),
		value: 'large',
		activeLabel: __( 'Large', 'woocommerce' ),
	},
	{
		type: __( 'Status', 'woocommerce' ),
		value: 'instock',
		activeLabel: __( 'In stock', 'woocommerce' ),
	},
	{
		type: __( 'Status', 'woocommerce' ),
		value: 'onsale',
		activeLabel: __( 'On sale', 'woocommerce' ),
	},
];
