/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import type { CartResponseShippingRate } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { API_SITE_CURRENCY, displayForMinorUnit } from './utils';

export const previewShippingRates: CartResponseShippingRate[] = [
	{
		destination: {
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
		},
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
				...API_SITE_CURRENCY,
				name: __( 'Flat rate shipping', 'woocommerce' ),
				description: '',
				delivery_time: '',
				price: displayForMinorUnit( '500' ),
				taxes: '0',
				rate_id: 'flat_rate:0',
				instance_id: 0,
				meta_data: [],
				method_id: 'flat_rate',
				selected: false,
			},
			{
				...API_SITE_CURRENCY,
				name: __( 'Free shipping', 'woocommerce' ),
				description: '',
				delivery_time: '',
				price: '0',
				taxes: '0',
				rate_id: 'free_shipping:1',
				instance_id: 0,
				meta_data: [],
				method_id: 'flat_rate',
				selected: true,
			},
			{
				...API_SITE_CURRENCY,
				name: __( 'Local pickup', 'woocommerce' ),
				description: '',
				delivery_time: '',
				price: '0',
				taxes: '0',
				rate_id: 'pickup_location:1',
				instance_id: 1,
				meta_data: [
					{
						key: 'pickup_location',
						value: 'New York',
					},
					{
						key: 'pickup_address',
						value: '123 Easy Street, New York, 12345',
					},
				],
				method_id: 'pickup_location',
				selected: false,
			},
			{
				...API_SITE_CURRENCY,
				name: __( 'Local pickup', 'woocommerce' ),
				description: '',
				delivery_time: '',
				price: '0',
				taxes: '0',
				rate_id: 'pickup_location:2',
				instance_id: 1,
				meta_data: [
					{
						key: 'pickup_location',
						value: 'Los Angeles',
					},
					{
						key: 'pickup_address',
						value: '123 Easy Street, Los Angeles, California, 90210',
					},
				],
				method_id: 'pickup_location',
				selected: false,
			},
		],
	},
];
