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

/**
 * Internal dependencies
 */
import {
	getLocalPickupPrices,
	getShippingPrices,
} from '../../../blocks/checkout/inner-blocks/checkout-shipping-method-block/shared/helpers';
import { generateShippingRate } from '../../../mocks/shipping-package';

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
const blockSettingsMock = jest.requireMock( '@woocommerce/block-settings' );

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
		generateShippingRate( {
			rateId: 'flat_rate:1',
			name: 'Flat rate',
			price: '10',
			instanceID: 1,
		} ),
		generateShippingRate( {
			rateId: 'local_pickup:1',
			name: 'Local pickup',
			price: '0',
			instanceID: 2,
		} ),
		generateShippingRate( {
			rateId: 'local_pickup:2',
			name: 'Local pickup',
			price: '10',
			instanceID: 3,
		} ),
		generateShippingRate( {
			rateId: 'local_pickup:3',
			name: 'Local pickup',
			price: '50',
			instanceID: 4,
		} ),
		generateShippingRate( {
			rateId: 'flat_rate:2',
			name: 'Flat rate',
			price: '50',
			instanceID: 5,
		} ),
	],
};
describe( 'Test Min and Max rates', () => {
	it( 'returns the lowest and highest rates when local pickup method is used', () => {
		expect( getLocalPickupPrices( testPackage.shipping_rates ) ).toEqual( {
			min: generateShippingRate( {
				rateId: 'local_pickup:1',
				name: 'Local pickup',
				price: '0',
				instanceID: 2,
			} ),
			max: generateShippingRate( {
				rateId: 'local_pickup:3',
				name: 'Local pickup',
				price: '50',
				instanceID: 4,
			} ),
		} );
	} );
	it( 'returns the lowest and highest rates when flat rate shipping method is used', () => {
		expect( getShippingPrices( testPackage.shipping_rates ) ).toEqual( {
			min: generateShippingRate( {
				rateId: 'flat_rate:1',
				name: 'Flat rate',
				price: '10',
				instanceID: 1,
			} ),
			max: generateShippingRate( {
				rateId: 'flat_rate:2',
				name: 'Flat rate',
				price: '50',
				instanceID: 5,
			} ),
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
			blockSettingsMock.LOCAL_PICKUP_ENABLED = false;
			const ratesToTest = [ 'flat_rate', 'local_pickup' ];
			expect( hasCollectableRate( ratesToTest ) ).toBe( false );
		} );
	} );
} );
