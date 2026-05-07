/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const filtersPreview = [
	{
		id: 'color_blue',
		type: __( 'Color', 'woocommerce' ),
		value: 'blue',
		label: __( 'Blue', 'woocommerce' ),
	},
	{
		id: 'color_red',
		type: __( 'Color', 'woocommerce' ),
		value: 'red',
		label: __( 'Red', 'woocommerce' ),
	},
	{
		id: 'size_large',
		type: __( 'Size', 'woocommerce' ),
		value: 'large',
		label: __( 'Large', 'woocommerce' ),
	},
	{
		id: 'status_instock',
		type: __( 'Status', 'woocommerce' ),
		value: 'instock',
		label: __( 'In stock', 'woocommerce' ),
	},
	{
		id: 'status_onsale',
		type: __( 'Status', 'woocommerce' ),
		value: 'onsale',
		label: __( 'On sale', 'woocommerce' ),
	},
];
