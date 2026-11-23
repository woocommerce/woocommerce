/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createRegistry } from '@wordpress/data';
import type { CartShippingRate } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { store as cartStore } from '..';
import {
	generateShippingPackage,
	generateShippingRate,
} from '../../../../../assets/js/mocks/shipping-package';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: Object.assign( jest.fn(), {
		use: jest.fn(),
		setNonce: jest.fn(),
		setCartHash: jest.fn(),
	} ),
} ) );

const createPackages = ( selectedRateIds: string[] ) =>
	selectedRateIds.map( ( selectedRateId, packageId ) =>
		generateShippingPackage( {
			packageId,
			shippingRates: [
				generateShippingRate( {
					rateId: 'flat_rate:1',
					name: 'Flat rate',
					price: '1250',
					instanceID: 1,
					selected: selectedRateId === 'flat_rate:1',
				} ),
				generateShippingRate( {
					rateId: 'pickup_location:0',
					name: 'Pickup location',
					price: '0',
					instanceID: 0,
					selected: selectedRateId === 'pickup_location:0',
				} ),
			],
		} )
	);

const rejectedSelection = {
	code: 'woocommerce_rest_cart_shipping_rate_invalid',
	message: 'Shipping rate rejected.',
	data: { status: 400 },
};

describe( 'batched shipping rate selection', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		jest.mocked( apiFetch ).mockReset();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'preserves successful packages when the middle selection fails', async () => {
		const registry = createRegistry();
		registry.register( cartStore );
		const dispatch = registry.dispatch( cartStore );
		const select = registry.select( cartStore );
		dispatch.finishResolution( 'getCartData' );
		dispatch.setCartData( {
			shippingRates: createPackages( [
				'pickup_location:0',
				'pickup_location:0',
				'pickup_location:0',
			] ),
		} );
		jest.mocked( apiFetch ).mockResolvedValueOnce( {
			responses: [
				{
					status: 200,
					headers: {},
					body: {
						shipping_rates: createPackages( [
							'flat_rate:1',
							'pickup_location:0',
							'pickup_location:0',
						] ),
					},
				},
				{ status: 400, headers: {}, body: rejectedSelection },
				{
					status: 200,
					headers: {},
					body: {
						shipping_rates: createPackages( [
							'flat_rate:1',
							'pickup_location:0',
							'flat_rate:1',
						] ),
						totals: { total_shipping: '2500', total_price: '5500' },
					},
				},
			],
		} );

		const selections = Promise.allSettled(
			[ 0, 1, 2 ].map( ( packageId ) =>
				dispatch.selectShippingRate( 'flat_rate:1', packageId )
			)
		);
		await jest.runOnlyPendingTimersAsync();
		const results = await selections;

		expect( results.map( ( result ) => result.status ) ).toEqual( [
			'fulfilled',
			'rejected',
			'fulfilled',
		] );
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: '/wc/store/v1/batch',
				data: {
					requests: [ 0, 1, 2 ].map( ( packageId ) =>
						expect.objectContaining( {
							path: '/wc/store/v1/cart/select-shipping-rate',
							body: {
								package_id: packageId,
								rate_id: 'flat_rate:1',
							},
						} )
					),
				},
			} )
		);
		expect(
			select
				.getShippingRates()
				.map(
					( shippingPackage: CartShippingRate ) =>
						shippingPackage.shipping_rates.find(
							( rate ) => rate.selected
						)?.rate_id
				)
		).toEqual( [ 'flat_rate:1', 'pickup_location:0', 'flat_rate:1' ] );
		expect( select.getCartTotals() ).toMatchObject( {
			total_shipping: '2500',
			total_price: '5500',
		} );
		expect( select.isShippingRateBeingSelected() ).toBe( false );
		expect( select.getCartErrors() ).toContainEqual( rejectedSelection );
	} );
	it( 'restores all packages when a shared pickup selection fails', async () => {
		const registry = createRegistry();
		registry.register( cartStore );
		const dispatch = registry.dispatch( cartStore );
		const select = registry.select( cartStore );
		dispatch.finishResolution( 'getCartData' );
		const originalRates = createPackages( [
			'flat_rate:1',
			'flat_rate:1',
			'flat_rate:1',
		] );
		dispatch.setCartData( { shippingRates: originalRates } );
		jest.mocked( apiFetch ).mockResolvedValueOnce( {
			responses: [
				{ status: 400, headers: {}, body: rejectedSelection },
			],
		} );

		const selections = Promise.allSettled( [
			dispatch.selectShippingRate( 'pickup_location:0', null ),
		] );
		await jest.runOnlyPendingTimersAsync();
		expect( await selections ).toEqual( [
			{ status: 'rejected', reason: rejectedSelection },
		] );

		expect( select.getShippingRates() ).toEqual( originalRates );
		expect( select.isShippingRateBeingSelected() ).toBe( false );
	} );
} );
