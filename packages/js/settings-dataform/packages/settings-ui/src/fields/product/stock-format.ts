/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_stock_format',
	label: __( 'Stock display format', 'woocommerce' ),
	type: 'text' as const,
	description: __(
		'This controls how stock quantities are displayed on the frontend.',
		'woocommerce'
	),
	Edit: 'select',
	elements: [
		{
			value: '',
			label: __(
				'Always show quantity remaining in stock e.g. "12 in stock"',
				'woocommerce'
			),
		},
		{
			value: 'low_amount',
			label: __(
				'Only show quantity remaining in stock when low e.g. "Only 2 left in stock"',
				'woocommerce'
			),
		},
		{
			value: 'no_amount',
			label: __(
				'Never show quantity remaining in stock',
				'woocommerce'
			),
		},
	],
};
