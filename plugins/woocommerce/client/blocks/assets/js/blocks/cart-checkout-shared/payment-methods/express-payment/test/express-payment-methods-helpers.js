/**
 * Internal dependencies
 */
import { getExpressPaymentMethodsState } from '../express-payment-methods-helpers';

describe( 'getExpressPaymentMethodsState', () => {
	describe( 'when no methods are provided', () => {
		it( 'should return default values for empty state', () => {
			const result = getExpressPaymentMethodsState( {} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( false );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 2 );
		} );

		it( 'should handle undefined parameters gracefully', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: undefined,
				expressPaymentMethodsInitialized: undefined,
				registeredExpressPaymentMethods: undefined,
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( false );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 2 );
		} );

		it( 'should handle empty parameters gracefully', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: false,
				registeredExpressPaymentMethods: {},
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( false );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 2 );
		} );
	} );

	describe( 'when methods are registered but not initialized', () => {
		it( 'should indicate methods are registered but not initialized', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: false,
				registeredExpressPaymentMethods: { stripe: {}, paypal: {} },
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( true );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( true );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 2 ); // Default fallback
		} );

		it( 'should handle single registered method not initialized', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: false,
				registeredExpressPaymentMethods: { stripe: {} },
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( true );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( true );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 2 ); // Default fallback
		} );
	} );

	describe( 'when methods are initialized but none are available', () => {
		it( 'should indicate no valid registered methods', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: true,
				registeredExpressPaymentMethods: { stripe: {}, paypal: {} },
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( true );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				true
			);
			expect( result.availableExpressPaymentsCount ).toBe( 2 ); // Default fallback
		} );
	} );

	describe( 'when methods are available and initialized', () => {
		it( 'should return correct state for available methods', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: { stripe: {}, paypal: {} },
				expressPaymentMethodsInitialized: true,
				registeredExpressPaymentMethods: { stripe: {}, paypal: {} },
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( true );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 2 );
		} );

		it( 'should handle single available method', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: { stripe: {} },
				expressPaymentMethodsInitialized: true,
				registeredExpressPaymentMethods: { stripe: {} },
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( true );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 1 );
		} );

		it( 'should handle multiple available methods', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: {
					stripe: {},
					paypal: {},
					applepay: {},
				},
				expressPaymentMethodsInitialized: true,
				registeredExpressPaymentMethods: {
					stripe: {},
					paypal: {},
					applepay: {},
				},
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( true );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 3 );
		} );
	} );

	describe( 'availableExpressPaymentsCount fallback behavior', () => {
		it( 'should return default count of 2 when no methods are available', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: true,
				registeredExpressPaymentMethods: { stripe: {} },
			} );

			expect( result.availableExpressPaymentsCount ).toBe( 2 );
		} );

		it( 'should return actual count when methods are available', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: {
					stripe: {},
					paypal: {},
					applepay: {},
					googlepay: {},
				},
				expressPaymentMethodsInitialized: true,
				registeredExpressPaymentMethods: {
					stripe: {},
					paypal: {},
					applepay: {},
					googlepay: {},
				},
			} );

			expect( result.availableExpressPaymentsCount ).toBe( 4 );
		} );
	} );

	describe( 'edge cases', () => {
		it( 'should handle mismatch between registered and available methods', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: { stripe: {} },
				expressPaymentMethodsInitialized: true,
				registeredExpressPaymentMethods: {
					stripe: {},
					paypal: {},
					applepay: {},
				},
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( true );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 1 );
		} );

		it( 'should handle expressPaymentMethodsInitialized true with no registered methods', () => {
			const result = getExpressPaymentMethodsState( {
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: true,
				registeredExpressPaymentMethods: {},
			} );

			expect( result.hasRegisteredExpressPaymentMethods ).toBe( false );
			expect(
				result.hasRegisteredNotInitializedExpressPaymentMethods
			).toBe( false );
			expect( result.hasNoValidRegisteredExpressPaymentMethods ).toBe(
				false
			);
			expect( result.availableExpressPaymentsCount ).toBe( 2 );
		} );
	} );
} );
