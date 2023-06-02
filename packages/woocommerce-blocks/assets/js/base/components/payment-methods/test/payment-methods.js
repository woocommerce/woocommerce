/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import {
	registerPaymentMethod,
	__experimentalDeRegisterPaymentMethod,
} from '@woocommerce/blocks-registry';
import {
	PaymentMethodDataProvider,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import PaymentMethods from '../payment-methods';

jest.mock( '../saved-payment-method-options', () => ( { onChange } ) => {
	return (
		<>
			<span>Saved payment method options</span>
			<button onClick={ () => onChange( '0' ) }>Select saved</button>
		</>
	);
} );

jest.mock( '../../radio-control-accordion', () => ( { onChange } ) => (
	<>
		<span>Payment method options</span>
		<button onClick={ () => onChange( 'stripe' ) }>
			Select new payment
		</button>
	</>
) );

const registerMockPaymentMethods = () => {
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
};

const resetMockPaymentMethods = () => {
	[ 'stripe' ].forEach( ( name ) => {
		__experimentalDeRegisterPaymentMethod( name );
	} );
};

describe( 'PaymentMethods', () => {
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
			const paymentMethodOptions = screen.queryByText(
				/Payment method options/
			);
			expect( savedPaymentMethodOptions ).not.toBeNull();
			expect( paymentMethodOptions ).not.toBeNull();
			const savedToken = screen.queryByText(
				/Active Payment Method: stripe/
			);
			expect( savedToken ).toBeNull();
		} );

		fireEvent.click( screen.getByText( 'Select new payment' ) );

		await waitFor( () => {
			const activePaymentMethod = screen.queryByText(
				/Active Payment Method: stripe/
			);
			expect( activePaymentMethod ).not.toBeNull();
		} );

		resetMockPaymentMethods();
	} );
} );
