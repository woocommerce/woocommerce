/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';

export const previewShippingRates = [
	{
		destination: {},
		package_id: 0,
		name: __( 'Shipping', 'woocommerce' ),
		items: [
			{
				key: '33e75ff09dd601bbe69f351039152189',
				name: _x(
					'Beanie with Logo',
					'example product in Cart Block',
					'woocommerce'
				),
				quantity: 2,
			},
			{
				key: '6512bd43d9caa6e02c990b0a82652dca',
				name: _x(
					'Beanie',
					'example product in Cart Block',
					'woocommerce'
				),
				quantity: 1,
			},
		],
		shipping_rates: [
			{
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
				name: __( 'Free shipping', 'woocommerce' ),
				description: '',
				delivery_time: '',
				price: '000',
				rate_id: 'free_shipping:1',
				method_id: 'flat_rate',
				selected: true,
			},
			{
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
				name: __( 'Local pickup', 'woocommerce' ),
				description: '',
				delivery_time: '',
				price: '200',
				rate_id: 'local_pickup:1',
				method_id: 'local_pickup',
				selected: false,
			},
		],
	},
];
