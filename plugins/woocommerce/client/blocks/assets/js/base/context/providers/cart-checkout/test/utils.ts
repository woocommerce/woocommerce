/**
 * Internal dependencies
 */
import { validatePaymentMethodSelection } from '../utils';

describe( 'validatePaymentMethodSelection', () => {
	it( 'passes when the store still preselects a payment method, leaving the server to validate as before', () => {
		expect(
			validatePaymentMethodSelection( {
				preselectPaymentMethod: true,
				hasPaymentMethods: true,
				cartNeedsPayment: true,
				activePaymentMethod: '',
				isExpressPaymentMethodActive: false,
			} )
		).toBe( true );
	} );

	it( 'passes when only express payment methods are available, leaving the server to validate as before', () => {
		expect(
			validatePaymentMethodSelection( {
				preselectPaymentMethod: false,
				hasPaymentMethods: false,
				cartNeedsPayment: true,
				activePaymentMethod: '',
				isExpressPaymentMethodActive: false,
			} )
		).toBe( true );
	} );

	it( 'passes when the cart does not need payment', () => {
		expect(
			validatePaymentMethodSelection( {
				preselectPaymentMethod: false,
				hasPaymentMethods: true,
				cartNeedsPayment: false,
				activePaymentMethod: '',
				isExpressPaymentMethodActive: false,
			} )
		).toBe( true );
	} );

	it( 'passes when an express payment method is handling the payment', () => {
		expect(
			validatePaymentMethodSelection( {
				preselectPaymentMethod: false,
				hasPaymentMethods: true,
				cartNeedsPayment: true,
				activePaymentMethod: '',
				isExpressPaymentMethodActive: true,
			} )
		).toBe( true );
	} );

	it( 'passes when a payment method is selected', () => {
		expect(
			validatePaymentMethodSelection( {
				preselectPaymentMethod: false,
				hasPaymentMethods: true,
				cartNeedsPayment: true,
				activePaymentMethod: 'cod',
				isExpressPaymentMethodActive: false,
			} )
		).toBe( true );
	} );

	it( 'asks for an error notice with a fixed id in the payment section when nothing is selected', () => {
		expect(
			validatePaymentMethodSelection( {
				preselectPaymentMethod: false,
				hasPaymentMethods: true,
				cartNeedsPayment: true,
				activePaymentMethod: '',
				isExpressPaymentMethodActive: false,
			} )
		).toEqual( {
			type: 'error',
			errorMessage: 'Please select a payment method.',
			context: 'wc/checkout/payments',
			id: 'wc-payment-method-required',
		} );
	} );
} );
