/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import {
	registerPaymentMethod,
	__experimentalDeRegisterPaymentMethod,
} from '@woocommerce/blocks-registry';
import { PaymentMethodDataProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import PaymentMethods from '../payment-methods';

jest.mock( '../payment-method-options', () => () => (
	<span>Payment method options</span>
) );
jest.mock( '../saved-payment-method-options', () => ( { onChange } ) => (
	<>
		<span>Saved payment method options</span>
		<button onClick={ () => onChange( '1' ) }>Select saved</button>
		<button onClick={ () => onChange( '0' ) }>Select not saved</button>
	</>
) );

const registerMockPaymentMethods = () => {
	[ 'cheque' ].forEach( ( name ) => {
		registerPaymentMethod(
			( Config ) =>
				new Config( {
					name,
					label: name,
					content: <div>A payment method</div>,
					edit: <div>A payment method</div>,
					icons: null,
					canMakePayment: () => true,
					ariaLabel: name,
				} )
		);
	} );
};

const resetMockPaymentMethods = () => {
	[ 'cheque' ].forEach( ( name ) => {
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
			const noPaymentMethods = screen.queryByText(
				/no payment methods available/
			);
			expect( noPaymentMethods ).not.toBeNull();
		} );
	} );

	test( 'should hide/show PaymentMethodOptions when a saved payment method is checked/unchecked', async () => {
		registerMockPaymentMethods();
		render(
			<PaymentMethodDataProvider>
				<PaymentMethods />
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
		} );

		fireEvent.click( screen.getByText( 'Select saved' ) );

		await waitFor( () => {
			const savedPaymentMethodOptions = screen.queryByText(
				/Saved payment method options/
			);
			const paymentMethodOptions = screen.queryByText(
				/Payment method options/
			);
			expect( savedPaymentMethodOptions ).not.toBeNull();
			expect( paymentMethodOptions ).toBeNull();
		} );

		fireEvent.click( screen.getByText( 'Select not saved' ) );

		await waitFor( () => {
			const savedPaymentMethodOptions = screen.queryByText(
				/Saved payment method options/
			);
			const paymentMethodOptions = screen.queryByText(
				/Payment method options/
			);
			expect( savedPaymentMethodOptions ).not.toBeNull();
			expect( paymentMethodOptions ).not.toBeNull();
		} );
		resetMockPaymentMethods();
	} );
} );
