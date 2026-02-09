/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import type { CartResponseShippingRate } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { API_SITE_CURRENCY, displayForMinorUnit } from './utils';

// Get local pickup locations from the settings and format into some preview shipping rates for the response.
const localPickupEnabled = getSetting< boolean >( 'localPickupEnabled', false );
const localPickupTitle = getSetting< string >(
	'localPickupText',
	__( 'Local pickup', 'woocommerce' )
);
const localPickupCost = getSetting< string >( 'localPickupCost', '' );
const localPickupLocations = localPickupEnabled
	? getSetting<
			{
				enabled: boolean;
				name: string;
				formatted_address: string;
				details: string;
			}[]
	  >( 'localPickupLocations', [] )
	: [];

const localPickupRates = localPickupLocations
	? Object.values( localPickupLocations ).map(
			( location, index: number ) => ( {
				...API_SITE_CURRENCY,
				name: `${ localPickupTitle } (${ location.name })`,
				description: '',
				delivery_time: '',
				price: displayForMinorUnit( localPickupCost, 0 ) || '0',
				taxes: '0',
				rate_id: `pickup_location:${ index + 1 }`,
				instance_id: index + 1,
				meta_data: [
					{
						key: 'pickup_location',
						value: location.name,
					},
					{
						key: 'pickup_address',
						value: location.formatted_address,
					},
					{
						key: 'pickup_details',
						value: location.details,
					},
				],
				method_id: 'pickup_location',
				selected: false,
			} )
	  )
	: [];

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
			...localPickupRates,
		],
	},
];
