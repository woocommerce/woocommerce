/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const previewShippingRates = [
	{
		destination: {},
		items: {},
		shipping_rates: [
			{
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
				name: __( 'Free shipping', 'woo-gutenberg-products-block' ),
				description: '',
				delivery_time: '',
				price: '200',
				rate_id: 'free_shipping:1',
			},
			{
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
				name: __( 'Local pickup', 'woo-gutenberg-products-block' ),
				description: '',
				delivery_time: '',
				price: '0',
				rate_id: 'local_pickup:1',
			},
		],
	},
];
