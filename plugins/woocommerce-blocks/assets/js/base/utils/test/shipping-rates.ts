/**
 * External dependencies
 */
import {
	hasCollectableRate,
	isPackageRateCollectable,
} from '@woocommerce/base-utils';
import { CartShippingRate } from '@woocommerce/type-defs/cart';

jest.mock( '@woocommerce/settings', () => {
	return {
		...jest.requireActual( '@woocommerce/settings' ),
		getSetting: ( setting: string ) => {
			if ( setting === 'collectableMethodIds' ) {
				return [ 'local_pickup' ];
			}
			return jest
				.requireActual( '@woocommerce/settings' )
				.getSetting( setting );
		},
	};
} );
describe( 'hasCollectableRate', () => {
	it( 'correctly identifies if an array contains a collectable rate', () => {
		const ratesToTest = [ 'flat_rate', 'local_pickup' ];
		expect( hasCollectableRate( ratesToTest ) ).toBe( true );
		const ratesToTest2 = [ 'flat_rate', 'free_shipping' ];
		expect( hasCollectableRate( ratesToTest2 ) ).toBe( false );
	} );
} );

describe( 'isPackageRateCollectable', () => {
	it( 'correctly identifies if a package rate is collectable or not', () => {
		const testPackage: CartShippingRate = {
			package_id: 0,
			name: 'Shipping',
			destination: {
				address_1: '',
				address_2: '',
				city: '',
				state: '',
				postcode: '',
				country: '',
			},
			items: [],
			shipping_rates: [
				{
					rate_id: 'flat_rate:1',
					name: 'Flat rate',
					description: '',
					delivery_time: '',
					price: '10',
					taxes: '0',
					instance_id: 1,
					method_id: 'flat_rate',
					meta_data: [],
					selected: true,
					currency_code: 'USD',
					currency_symbol: '$',
					currency_minor_unit: 2,
					currency_decimal_separator: '.',
					currency_thousand_separator: ',',
					currency_prefix: '$',
					currency_suffix: '',
				},
				{
					rate_id: 'local_pickup:2',
					name: 'Local pickup',
					description: '',
					delivery_time: '',
					price: '0',
					taxes: '0',
					instance_id: 2,
					method_id: 'local_pickup',
					meta_data: [],
					selected: false,
					currency_code: 'USD',
					currency_symbol: '$',
					currency_minor_unit: 2,
					currency_decimal_separator: '.',
					currency_thousand_separator: ',',
					currency_prefix: '$',
					currency_suffix: '',
				},
			],
		};
		expect(
			isPackageRateCollectable( testPackage.shipping_rates[ 0 ] )
		).toBe( false );
		expect(
			isPackageRateCollectable( testPackage.shipping_rates[ 1 ] )
		).toBe( true );
	} );
} );
