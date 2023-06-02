/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import {
	registerPaymentMethod,
	registerExpressPaymentMethod,
	__experimentalDeRegisterPaymentMethod,
	__experimentalDeRegisterExpressPaymentMethod,
} from '@woocommerce/blocks-registry';
import {
	CheckoutExpressPayment,
	SavedPaymentMethodOptions,
} from '@woocommerce/base-components/payment-methods';
/**
 * Internal dependencies
 */
import {
	usePaymentMethodDataContext,
	PaymentMethodDataProvider,
} from '../payment-method-data-context';
import { defaultCartState } from '../../../../../data/default-states';

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
								gateway: 'stripe',
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

const registerMockPaymentMethods = () => {
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
	[ 'stripe' ].forEach( ( name ) => {
		registerPaymentMethod( {
			name,
			label: name,
			content: <div>A payment method</div>,
			edit: <div>A payment method</div>,
			icons: null,
			canMakePayment: () => true,
			supports: {
				showSavedCards: true,
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
	[ 'cheque', 'bacs', 'stripe' ].forEach( ( name ) => {
		__experimentalDeRegisterPaymentMethod( name );
	} );
	[ 'express-payment' ].forEach( ( name ) => {
		__experimentalDeRegisterExpressPaymentMethod( name );
	} );
};

describe( 'Testing Payment Method Data Context Provider', () => {
	beforeEach( async () => {
		registerMockPaymentMethods();
		fetchMock.mockResponse( ( req ) => {
			if ( req.url.match( /wc\/store\/cart/ ) ) {
				return Promise.resolve( JSON.stringify( previewCart ) );
			}
		} );
		// need to clear the store resolution state between tests.
		await dispatch( storeKey ).invalidateResolutionForStore();
		await dispatch( storeKey ).receiveCart( defaultCartState );
	} );
	afterEach( async () => {
		resetMockPaymentMethods();
		fetchMock.resetMocks();
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
		render( <TestComponent /> );
		// should initialize by default the first payment method.
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: cheque/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );
		// Express payment method clicked.
		fireEvent.click(
			screen.getByText( 'express-payment express payment method' )
		);
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: express-payment/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );
		// Express payment method closed.
		fireEvent.click(
			screen.getByText( 'express-payment express payment method close' )
		);
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: cheque/
			);
			expect( activePaymentMethod ).not.toBeNull();
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
					{ paymentMethodData[ 'wc-stripe-payment-token' ] && (
						<span>Stripe token</span>
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
		render( <TestComponent /> );
		// Should initialize by default the default saved payment method.
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: stripe/
			);
			const stripeToken = screen.queryByText( /Stripe token/ );
			expect( activePaymentMethod ).not.toBeNull();
			expect( stripeToken ).not.toBeNull();
		} );
		// Express payment method clicked.
		fireEvent.click(
			screen.getByText( 'express-payment express payment method' )
		);
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: express-payment/
			);
			const stripeToken = screen.queryByText( /Stripe token/ );
			expect( activePaymentMethod ).not.toBeNull();
			expect( stripeToken ).toBeNull();
		} );
		// Express payment method closed.
		fireEvent.click(
			screen.getByText( 'express-payment express payment method close' )
		);
		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: stripe/
			);
			const stripeToken = screen.queryByText( /Stripe token/ );
			expect( activePaymentMethod ).not.toBeNull();
			expect( stripeToken ).not.toBeNull();
		} );
	} );
} );
