/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const sellingVenueOptions = [
	{
		key: 'no',
		label: __( 'No', 'woocommerce' ),
	},
	{
		key: 'other',
		label: __( 'Yes, on another platform', 'woocommerce' ),
	},
	{
		key: 'other-woocommerce',
		label: __(
			'Yes, I own a different store powered by WooCommerce',
			'woocommerce'
		),
	},
	{
		key: 'brick-mortar',
		label: __(
			'Yes, in person at physical stores and/or events',
			'woocommerce'
		),
	},
	{
		key: 'brick-mortar-other',
		label: __(
			'Yes, on another platform and in person at physical stores and/or events',
			'woocommerce'
		),
	},
];
