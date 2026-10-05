import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import type { GlobalPaymentMethod } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { PaymentState } from '../default-state';
import { SavedPaymentMethod } from '../types';

// Helper function to get a fresh instance of defaultPaymentState for each test
const getDefaultPaymentStateWithMocks = async ( {
	isEditorMode = false,
	checkoutData = {
		payment_method: 'stripe',
	},
	customerPaymentMethods = {},
	globalPaymentMethods = [],
}: {
	isEditorMode?: boolean;
	checkoutData?: {
		payment_method: string;
	};
	customerPaymentMethods?: Record< string, SavedPaymentMethod[] >;
	globalPaymentMethods?: GlobalPaymentMethod[];
} = {} ): PaymentState => {
	let state: PaymentState | undefined;

	// IsolateModules is used to ensure that the module is not cached between tests
	vi.resetModules(),
		await ( async () => {
			// Set up mocks before requiring the module
			// Using doMock as calls to `mock` are hoisted to the top of the file
			vi.doMock( '../../utils', () => {
				const mock = {
					isEditor: () => isEditorMode,
				};
				return Object.defineProperties(
					{
						default: mock,
					},
					Object.getOwnPropertyDescriptors( mock )
				);
			} );
			vi.doMock( '../../checkout/constants', () => {
				const mock = {
					checkoutData,
				};
				return Object.defineProperties(
					{
						default: mock,
					},
					Object.getOwnPropertyDescriptors( mock )
				);
			} );
			vi.doMock( '@woocommerce/settings', () => {
				const mock = {
					getSetting: ( setting: string ) => {
						switch ( setting ) {
							case 'globalPaymentMethods':
								return globalPaymentMethods;
							case 'customerPaymentMethods':
								return customerPaymentMethods;
							default:
								return {};
						}
					},
				};
				return Object.defineProperties(
					{
						default: mock,
					},
					Object.getOwnPropertyDescriptors( mock )
				);
			} );

			// Get a fresh copy of the state
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Cloning using structuredClone is not supported in jsdom and Object.assign won't work as the state contains objects that need to be reset too. This is a clean way to get a fresh copy of the state.
			state = ( await import( '../default-state' ) ).defaultPaymentState;
		} )();

	// TypeScript needs this check, but isolateModules will always set the state
	if ( ! state ) {
		throw new Error( 'Failed to initialize state' );
	}
	return state;
};
describe( 'defaultPaymentState', () => {
	describe( 'Initial state', () => {
		it( 'should initialize with correct default values', async () => {
			const state = await getDefaultPaymentStateWithMocks();
			expect( state ).toEqual( {
				status: 'idle',
				activePaymentMethod: 'stripe',
				availablePaymentMethods: {},
				availableExpressPaymentMethods: {},
				registeredExpressPaymentMethods: {},
				savedPaymentMethods: {},
				paymentMethodData: {},
				paymentResult: null,
				paymentMethodsInitialized: false,
				expressPaymentMethodsInitialized: false,
				shouldSavePaymentMethod: false,
			} );
		} );
	} );
	describe( 'In editor mode, activePaymentMethod', () => {
		it( 'should be an empty string when no global payment methods exist', async () => {
			const state = await getDefaultPaymentStateWithMocks( {
				isEditorMode: true,
				globalPaymentMethods: [],
			} );
			expect( state.activePaymentMethod ).toBe( '' );
		} );
		it( 'should be equal to the first payment method id from globalPaymentMethods when payment methods exist', async () => {
			const mockGlobalPaymentMethods: GlobalPaymentMethod[] = [
				{
					id: 'stripe',
					title: 'Stripe',
					description: 'Pay with Stripe',
				},
				{
					id: 'paypal',
					title: 'PayPal',
					description: 'Pay with PayPal',
				},
			];
			const state = await getDefaultPaymentStateWithMocks( {
				isEditorMode: true,
				globalPaymentMethods: mockGlobalPaymentMethods,
			} );
			expect( state.activePaymentMethod ).toBe( 'stripe' );
		} );
	} );
	describe( 'Frontend', () => {
		it( 'should set activePaymentMethod to value from checkoutData.payment_method', async () => {
			const state = await getDefaultPaymentStateWithMocks( {
				checkoutData: {
					payment_method: 'stripe',
				},
			} );
			expect( state.activePaymentMethod ).toBe( 'stripe' );
		} );
		describe( 'Payment method data', () => {
			it( 'should be empty when no default payment method exists', async () => {
				const state = await getDefaultPaymentStateWithMocks( {
					checkoutData: {
						payment_method: '',
					},
					customerPaymentMethods: {},
					globalPaymentMethods: [],
				} );
				expect( state.paymentMethodData ).toEqual( {} );
			} );
			it( 'should be empty when default payment method does not match saved methods', async () => {
				const mockSavedPaymentMethods: Record<
					string,
					SavedPaymentMethod[]
				> = {
					cc: [
						{
							method: {
								gateway: 'stripe',
								brand: 'visa',
								last4: '1234',
							},
							tokenId: 123,
							is_default: true,
							expires: '10/99',
							actions: {
								default: {
									name: 'Delete',
									url: 'https://example.com',
								},
							},
						},
					],
				};
				const state = await getDefaultPaymentStateWithMocks( {
					checkoutData: {
						payment_method: 'paypal',
					},
					customerPaymentMethods: mockSavedPaymentMethods,
				} );
				expect( state.paymentMethodData ).toEqual( {} );
			} );
			it( 'should be equal to the payment method data for the default payment method', async () => {
				const customerPaymentMethods: Record<
					string,
					SavedPaymentMethod[]
				> = {
					cc: [
						{
							method: {
								gateway: 'stripe',
								brand: 'visa',
								last4: '1234',
							},
							tokenId: 123,
							is_default: true,
							expires: '10/99',
							actions: {
								default: {
									name: 'Delete',
									url: 'https://example.com',
								},
							},
						},
					],
				};
				const state = await getDefaultPaymentStateWithMocks( {
					checkoutData: {
						payment_method: 'stripe',
					},
					customerPaymentMethods,
				} );
				expect( state.paymentMethodData ).toEqual( {
					token: '123',
					payment_method: 'stripe',
					'wc-stripe-payment-token': '123',
				} );
			} );
		} );
		describe( 'Saved payment methods', () => {
			it( 'should handle saved payment methods correctly', async () => {
				const mockSavedPaymentMethods: Record<
					string,
					SavedPaymentMethod[]
				> = {
					cc: [
						{
							method: {
								gateway: 'stripe',
								brand: 'visa',
								last4: '1234',
							},
							tokenId: 123,
							is_default: true,
							expires: '10/99',
							actions: {
								default: {
									name: 'Delete',
									url: 'https://example.com',
								},
							},
						},
					],
				};
				const state = await getDefaultPaymentStateWithMocks( {
					customerPaymentMethods: mockSavedPaymentMethods,
				} );
				expect( state.savedPaymentMethods ).toEqual(
					mockSavedPaymentMethods
				);
			} );
		} );
	} );
} );
