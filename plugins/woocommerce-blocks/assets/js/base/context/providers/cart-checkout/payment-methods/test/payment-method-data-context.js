/**
 * External dependencies
 */
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import {
	registerPaymentMethod,
	registerExpressPaymentMethod,
	__experimentalDeRegisterPaymentMethod,
	__experimentalDeRegisterExpressPaymentMethod,
} from '@woocommerce/blocks-registry';
import { default as fetchMock } from 'jest-fetch-mock';

/**
 * Internal dependencies
 */
import {
	usePaymentMethodDataContext,
	PaymentMethodDataProvider,
} from '../payment-method-data-context';
import {
	CheckoutExpressPayment,
	SavedPaymentMethodOptions,
} from '../../../../../../blocks/cart-checkout/payment-methods';
import { defaultCartState } from '../../../../../../data/default-states';

jest.mock( '@woocommerce/settings', () => {
	const originalModule = jest.requireActual( '@woocommerce/settings' );

	return {
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
			content: <Content />,
			edit: <div>An express payment method</div>,
			canMakePayment: () => true,
			paymentMethodId: name,
			supports: {
				features: [ 'products' ],
			},
		} );
	} );
};

const resetMockPaymentMethods = () => {
	[ 'cheque', 'bacs', 'credit-card' ].forEach( ( name ) => {
		__experimentalDeRegisterPaymentMethod( name );
	} );
	[ 'express-payment' ].forEach( ( name ) => {
		__experimentalDeRegisterExpressPaymentMethod( name );
	} );
};

describe( 'Testing Payment Method Data Context Provider', () => {
	beforeEach( () => {
		act( () => {
			registerMockPaymentMethods( false );

			fetchMock.mockResponse( ( req ) => {
				if ( req.url.match( /wc\/store\/v1\/cart/ ) ) {
					return Promise.resolve( JSON.stringify( previewCart ) );
				}
				return Promise.resolve( '' );
			} );

			// need to clear the store resolution state between tests.
			dispatch( storeKey ).invalidateResolutionForStore();
			dispatch( storeKey ).receiveCart( defaultCartState.cartData );
		} );
	} );

	afterEach( async () => {
		act( () => {
			resetMockPaymentMethods();
			fetchMock.resetMocks();
		} );
	} );

	it( 'toggles active payment method correctly for express payment activation and close', async () => {
		const TriggerActiveExpressPaymentMethod = () => {
			const { activePaymentMethod } = usePaymentMethodDataContext();
			return (
				<>
					<CheckoutExpressPayment />
					{ 'Active Payment Method: ' + activePaymentMethod }
				</>
			);
		};
		const TestComponent = () => {
			return (
				<PaymentMethodDataProvider>
					<TriggerActiveExpressPaymentMethod />
				</PaymentMethodDataProvider>
			);
		};

		act( () => {
			render( <TestComponent /> );
		} );

		// should initialize by default the first payment method.
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: cheque/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		act( () => {
			// Express payment method clicked.
			userEvent.click(
				screen.getByText( 'express-payment express payment method' )
			);
		} );

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: express-payment/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		act( () => {
			// Express payment method closed.
			userEvent.click(
				screen.getByText(
					'express-payment express payment method close'
				)
			);
		} );

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: cheque/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		// ["`select` control in `@wordpress/data-controls` is deprecated. Please use built-in `resolveSelect` control in `@wordpress/data` instead."]
		expect( console ).toHaveWarned();
	} );
} );

describe( 'Testing Payment Method Data Context Provider with saved cards turned on', () => {
	beforeEach( () => {
		act( () => {
			registerMockPaymentMethods( true );

			fetchMock.mockResponse( ( req ) => {
				if ( req.url.match( /wc\/store\/v1\/cart/ ) ) {
					return Promise.resolve( JSON.stringify( previewCart ) );
				}
				return Promise.resolve( '' );
			} );

			// need to clear the store resolution state between tests.
			dispatch( storeKey ).invalidateResolutionForStore();
			dispatch( storeKey ).receiveCart( defaultCartState.cartData );
		} );
	} );

	afterEach( async () => {
		act( () => {
			resetMockPaymentMethods();
			fetchMock.resetMocks();
		} );
	} );

	it( 'resets saved payment method data after starting and closing an express payment method', async () => {
		const TriggerActiveExpressPaymentMethod = () => {
			const {
				activePaymentMethod,
				paymentMethodData,
			} = usePaymentMethodDataContext();
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
			return (
				<PaymentMethodDataProvider>
					<TriggerActiveExpressPaymentMethod />
				</PaymentMethodDataProvider>
			);
		};

		act( () => {
			render( <TestComponent /> );
		} );

		// Should initialize by default the default saved payment method.
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: credit-card/
			);
			const creditCardToken = screen.queryByText( /credit-card token/ );
			expect( activePaymentMethod ).not.toBeNull();
			expect( creditCardToken ).not.toBeNull();
		} );

		act( () => {
			// Express payment method clicked.
			userEvent.click(
				screen.getByText( 'express-payment express payment method' )
			);
		} );

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: express-payment/
			);
			const creditCardToken = screen.queryByText( /credit-card token/ );
			expect( activePaymentMethod ).not.toBeNull();
			expect( creditCardToken ).toBeNull();
		} );

		act( () => {
			// Express payment method closed.
			userEvent.click(
				screen.getByText(
					'express-payment express payment method close'
				)
			);
		} );

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: credit-card/
			);
			const creditCardToken = screen.queryByText( /credit-card token/ );
			expect( activePaymentMethod ).not.toBeNull();
			expect( creditCardToken ).not.toBeNull();
		} );
	} );
} );
