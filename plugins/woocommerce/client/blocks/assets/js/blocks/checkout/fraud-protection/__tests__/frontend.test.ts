/**
 * @jest-environment jsdom
 */

/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';

// Mock WordPress dependencies
jest.mock( '@wordpress/hooks', () => ( {
	addAction: jest.fn(),
} ) );

jest.mock( '@wordpress/api-fetch' );

const mockAddAction = addAction as jest.MockedFunction< typeof addAction >;
const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'Fraud Protection Frontend', () => {
	beforeEach( () => {
		// Clear mocks before each test
		jest.clearAllMocks();
		jest.resetModules();

		// Clear window params
		delete (
			window as Window & { wc_fraud_protection_blocks_params?: unknown }
		 ).wc_fraud_protection_blocks_params;
	} );

	describe( 'Initialization', () => {
		it( 'should not register hook when settings are not defined', () => {
			// Re-import to trigger initialization
			jest.isolateModules( () => {
				require( '../frontend' );
			} );

			// Verify addAction was not called
			expect( mockAddAction ).not.toHaveBeenCalled();
		} );

		it( 'should not register hook when feature is disabled', () => {
			// Set disabled settings
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: false,
			};

			// Re-import to trigger initialization
			jest.isolateModules( () => {
				require( '../frontend' );
			} );

			// Verify addAction was not called
			expect( mockAddAction ).not.toHaveBeenCalled();
		} );

		it( 'should register hook when feature is enabled', () => {
			// Set enabled settings
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: true,
			};

			// Re-import to trigger initialization
			jest.isolateModules( () => {
				require( '../frontend' );
			} );

			// Verify addAction was called with correct parameters
			expect( mockAddAction ).toHaveBeenCalledWith(
				'experimental__woocommerce_blocks-checkout-set-active-payment-method',
				'woocommerce-fraud-protection',
				expect.any( Function )
			);
		} );

		it( 'should register handler function when enabled', () => {
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: true,
			};

			jest.isolateModules( () => {
				require( '../frontend' );
			} );

			// Verify a function was registered as the handler
			const registeredHandler = mockAddAction.mock.calls[ 0 ]?.[ 2 ];
			expect( typeof registeredHandler ).toBe( 'function' );
		} );
	} );

	describe( 'Payment Method Handler Integration', () => {
		it( 'should call apiFetch when handler is invoked with valid payment method', async () => {
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: true,
			};

			mockApiFetch.mockResolvedValue( {} );

			let registeredHandler: ( ( data: unknown ) => void ) | undefined;

			jest.isolateModules( () => {
				require( '../frontend' );
				registeredHandler = mockAddAction.mock.calls[ 0 ]?.[ 2 ] as (
					data: unknown
				) => void;
			} );

			// Assert handler is defined
			expect( registeredHandler ).toBeDefined();

			// Call the registered handler
			await registeredHandler!( {
				paymentMethodSlug: 'stripe',
			} );

			// Verify API was called
			expect( mockApiFetch ).toHaveBeenCalledWith( {
				path: '/wc/store/v1/fraud-protection/payment-method-selected',
				method: 'POST',
				data: {
					payment_method: 'stripe',
				},
			} );
		} );

		it( 'should not call apiFetch when payment method is empty', async () => {
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: true,
			};

			let registeredHandler: ( ( data: unknown ) => void ) | undefined;

			jest.isolateModules( () => {
				require( '../frontend' );
				registeredHandler = mockAddAction.mock.calls[ 0 ]?.[ 2 ] as (
					data: unknown
				) => void;
			} );

			// Assert handler is defined
			expect( registeredHandler ).toBeDefined();

			await registeredHandler!( {
				paymentMethodSlug: '',
			} );

			expect( mockApiFetch ).not.toHaveBeenCalled();
		} );

		it( 'should handle API errors gracefully without throwing', async () => {
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: true,
			};

			const consoleErrorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation();

			mockApiFetch.mockRejectedValue( new Error( 'API Error' ) );

			let registeredHandler: ( ( data: unknown ) => void ) | undefined;

			jest.isolateModules( () => {
				require( '../frontend' );
				registeredHandler = mockAddAction.mock.calls[ 0 ]?.[ 2 ] as (
					data: unknown
				) => void;
			} );

			// Assert handler is defined
			expect( registeredHandler ).toBeDefined();

			// Should not throw
			await expect(
				registeredHandler!( {
					paymentMethodSlug: 'stripe',
				} )
			).resolves.not.toThrow();

			// Error should be logged
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				'Fraud protection tracking error:',
				expect.any( Error )
			);

			consoleErrorSpy.mockRestore();
		} );

		it( 'should send correct data for different payment methods', async () => {
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: true,
			};

			mockApiFetch.mockResolvedValue( {} );

			const paymentMethods = [ 'stripe', 'paypal', 'cod' ];

			for ( const method of paymentMethods ) {
				jest.clearAllMocks();

				let registeredHandler:
					| ( ( data: unknown ) => void )
					| undefined;

				jest.isolateModules( () => {
					require( '../frontend' );
					registeredHandler = mockAddAction.mock
						.calls[ 0 ]?.[ 2 ] as ( data: unknown ) => void;
				} );

				// Assert handler is defined
				expect( registeredHandler ).toBeDefined();

				await registeredHandler!( {
					paymentMethodSlug: method,
				} );

				expect( mockApiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						data: {
							payment_method: method,
						},
					} )
				);
			}
		} );
	} );

	describe( 'API Endpoint and Method', () => {
		it( 'should use correct Store API endpoint', async () => {
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: true,
			};

			mockApiFetch.mockResolvedValue( {} );

			let registeredHandler: ( ( data: unknown ) => void ) | undefined;

			jest.isolateModules( () => {
				require( '../frontend' );
				registeredHandler = mockAddAction.mock.calls[ 0 ]?.[ 2 ] as (
					data: unknown
				) => void;
			} );

			// Assert handler is defined
			expect( registeredHandler ).toBeDefined();

			await registeredHandler!( {
				paymentMethodSlug: 'stripe',
			} );

			expect( mockApiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					path: '/wc/store/v1/fraud-protection/payment-method-selected',
				} )
			);
		} );

		it( 'should use POST method', async () => {
			(
				window as Window & {
					wc_fraud_protection_blocks_params?: unknown;
				}
			 ).wc_fraud_protection_blocks_params = {
				enabled: true,
			};

			mockApiFetch.mockResolvedValue( {} );

			let registeredHandler: ( ( data: unknown ) => void ) | undefined;

			jest.isolateModules( () => {
				require( '../frontend' );
				registeredHandler = mockAddAction.mock.calls[ 0 ]?.[ 2 ] as (
					data: unknown
				) => void;
			} );

			// Assert handler is defined
			expect( registeredHandler ).toBeDefined();

			await registeredHandler!( {
				paymentMethodSlug: 'stripe',
			} );

			expect( mockApiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					method: 'POST',
				} )
			);
		} );
	} );
} );
