/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import fetchMock from 'jest-fetch-mock';

/**
 * Internal dependencies
 */
import { useStoreCartCoupons } from '../use-store-cart-coupons';

// Mock the resolvers to avoid actual API calls on cart data store setup.
jest.mock( '../../../../../data/cart/resolvers', () => {
	return {
		...jest.requireActual( '../../../../../data/cart/resolvers' ),
		getCartData: jest
			.fn()
			.mockResolvedValue(
				jest.requireActual( '@woocommerce/resource-previews' )
					.previewCart
			),
	};
} );

describe( 'useStoreCartCoupons hook API integration', () => {
	beforeEach( () => {
		fetchMock.enableMocks();
		fetchMock.resetMocks();
	} );

	afterEach( () => {
		fetchMock.disableMocks();
	} );

	describe( 'applyCoupon API calls', () => {
		it( 'calls the correct API endpoint when applying a coupon', async () => {
			// Mock a successful response
			fetchMock.mockResponseOnce(
				JSON.stringify( {
					responses: [
						{
							status: 200,
							body: {
								items: [],
								coupons: [
									{
										code: 'TEST5',
										discount_type: 'fixed_cart',
									},
								],
								totals: {
									total_price: '4500',
									total_discount: '500',
								},
								needs_shipping: false,
								shipping_address: {},
								billing_address: {},
							},
						},
					],
				} )
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// Apply a coupon
			await act( async () => {
				await result.current.applyCoupon( 'TEST5' );
			} );

			// Verify exactly one batch API call was made
			expect( fetchMock ).toHaveBeenCalledWith(
				expect.stringContaining( '/wc/store/v1/batch' ),
				expect.objectContaining( {
					method: 'POST',
					headers: expect.objectContaining( {
						'Content-Type': 'application/json',
					} ),
					body: expect.stringContaining( 'apply-coupon' ),
				} )
			);

			// // Verify the request body contains the coupon code
			const batchPayload = fetchMock.mock.calls[ 0 ][ 1 ] as RequestInit;
			const requestBody = batchPayload.body as string;
			const parsedBody = JSON.parse( requestBody );
			expect( parsedBody.requests ).toHaveLength( 1 );
			expect( parsedBody.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/apply-coupon'
			);
			expect( parsedBody.requests[ 0 ].method ).toBe( 'POST' );
			expect( parsedBody.requests[ 0 ].data.code ).toBe( 'TEST5' );
		} );

		it( 'calls API with correct data for multiple coupon types', async () => {
			const testCoupons = [
				'5fixedcheckout', // Fixed cart discount
				'50percoffcheckout', // Percentage discount
				'10fixedproductcheckout', // Fixed product discount
			];

			// Mock successful responses for each coupon
			fetchMock.mockResponses(
				[
					JSON.stringify( {
						responses: [ { status: 200, body: {} } ],
					} ),
					{ status: 200 },
				],
				[
					JSON.stringify( {
						responses: [ { status: 200, body: {} } ],
					} ),
					{ status: 200 },
				],
				[
					JSON.stringify( {
						responses: [ { status: 200, body: {} } ],
					} ),
					{ status: 200 },
				]
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// Apply each coupon and verify API calls
			for ( let i = 0; i < testCoupons.length; i++ ) {
				await act( async () => {
					await result.current.applyCoupon( testCoupons[ i ] );
				} );

				// Verify the API call for this coupon
				const callIndex = i;
				const requestBody = fetchMock.mock.calls?.[ callIndex ]?.[ 1 ]
					?.body as string;
				const parsedBody = JSON.parse( requestBody );

				expect( parsedBody.requests[ 0 ].path ).toBe(
					'/wc/store/v1/cart/apply-coupon'
				);
				expect( parsedBody.requests[ 0 ].method ).toBe( 'POST' );
				expect( parsedBody.requests[ 0 ].data.code ).toBe(
					testCoupons[ i ]
				);
			}

			expect( fetchMock ).toHaveBeenCalledTimes( 3 );
			expect( fetchMock ).toHaveBeenNthCalledWith(
				1,
				expect.stringContaining( '/wc/store/v1/batch' ),
				expect.objectContaining( {
					method: 'POST',
					body: expect.stringContaining( '5fixedcheckout' ),
				} )
			);
			expect( fetchMock ).toHaveBeenNthCalledWith(
				2,
				expect.stringContaining( '/wc/store/v1/batch' ),
				expect.objectContaining( {
					method: 'POST',
					body: expect.stringContaining( '50percoffcheckout' ),
				} )
			);
			expect( fetchMock ).toHaveBeenNthCalledWith(
				3,
				expect.stringContaining( '/wc/store/v1/batch' ),
				expect.objectContaining( {
					method: 'POST',
					body: expect.stringContaining( '10fixedproductcheckout' ),
				} )
			);
		} );

		it( 'handles API errors correctly without breaking', async () => {
			// Mock an error response
			fetchMock.mockRejectOnce( ( resolve, reject ) =>
				reject( {
					code: 'woocommerce_rest_cart_coupon_error',
					message: 'Coupon "INVALID" does not exist!',
					data: {
						status: 400,
						details: {
							cart: 'Coupon "INVALID" does not exist!',
						},
					},
				} )
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// Apply invalid coupon - should not throw error
			await act( async () => {
				const success = await result.current.applyCoupon( 'INVALID' );
				expect( success ).toBe( false );
			} );

			// Verify the API call was still made
			expect( fetchMock ).toHaveBeenCalledTimes( 1 );
			expect( fetchMock ).toHaveBeenCalledWith(
				expect.stringContaining( '/wc/store/v1/batch' ),
				expect.objectContaining( {
					method: 'POST',
					body: expect.stringContaining( 'INVALID' ),
				} )
			);
		} );

		it( 'includes cache control and proper headers', async () => {
			fetchMock.mockResponseOnce(
				JSON.stringify( {
					responses: [ { status: 200, body: {} } ],
				} )
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current.applyCoupon( 'CACHE_TEST' );
			} );

			// Verify proper headers are sent
			expect( fetchMock ).toHaveBeenCalledWith(
				expect.any( String ),
				expect.objectContaining( {
					method: 'POST',
					headers: expect.objectContaining( {
						'Content-Type': 'application/json',
						Accept: expect.stringContaining( 'application/json' ),
					} ),
					credentials: 'include',
				} )
			);
		} );

		it( 'formats batch request correctly for coupon application', async () => {
			fetchMock.mockResponseOnce(
				JSON.stringify( {
					responses: [ { status: 200, body: {} } ],
				} )
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current.applyCoupon( 'BATCH_TEST' );
			} );

			// Parse and verify the batch request structure
			const requestBody = fetchMock.mock?.calls?.[ 0 ]?.[ 1 ]
				?.body as string;
			const parsedBody = JSON.parse( requestBody );

			expect( parsedBody ).toEqual( {
				requests: [
					expect.objectContaining( {
						path: '/wc/store/v1/cart/apply-coupon',
						method: 'POST',
						data: { code: 'BATCH_TEST' },
						cache: 'no-store',
					} ),
				],
			} );
		} );
	} );

	describe( 'removeCoupon API calls', () => {
		it( 'calls the correct API endpoint when removing a coupon', async () => {
			fetchMock.mockResponseOnce(
				JSON.stringify( {
					responses: [ { status: 200, body: {} } ],
				} )
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current.removeCoupon( 'REMOVE_TEST' );
			} );

			// Verify the API call was made correctly for removal
			expect( fetchMock ).toHaveBeenCalledTimes( 1 );

			const requestBody = fetchMock.mock.calls?.[ 0 ]?.[ 1 ]
				?.body as string;
			const parsedBody = JSON.parse( requestBody );

			expect( parsedBody.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/remove-coupon'
			);
			expect( parsedBody.requests[ 0 ].method ).toBe( 'POST' );
			expect( parsedBody.requests[ 0 ].data.code ).toBe( 'REMOVE_TEST' );
		} );
	} );

	describe( 'Context-specific behavior', () => {
		it( 'works correctly with wc/checkout context', async () => {
			fetchMock.mockResponseOnce(
				JSON.stringify( {
					responses: [ { status: 200, body: {} } ],
				} )
			);

			// Test with checkout context specifically
			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current.applyCoupon( 'CHECKOUT_CONTEXT' );
			} );

			// Verify API call is made regardless of context
			expect( fetchMock ).toHaveBeenCalledTimes( 1 );

			const requestBody = fetchMock.mock?.calls?.[ 0 ]?.[ 1 ]
				?.body as string;
			const parsedBody = JSON.parse( requestBody );
			expect( parsedBody.requests[ 0 ].data.code ).toBe(
				'CHECKOUT_CONTEXT'
			);
		} );

		it( 'works correctly with different contexts', async () => {
			fetchMock.mockResponses(
				[
					JSON.stringify( {
						responses: [ { status: 200, body: {} } ],
					} ),
					{ status: 200 },
				],
				[
					JSON.stringify( {
						responses: [ { status: 200, body: {} } ],
					} ),
					{ status: 200 },
				]
			);

			// Test checkout context
			const { result: checkoutResult } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// Test cart context
			const { result: cartResult } = renderHook( () =>
				useStoreCartCoupons( 'wc/cart' )
			);

			await act( async () => {
				await checkoutResult.current.applyCoupon( 'CHECKOUT_COUPON' );
			} );

			await act( async () => {
				await cartResult.current.applyCoupon( 'CART_COUPON' );
			} );

			// Both should make the same API calls
			expect( fetchMock ).toHaveBeenCalledTimes( 2 );

			// Verify both calls have the same API structure
			const firstCall = JSON.parse(
				fetchMock.mock?.calls?.[ 0 ]?.[ 1 ]?.body as string
			);
			const secondCall = JSON.parse(
				fetchMock.mock?.calls?.[ 1 ]?.[ 1 ]?.body as string
			);

			expect( firstCall.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/apply-coupon'
			);
			expect( secondCall.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/apply-coupon'
			);
		} );
	} );

	describe( 'Edge cases', () => {
		it( 'handles special characters in coupon codes', async () => {
			fetchMock.mockResponseOnce(
				JSON.stringify( {
					responses: [ { status: 200, body: {} } ],
				} )
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			const specialCoupon = 'COUPON-WITH_SPECIAL@CHARS';
			await act( async () => {
				await result.current.applyCoupon( specialCoupon );
			} );

			const requestBody = fetchMock.mock?.calls?.[ 0 ]?.[ 1 ]
				?.body as string;
			const parsedBody = JSON.parse( requestBody );
			expect( parsedBody.requests[ 0 ].data.code ).toBe( specialCoupon );
		} );

		it( 'handles empty coupon codes gracefully', async () => {
			fetchMock.mockResponseOnce(
				JSON.stringify( {
					responses: [ { status: 200, body: {} } ],
				} )
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current.applyCoupon( '' );
			} );

			const requestBody = fetchMock.mock?.calls?.[ 0 ]?.[ 1 ]
				?.body as string;
			const parsedBody = JSON.parse( requestBody );
			expect( parsedBody.requests[ 0 ].data.code ).toBe( '' );
		} );

		it( 'handles network failures gracefully', async () => {
			// Mock network failure
			fetchMock.mockRejectOnce( new Error( 'Network error' ) );

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// Should not throw error even on network failure
			await act( async () => {
				const success = await result.current.applyCoupon(
					'NETWORK_FAIL'
				);
				expect( success ).toBe( false );
			} );

			expect( fetchMock ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
