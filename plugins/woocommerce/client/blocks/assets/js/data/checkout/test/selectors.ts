/**
 * External dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { prefersCollection } from '../selectors';
import { defaultState } from '../default-state';
import { generateShippingRate, generateShippingPackage } from '../../../mocks/shipping-package';

// Must be hoisted before imports so shipping-rates.ts captures the mocked collectableMethodIds.
jest.mock( '@woocommerce/settings', () => {
	const actual = jest.requireActual( '@woocommerce/settings' );
	return {
		__esModule: true,
		...actual,
		getSetting: jest.fn().mockImplementation( ( key: string, fallback?: unknown ) => {
			if ( key === 'collectableMethodIds' ) {
				return [ 'pickup_location' ];
			}
			return fallback;
		} ),
	};
} );

// LOCAL_PICKUP_ENABLED must be true for hasCollectableRate to work.
jest.mock( '@woocommerce/block-settings', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/block-settings' ),
	LOCAL_PICKUP_ENABLED: true,
} ) );

jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );
	return {
		__esModule: true,
		...actual,
		select: jest.fn(),
	};
} );

const mockSelect = select as jest.Mock;
const { getSetting } = jest.requireMock( '@woocommerce/settings' );

const pickupRate = generateShippingRate( {
	rateId: 'pickup_location:1',
	name: 'Pickup',
	price: '0',
	instanceID: 1,
	methodID: 'pickup_location',
	selected: true,
} );

const shippingRate = generateShippingRate( {
	rateId: 'flat_rate:1',
	name: 'Flat Rate',
	price: '500',
	instanceID: 1,
	methodID: 'flat_rate',
	selected: true,
} );

describe( 'prefersCollection selector', () => {
	beforeEach( () => {
		// Reset to default: getSetting('defaultCheckoutTab') returns 'shipping' (via fallback).
		getSetting.mockImplementation( ( key: string, fallback?: unknown ) => {
			if ( key === 'collectableMethodIds' ) return [ 'pickup_location' ];
			return fallback;
		} );
	} );

	describe( 'when state.prefersCollection is explicitly set', () => {
		it( 'returns true when state is true', () => {
			const state = { ...defaultState, prefersCollection: true };
			expect( prefersCollection( state ) ).toBe( true );
		} );

		it( 'returns false when state is false', () => {
			const state = { ...defaultState, prefersCollection: false };
			expect( prefersCollection( state ) ).toBe( false );
		} );
	} );

	describe( 'when state.prefersCollection is undefined (first load)', () => {
		const undefinedState = { ...defaultState, prefersCollection: undefined };

		it( 'returns false when there are no shipping rates', () => {
			mockSelect.mockReturnValue( { getShippingRates: () => [] } );
			expect( prefersCollection( undefinedState ) ).toBe( false );
		} );

		it( 'returns false when shipping rates have not loaded yet', () => {
			mockSelect.mockReturnValue( { getShippingRates: () => null } );
			expect( prefersCollection( undefinedState ) ).toBe( false );
		} );

		it( 'returns a falsy value when no rate is selected in the package', () => {
			const unselectedPickup = generateShippingRate( {
				rateId: 'pickup_location:1',
				name: 'Pickup',
				price: '0',
				instanceID: 1,
				methodID: 'pickup_location',
				selected: false,
			} );
			const pkg = generateShippingPackage( {
				packageId: 0,
				shippingRates: [ unselectedPickup ],
			} );
			mockSelect.mockReturnValue( { getShippingRates: () => [ pkg ] } );
			// No selected rate → falls through to state.prefersCollection (undefined), which is falsy.
			expect( prefersCollection( undefinedState ) ).toBeFalsy();
		} );

		describe( 'when a pickup rate is pre-selected by the server', () => {
			beforeEach( () => {
				const pkg = generateShippingPackage( {
					packageId: 0,
					shippingRates: [ pickupRate ],
				} );
				mockSelect.mockReturnValue( {
					getShippingRates: () => [ pkg ],
				} );
			} );

			it( 'returns false when defaultCheckoutTab setting is "shipping"', () => {
				getSetting.mockImplementation( ( key: string, fallback?: unknown ) => {
					if ( key === 'collectableMethodIds' ) return [ 'pickup_location' ];
					if ( key === 'defaultCheckoutTab' ) return 'shipping';
					return fallback;
				} );
				expect( prefersCollection( undefinedState ) ).toBe( false );
			} );

			it( 'returns false when defaultCheckoutTab setting is absent (fallback to "shipping")', () => {
				// Default mock: getSetting('defaultCheckoutTab', 'shipping') returns 'shipping' via fallback.
				expect( prefersCollection( undefinedState ) ).toBe( false );
			} );

			it( 'returns true when defaultCheckoutTab setting is "local_pickup"', () => {
				getSetting.mockImplementation( ( key: string, fallback?: unknown ) => {
					if ( key === 'collectableMethodIds' ) return [ 'pickup_location' ];
					if ( key === 'defaultCheckoutTab' ) return 'local_pickup';
					return fallback;
				} );
				expect( prefersCollection( undefinedState ) ).toBe( true );
			} );
		} );

		it( 'returns false when a regular shipping rate is pre-selected', () => {
			const pkg = generateShippingPackage( {
				packageId: 0,
				shippingRates: [ shippingRate ],
			} );
			mockSelect.mockReturnValue( { getShippingRates: () => [ pkg ] } );
			expect( prefersCollection( undefinedState ) ).toBe( false );
		} );
	} );
} );
