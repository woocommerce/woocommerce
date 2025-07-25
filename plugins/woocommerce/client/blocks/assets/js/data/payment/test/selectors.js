/**
 * External dependencies
 */
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { previewCart } from '@woocommerce/resource-previews';
import * as wpDataFunctions from '@wordpress/data';
import {
	CART_STORE_KEY as storeKey,
	paymentStore,
} from '@woocommerce/block-data';
import {
	registerPaymentMethod,
	registerExpressPaymentMethod,
	__experimentalDeRegisterPaymentMethod,
	__experimentalDeRegisterExpressPaymentMethod,
	getExpressPaymentMethods,
} from '@woocommerce/blocks-registry';
import { server, http, HttpResponse } from '@woocommerce/test-utils/msw';

/**
 * Internal dependencies
 */
import {
	CheckoutExpressPayment,
	SavedPaymentMethodOptions,
} from '../../../blocks/cart-checkout-shared/payment-methods';
import { defaultCartState } from '../../cart/default-state';
import { getRegisteredExpressPaymentMethods } from '../selectors';

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...originalModule,
		select: jest.fn( ( storeName ) => {
			const originalStore = originalModule.select( storeName );
			if ( storeName === 'wc/store/cart' ) {
				return {
					...originalStore,
					hasFinishedResolution: jest.fn( ( selectorName ) => {
						if ( selectorName === 'getCartTotals' ) {
							return true;
						}
						return originalStore.hasFinishedResolution(
							selectorName
						);
					} ),
				};
			}
			return originalStore;
		} ),
	};
} );

jest.mock( '@woocommerce/settings', () => {
	const originalModule = jest.requireActual( '@woocommerce/settings' );

	return {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore We know @woocommerce/settings is an object.
		...originalModule,
		getSetting: ( setting, ...rest ) => {
			if ( setting === 'customerPaymentMethods' ) {
				return {
					cc: [
						{
							method: {
								gateway: 'credit-card',
								last4: '4242',
								brand: 'Visa',
							},
							expires: '12/22',
							is_default: true,
							tokenId: 1,
						},
					],
				};
			}
			return originalModule.getSetting( setting, ...rest );
		},
	};
} );

const registerMockPaymentMethods = ( savedCards = true ) => {
	[ 'cheque', 'bacs' ].forEach( ( name ) => {
		registerPaymentMethod( {
			name,
			label: name,
			content: <div>A payment method</div>,
			edit: <div>A payment method</div>,
			icons: null,
			canMakePayment: () => true,
			supports: {
				features: [ 'products' ],
			},
			ariaLabel: name,
		} );
	} );
	[ 'credit-card' ].forEach( ( name ) => {
		registerPaymentMethod( {
			name,
			label: name,
			content: <div>A payment method</div>,
			edit: <div>A payment method</div>,
			icons: null,
			canMakePayment: () => true,
			supports: {
				showSavedCards: savedCards,
				showSaveOption: true,
				features: [ 'products' ],
			},
			ariaLabel: name,
		} );
	} );
	[ 'express-payment' ].forEach( ( name ) => {
		const Content = ( {
			onClose = () => void null,
			onClick = () => void null,
		} ) => {
			return (
				<>
					<button onClick={ onClick }>
						{ name + ' express payment method' }
					</button>
					<button onClick={ onClose }>
						{ name + ' express payment method close' }
					</button>
				</>
			);
		};
		registerExpressPaymentMethod( {
			name,
			title: `${ name } express payment method`,
			description: `${ name } express payment method description`,
			gatewayId: 'woo',
			content: <Content />,
			edit: <div>An express payment method</div>,
			canMakePayment: () => true,
			paymentMethodId: name,
			supports: {
				features: [ 'products' ],
			},
		} );
	} );
	wpDataFunctions
		.dispatch( paymentStore )
		.__internalUpdateAvailablePaymentMethods();

	// Set registered express payment methods
	const registeredExpressMethods = getExpressPaymentMethods();
	const plainRegisteredMethods = {};
	Object.keys( registeredExpressMethods ).forEach( ( methodName ) => {
		const method = registeredExpressMethods[ methodName ];
		plainRegisteredMethods[ methodName ] = {
			name: method.name,
			title: method.title,
			description: method.description,
			gatewayId: method.gatewayId,
			supportsStyle: method.supports?.style || [],
		};
	} );
	wpDataFunctions
		.dispatch( paymentStore )
		.__internalSetRegisteredExpressPaymentMethods( plainRegisteredMethods );
};

const resetMockPaymentMethods = () => {
	[ 'cheque', 'bacs', 'credit-card' ].forEach( ( name ) => {
		__experimentalDeRegisterPaymentMethod( name );
	} );
	[ 'express-payment' ].forEach( ( name ) => {
		__experimentalDeRegisterExpressPaymentMethod( name );
	} );
};

describe( 'Payment method data store selectors/thunks', () => {
	beforeEach( () => {
		act( () => {
			registerMockPaymentMethods( false );

			// Set up MSW handlers for cart requests
			server.use(
				http.get( '/wc/store/v1/cart', () => {
					return HttpResponse.json( previewCart );
				} )
			);

			// need to clear the store resolution state between tests.
			wpDataFunctions.dispatch( storeKey ).invalidateResolutionForStore();
			wpDataFunctions
				.dispatch( storeKey )
				.receiveCart( defaultCartState.cartData );
		} );
	} );

	afterEach( async () => {
		act( () => {
			resetMockPaymentMethods();
			server.resetHandlers();
		} );
	} );

	it( 'toggles active payment method correctly for express payment activation and close', async () => {
		const TriggerActiveExpressPaymentMethod = () => {
			const activePaymentMethod = wpDataFunctions.useSelect(
				( select ) => {
					return select( paymentStore ).getActivePaymentMethod();
				}
			);

			return (
				<>
					<CheckoutExpressPayment />
					{ 'Active Payment Method: ' + activePaymentMethod }
				</>
			);
		};
		const TestComponent = () => {
			return <TriggerActiveExpressPaymentMethod />;
		};

		render( <TestComponent /> );

		// should initialize by default the first payment method.
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: credit-card/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		// Express payment method clicked.
		userEvent.click(
			screen.getByText( 'express-payment express payment method' )
		);

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: express-payment/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		// Express payment method closed.
		userEvent.click(
			screen.getByText( 'express-payment express payment method close' )
		);

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: credit-card/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );
	} );
} );

describe( 'Testing Payment Methods work correctly with saved cards turned on', () => {
	beforeEach( () => {
		act( () => {
			registerMockPaymentMethods( true );

			// Set up MSW handlers for cart requests
			server.use(
				http.get( '/wc/store/v1/cart', () => {
					return HttpResponse.json( previewCart );
				} )
			);

			// need to clear the store resolution state between tests.
			wpDataFunctions.dispatch( storeKey ).invalidateResolutionForStore();
			wpDataFunctions
				.dispatch( storeKey )
				.receiveCart( defaultCartState.cartData );
		} );
	} );

	afterEach( async () => {
		act( () => {
			resetMockPaymentMethods();
			server.resetHandlers();
		} );
	} );

	it( 'resets saved payment method data after starting and closing an express payment method', async () => {
		const TriggerActiveExpressPaymentMethod = () => {
			const { activePaymentMethod, paymentMethodData } =
				wpDataFunctions.useSelect( ( select ) => {
					const store = select( paymentStore );
					return {
						activePaymentMethod: store.getActivePaymentMethod(),
						paymentMethodData: store.getPaymentMethodData(),
					};
				} );
			return (
				<>
					<CheckoutExpressPayment />
					<SavedPaymentMethodOptions onChange={ () => void null } />
					{ 'Active Payment Method: ' + activePaymentMethod }
					{ paymentMethodData[ 'wc-credit-card-payment-token' ] && (
						<span>credit-card token</span>
					) }
				</>
			);
		};
		const TestComponent = () => {
			return <TriggerActiveExpressPaymentMethod />;
		};

		render( <TestComponent /> );

		// Should initialize by default the default saved payment method.
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: credit-card/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		await waitFor( () => {
			const creditCardToken = screen.queryByText( /credit-card token/ );
			expect( creditCardToken ).not.toBeNull();
		} );

		// Express payment method clicked.
		userEvent.click(
			screen.getByText( 'express-payment express payment method' )
		);

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: express-payment/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		await waitFor( () => {
			const creditCardToken = screen.queryByText( /credit-card token/ );
			expect( creditCardToken ).toBeNull();
		} );

		// Express payment method closed.
		userEvent.click(
			screen.getByText( 'express-payment express payment method close' )
		);

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: credit-card/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		await waitFor( () => {
			const creditCardToken = screen.queryByText( /credit-card token/ );
			expect( creditCardToken ).not.toBeNull();
		} );
	} );
} );

describe( 'Payment Selectors Unit Tests', () => {
	describe( 'getRegisteredExpressPaymentMethods', () => {
		it( 'should return the registered express payment methods from state', () => {
			const mockRegisteredMethods = {
				'stripe-express': {
					name: 'stripe-express',
					title: 'Stripe Express',
					description: 'Pay with Stripe express checkout',
					gatewayId: 'stripe',
					supportsStyle: [ 'height', 'borderRadius' ],
				},
				'paypal-express': {
					name: 'paypal-express',
					title: 'PayPal Express',
					description: 'Pay with PayPal express checkout',
					gatewayId: 'paypal',
					supportsStyle: [],
				},
			};

			const mockState = {
				status: 'idle',
				activePaymentMethod: 'stripe',
				availablePaymentMethods: {},
				availableExpressPaymentMethods: {},
				registeredExpressPaymentMethods: mockRegisteredMethods,
				savedPaymentMethods: {},
				paymentMethodData: {},
				paymentResult: null,
				paymentMethodsInitialized: false,
				expressPaymentMethodsInitialized: false,
				shouldSavePaymentMethod: false,
			};

			const result = getRegisteredExpressPaymentMethods( mockState );

			expect( result ).toEqual( mockRegisteredMethods );
		} );

		it( 'should return empty object when no registered express payment methods exist', () => {
			const mockState = {
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
			};

			const result = getRegisteredExpressPaymentMethods( mockState );

			expect( result ).toEqual( {} );
		} );

		it( 'should return the same reference when state does not change', () => {
			const mockRegisteredMethods = {
				'stripe-express': {
					name: 'stripe-express',
					title: 'Stripe Express',
					description: 'Pay with Stripe express checkout',
					gatewayId: 'stripe',
					supportsStyle: [ 'height', 'borderRadius' ],
				},
			};

			const mockState = {
				status: 'idle',
				activePaymentMethod: 'stripe',
				availablePaymentMethods: {},
				availableExpressPaymentMethods: {},
				registeredExpressPaymentMethods: mockRegisteredMethods,
				savedPaymentMethods: {},
				paymentMethodData: {},
				paymentResult: null,
				paymentMethodsInitialized: false,
				expressPaymentMethodsInitialized: false,
				shouldSavePaymentMethod: false,
			};

			const result1 = getRegisteredExpressPaymentMethods( mockState );
			const result2 = getRegisteredExpressPaymentMethods( mockState );

			expect( result1 ).toBe( result2 );
		} );

		it( 'should handle state with partial registered methods', () => {
			const mockRegisteredMethods = {
				'apple-pay': {
					name: 'apple-pay',
					title: 'Apple Pay',
					description: 'Pay with Apple Pay',
					gatewayId: 'apple-pay',
					supportsStyle: [ 'height' ],
				},
			};

			const mockState = {
				status: 'processing',
				activePaymentMethod: 'apple-pay',
				availablePaymentMethods: {
					'credit-card': {
						name: 'credit-card',
						title: 'Credit Card',
						description: 'Pay with credit card',
						gatewayId: 'credit-card',
						supportsStyle: [],
					},
				},
				availableExpressPaymentMethods: {
					'apple-pay': {
						name: 'apple-pay',
						title: 'Apple Pay',
						description: 'Pay with Apple Pay',
						gatewayId: 'apple-pay',
						supportsStyle: [ 'height' ],
					},
				},
				registeredExpressPaymentMethods: mockRegisteredMethods,
				savedPaymentMethods: {},
				paymentMethodData: { test: 'data' },
				paymentResult: null,
				paymentMethodsInitialized: true,
				expressPaymentMethodsInitialized: true,
				shouldSavePaymentMethod: true,
			};

			const result = getRegisteredExpressPaymentMethods( mockState );

			expect( result ).toEqual( mockRegisteredMethods );
			expect( result ).toHaveProperty( 'apple-pay' );
			expect( result[ 'apple-pay' ] ).toEqual( {
				name: 'apple-pay',
				title: 'Apple Pay',
				description: 'Pay with Apple Pay',
				gatewayId: 'apple-pay',
				supportsStyle: [ 'height' ],
			} );
		} );
	} );
} );
