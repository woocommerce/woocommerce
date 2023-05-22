/**
 * External dependencies
 */
import {
	hasCollectableRate,
	isPackageRateCollectable,
} from '@woocommerce/base-utils';
import {
	CartShippingRate,
	CartShippingPackageShippingRate,
} from '@woocommerce/type-defs/cart';
import * as blockSettings from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import {
	getLocalPickupPrices,
	getShippingPrices,
} from '../../../blocks/checkout/inner-blocks/checkout-shipping-method-block/shared/helpers';

jest.mock( '@woocommerce/settings', () => {
	return {
		__esModule: true,
		...jest.requireActual( '@woocommerce/settings' ),
		getSetting: jest.fn().mockImplementation( ( setting: string ) => {
			if ( setting === 'collectableMethodIds' ) {
				return [ 'local_pickup' ];
			}
			return jest
				.requireActual( '@woocommerce/settings' )
				.getSetting( setting );
		} ),
	};
} );
jest.mock( '@woocommerce/block-settings', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/block-settings' ),
	LOCAL_PICKUP_ENABLED: true,
} ) );

// Returns a rate object with the given values
const generateRate = (
	rateId: string,
	name: string,
	price: string,
	instanceID: number,
	selected = false
): typeof testPackage.shipping_rates[ 0 ] => {
	return {
		rate_id: rateId,
		name,
		description: '',
		delivery_time: '',
		price,
		taxes: '0',
		instance_id: instanceID,
		method_id: name.toLowerCase().split( ' ' ).join( '_' ),
		meta_data: [],
		selected,
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
	};
};

// A test package with 5 shipping rates
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
		generateRate( 'flat_rate:1', 'Flat rate', '10', 1 ),
		generateRate( 'local_pickup:1', 'Local pickup', '0', 2 ),
		generateRate( 'local_pickup:2', 'Local pickup', '10', 3 ),
		generateRate( 'local_pickup:3', 'Local pickup', '50', 4 ),
		generateRate( 'flat_rate:2', 'Flat rate', '50', 5 ),
	],
};
describe( 'Test Min and Max rates', () => {
	it( 'returns the lowest and highest rates when local pickup method is used', () => {
		expect( getLocalPickupPrices( testPackage.shipping_rates ) ).toEqual( {
			min: generateRate( 'local_pickup:1', 'Local pickup', '0', 2 ),

			max: generateRate( 'local_pickup:3', 'Local pickup', '50', 4 ),
		} );
	} );
	it( 'returns the lowest and highest rates when flat rate shipping method is used', () => {
		expect( getShippingPrices( testPackage.shipping_rates ) ).toEqual( {
			min: generateRate( 'flat_rate:1', 'Flat rate', '10', 1 ),
			max: generateRate( 'flat_rate:2', 'Flat rate', '50', 5 ),
		} );
	} );
	it( 'returns undefined as lowest and highest rates when shipping rates are not available', () => {
		const testEmptyShippingRates: CartShippingPackageShippingRate[] = [];
		expect( getLocalPickupPrices( testEmptyShippingRates ) ).toEqual( {
			min: undefined,
			max: undefined,
		} );
		expect( getShippingPrices( testEmptyShippingRates ) ).toEqual( {
			min: undefined,
			max: undefined,
		} );
	} );
} );

describe( 'isPackageRateCollectable', () => {
	it( 'correctly identifies if a package rate is collectable or not', () => {
		expect(
			isPackageRateCollectable( testPackage.shipping_rates[ 0 ] )
		).toBe( false );
		expect(
			isPackageRateCollectable( testPackage.shipping_rates[ 1 ] )
		).toBe( true );
	} );
	describe( 'hasCollectableRate', () => {
		it( 'correctly identifies if an array contains a collectable rate', () => {
			const ratesToTest = [ 'flat_rate', 'local_pickup' ];
			expect( hasCollectableRate( ratesToTest ) ).toBe( true );
			const ratesToTest2 = [ 'flat_rate', 'free_shipping' ];
			expect( hasCollectableRate( ratesToTest2 ) ).toBe( false );
		} );
		it( 'returns false for all rates if local pickup is disabled', () => {
			// Attempt to assign to const or readonly variable error on next line is OK because it is mocked by jest
			blockSettings.LOCAL_PICKUP_ENABLED = false;
			const ratesToTest = [ 'flat_rate', 'local_pickup' ];
			expect( hasCollectableRate( ratesToTest ) ).toBe( false );
		} );
	} );
} );
