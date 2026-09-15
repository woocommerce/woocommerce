/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { select } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import { server, http, HttpResponse } from '@woocommerce/test-utils/msw';

/**
 * Internal dependencies
 */
import { useStoreCartCoupons } from '../use-store-cart-coupons';

// Mock the resolvers to avoid actual API calls on cart data store setup.
jest.mock( '@woocommerce/block-data/cart/resolvers', () => {
	return {
		...jest.requireActual( '@woocommerce/block-data/cart/resolvers' ),
		getCartData: jest
			.fn()
			.mockResolvedValue(
				jest.requireActual( '@woocommerce/resource-previews' )
					.previewCart
			),
	};
} );

// The stubbed batch responses below are not complete Store API responses, so
// applyCoupon rejects after the request has been captured. Where only the
// request is under test, the rejection is ignored.
type CapturedRequest = {
	url: string;
	method: string;
	contentType?: string;
	body: string;
	headers?: Record< string, string >;
};

describe( 'useStoreCartCoupons hook API integration', () => {
	beforeEach( () => {
		// Reset any request handlers that were added in individual tests
		server.resetHandlers();
	} );

	describe( 'applyCoupon API calls', () => {
		it( 'calls the correct API endpoint when applying a coupon', async () => {
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			// Mock a successful response using MSW
			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						capturedRequest = {
							url: request.url,
							method: request.method,
							contentType: request.headers.get( 'content-type' ),
							body: await request.text(),
						};

						return HttpResponse.json( {
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
						} );
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// Apply a coupon
			await act( async () => {
				await result.current
					.applyCoupon( 'TEST5' )
					.catch( () => undefined );
			} );

			// Verify the request was made
			expect( capturedRequest ).not.toBeNull();
			expect( capturedRequest.url ).toContain( '/wc/store/v1/batch' );
			expect( capturedRequest.method ).toBe( 'POST' );
			expect( capturedRequest.contentType ).toContain(
				'application/json'
			);
			expect( capturedRequest.body ).toContain( 'apply-coupon' );

			// Verify the request body contains the coupon code
			const parsedBody = JSON.parse( capturedRequest.body );
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

			// Track the requests
			const capturedRequests: CapturedRequest[] = [];

			// Mock successful responses for each coupon using MSW
			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						const requestData = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};
						capturedRequests.push( requestData );

						return HttpResponse.json( {
							responses: [ { status: 200, body: {} } ],
						} );
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// Apply each coupon and verify API calls
			for ( let i = 0; i < testCoupons.length; i++ ) {
				await act( async () => {
					await result.current
						.applyCoupon( testCoupons[ i ] )
						.catch( () => undefined );
				} );

				// Verify the API call for this coupon
				const requestData = capturedRequests[ i ];
				expect( requestData ).toBeDefined();

				const parsedBody = JSON.parse( requestData.body );
				expect( parsedBody.requests[ 0 ].path ).toBe(
					'/wc/store/v1/cart/apply-coupon'
				);
				expect( parsedBody.requests[ 0 ].method ).toBe( 'POST' );
				expect( parsedBody.requests[ 0 ].data.code ).toBe(
					testCoupons[ i ]
				);
			}

			// Verify that all 3 requests were captured
			expect( capturedRequests ).toHaveLength( 3 );

			// Verify specific coupon codes in requests
			expect( capturedRequests[ 0 ].body ).toContain( '5fixedcheckout' );
			expect( capturedRequests[ 1 ].body ).toContain(
				'50percoffcheckout'
			);
			expect( capturedRequests[ 2 ].body ).toContain(
				'10fixedproductcheckout'
			);

			// Verify all requests are POST to the correct endpoint
			capturedRequests.forEach( ( request ) => {
				expect( request.url ).toContain( '/wc/store/v1/batch' );
				expect( request.method ).toBe( 'POST' );
			} );
		} );

		it( 'rejects with the server message and leaves the validation store empty', async () => {
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			// Mock an error response using MSW
			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						capturedRequest = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};

						return HttpResponse.json(
							{
								code: 'woocommerce_rest_cart_coupon_error',
								message: 'Coupon "INVALID" does not exist!',
								data: {
									status: 400,
									details: {
										cart: 'Coupon "INVALID" does not exist!',
									},
								},
							},
							{ status: 400 }
						);
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// The failure is reported to the caller, not to the validation store.
			await act( async () => {
				await expect(
					result.current.applyCoupon( 'INVALID' )
				).rejects.toThrow( 'Coupon "INVALID" does not exist!' );
			} );
			expect( select( validationStore ).hasValidationErrors() ).toBe(
				false
			);

			// Verify the API call was still made
			expect( capturedRequest ).not.toBeNull();
			expect( capturedRequest.url ).toContain( '/wc/store/v1/batch' );
			expect( capturedRequest.method ).toBe( 'POST' );
			expect( capturedRequest.body ).toContain( 'INVALID' );
		} );

		it( 'includes cache control and proper headers', async () => {
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						const headers: Record< string, string > = {};
						request.headers.forEach( ( value, key ) => {
							headers[ key ] = value;
						} );
						capturedRequest = {
							url: request.url,
							method: request.method,
							headers,
							body: await request.text(),
						};

						return HttpResponse.json( {
							responses: [ { status: 200, body: {} } ],
						} );
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current
					.applyCoupon( 'CACHE_TEST' )
					.catch( () => undefined );
			} );

			// Verify proper headers are sent
			expect( capturedRequest ).not.toBeNull();
			expect( capturedRequest.method ).toBe( 'POST' );
			expect( capturedRequest.headers?.[ 'content-type' ] ).toContain(
				'application/json'
			);
			expect( capturedRequest.headers?.accept ).toContain(
				'application/json'
			);
		} );

		it( 'formats batch request correctly for coupon application', async () => {
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						capturedRequest = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};

						return HttpResponse.json( {
							responses: [ { status: 200, body: {} } ],
						} );
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current
					.applyCoupon( 'BATCH_TEST' )
					.catch( () => undefined );
			} );

			// Parse and verify the batch request structure
			expect( capturedRequest ).not.toBeNull();
			const parsedBody = JSON.parse( capturedRequest.body );

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
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						capturedRequest = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};

						return HttpResponse.json( {
							responses: [ { status: 200, body: {} } ],
						} );
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current.removeCoupon( 'REMOVE_TEST' );
			} );

			// Verify the API call was made correctly for removal
			expect( capturedRequest ).not.toBeNull();
			expect( capturedRequest.url ).toContain( '/wc/store/v1/batch' );

			const parsedBody = JSON.parse( capturedRequest.body );
			expect( parsedBody.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/remove-coupon'
			);
			expect( parsedBody.requests[ 0 ].method ).toBe( 'POST' );
			expect( parsedBody.requests[ 0 ].data.code ).toBe( 'REMOVE_TEST' );
		} );
	} );

	describe( 'Context-specific behavior', () => {
		it( 'works correctly with wc/checkout context', async () => {
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						capturedRequest = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};

						return HttpResponse.json( {
							responses: [ { status: 200, body: {} } ],
						} );
					}
				)
			);

			// Test with checkout context specifically
			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current
					.applyCoupon( 'CHECKOUT_CONTEXT' )
					.catch( () => undefined );
			} );

			// Verify API call is made regardless of context
			expect( capturedRequest ).not.toBeNull();
			expect( capturedRequest.url ).toContain( '/wc/store/v1/batch' );

			const parsedBody = JSON.parse( capturedRequest.body );
			expect( parsedBody.requests[ 0 ].data.code ).toBe(
				'CHECKOUT_CONTEXT'
			);
		} );

		it( 'works correctly with different contexts', async () => {
			// Track the request details
			const capturedRequests: CapturedRequest[] = [];

			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						const requestData = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};
						capturedRequests.push( requestData );

						return HttpResponse.json( {
							responses: [ { status: 200, body: {} } ],
						} );
					}
				)
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
				await checkoutResult.current
					.applyCoupon( 'CHECKOUT_COUPON' )
					.catch( () => undefined );
			} );

			await act( async () => {
				await cartResult.current
					.applyCoupon( 'CART_COUPON' )
					.catch( () => undefined );
			} );

			// Both should make the same API calls
			expect( capturedRequests ).toHaveLength( 2 );

			// Verify both calls have the same API structure
			const firstCall = JSON.parse( capturedRequests[ 0 ].body );
			const secondCall = JSON.parse( capturedRequests[ 1 ].body );

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
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						capturedRequest = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};

						return HttpResponse.json( {
							responses: [ { status: 200, body: {} } ],
						} );
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			const specialCoupon = 'COUPON-WITH_SPECIAL@CHARS';
			await act( async () => {
				await result.current
					.applyCoupon( specialCoupon )
					.catch( () => undefined );
			} );

			expect( capturedRequest ).not.toBeNull();
			const parsedBody = JSON.parse( capturedRequest.body );
			expect( parsedBody.requests[ 0 ].data.code ).toBe( specialCoupon );
		} );

		it( 'handles empty coupon codes gracefully', async () => {
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						capturedRequest = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};

						return HttpResponse.json( {
							responses: [ { status: 200, body: {} } ],
						} );
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			await act( async () => {
				await result.current.applyCoupon( '' ).catch( () => undefined );
			} );

			expect( capturedRequest ).not.toBeNull();
			const parsedBody = JSON.parse( capturedRequest.body );
			expect( parsedBody.requests[ 0 ].data.code ).toBe( '' );
		} );

		it( 'handles network failures gracefully', async () => {
			// Track the request details
			let capturedRequest: CapturedRequest = {
				url: '',
				method: '',
				body: '',
			};

			// Mock network failure using MSW
			server.use(
				http.post(
					'/wc/store/v1/batch',
					async ( { request }: { request: Request } ) => {
						capturedRequest = {
							url: request.url,
							method: request.method,
							body: await request.text(),
						};

						return HttpResponse.error();
					}
				)
			);

			const { result } = renderHook( () =>
				useStoreCartCoupons( 'wc/checkout' )
			);

			// A network failure is reported the same way as a rejected coupon.
			await act( async () => {
				await expect(
					result.current.applyCoupon( 'NETWORK_FAIL' )
				).rejects.toThrow();
			} );
			expect( select( validationStore ).hasValidationErrors() ).toBe(
				false
			);

			expect( capturedRequest ).not.toBeNull();
			expect( capturedRequest.url ).toContain( '/wc/store/v1/batch' );
		} );
	} );
} );
