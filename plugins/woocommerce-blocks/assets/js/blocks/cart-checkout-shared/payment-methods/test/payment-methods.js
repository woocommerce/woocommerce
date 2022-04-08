/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { default as fetchMock } from 'jest-fetch-mock';
import {
	registerPaymentMethod,
	__experimentalDeRegisterPaymentMethod,
} from '@woocommerce/blocks-registry';
import {
	PaymentMethodDataProvider,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';
import userEvent from '@testing-library/user-event';
/**
 * Internal dependencies
 */
import PaymentMethods from '../payment-methods';
import { defaultCartState } from '../../../../data/default-states';

jest.mock( '../saved-payment-method-options', () => ( { onChange } ) => {
	return (
		<>
			<span>Saved payment method options</span>
			<button onClick={ () => onChange( '0' ) }>Select saved</button>
		</>
	);
} );

jest.mock(
	'@woocommerce/base-components/radio-control-accordion',
	() => ( { onChange } ) => (
		<>
			<span>Payment method options</span>
			<button onClick={ () => onChange( 'credit-card' ) }>
				Select new payment
			</button>
		</>
	)
);

const registerMockPaymentMethods = () => {
	[ 'credit-card' ].forEach( ( name ) => {
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
};

const resetMockPaymentMethods = () => {
	[ 'credit-card' ].forEach( ( name ) => {
		__experimentalDeRegisterPaymentMethod( name );
	} );
};

describe( 'PaymentMethods', () => {
	beforeEach( () => {
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

	afterEach( () => {
		fetchMock.resetMocks();
	} );

	test( 'should show no payment methods component when there are no payment methods', async () => {
		render(
			<PaymentMethodDataProvider>
				<PaymentMethods />
			</PaymentMethodDataProvider>
		);

		await waitFor( () => {
			const noPaymentMethods = screen.queryAllByText(
				/no payment methods available/
			);
			// We might get more than one match because the `speak()` function
			// creates an extra `div` with the notice contents used for a11y.
			expect( noPaymentMethods.length ).toBeGreaterThanOrEqual( 1 );
		} );
	} );

	test( 'selecting new payment method', async () => {
		const ShowActivePaymentMethod = () => {
			const {
				activePaymentMethod,
				activeSavedToken,
			} = usePaymentMethodDataContext();
			return (
				<>
					<div>
						{ 'Active Payment Method: ' + activePaymentMethod }
					</div>
					<div>{ 'Active Saved Token: ' + activeSavedToken }</div>
				</>
			);
		};

		registerMockPaymentMethods();
		render(
			<PaymentMethodDataProvider>
				<PaymentMethods />
				<ShowActivePaymentMethod />
			</PaymentMethodDataProvider>
		);

		await waitFor( () => {
			const savedPaymentMethodOptions = screen.queryByText(
				/Saved payment method options/
			);
			expect( savedPaymentMethodOptions ).not.toBeNull();
		} );

		await waitFor( () => {
			const paymentMethodOptions = screen.queryByText(
				/Payment method options/
			);
			expect( paymentMethodOptions ).not.toBeNull();
		} );

		await waitFor( () => {
			const savedToken = screen.queryByText(
				/Active Payment Method: credit-card/
			);
			expect( savedToken ).toBeNull();
		} );

		userEvent.click( screen.getByText( 'Select new payment' ) );

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: credit-card/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		resetMockPaymentMethods();
	} );
} );
