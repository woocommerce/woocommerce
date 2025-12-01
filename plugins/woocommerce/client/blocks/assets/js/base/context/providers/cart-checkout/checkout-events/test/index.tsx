/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import { checkoutStore, paymentStore } from '@woocommerce/block-data';
import { checkoutEvents } from '@woocommerce/blocks-checkout-events';

/**
 * Internal dependencies
 */
import { CheckoutEventsProvider } from '../index';

// Mock the registry functions
jest.mock( '@woocommerce/blocks-registry', () => ( {
	getPaymentMethods: jest.fn( () => ( {} ) ),
	getExpressPaymentMethods: jest.fn( () => ( {
		stripe: {
			name: 'stripe',
			title: 'Stripe',
			description: 'Pay with Stripe',
			gatewayId: 'stripe',
			supports: {
				style: [ 'height', 'borderRadius' ],
			},
		},
		paypal: {
			name: 'paypal',
			title: 'PayPal',
			description: 'Pay with PayPal',
			gatewayId: 'paypal',
			supports: {
				style: [],
			},
		},
	} ) ),
} ) );

describe( 'CheckoutEventsContext', () => {
	let mockSetRegisteredExpressPaymentMethods: jest.Mock;

	beforeEach( () => {
		jest.clearAllMocks();
		dispatch( checkoutStore ).__internalSetIdle();

		// Mock the payment store dispatch action
		mockSetRegisteredExpressPaymentMethods = jest.fn();
		jest.spyOn(
			dispatch( paymentStore ),
			'__internalSetRegisteredExpressPaymentMethods'
		).mockImplementation( mockSetRegisteredExpressPaymentMethods );
	} );

	it( '__internalSetRegisteredExpressPaymentMethods is called when component renders', () => {
		render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);

		expect( mockSetRegisteredExpressPaymentMethods ).toHaveBeenCalledWith( {
			stripe: {
				name: 'stripe',
				title: 'Stripe',
				description: 'Pay with Stripe',
				gatewayId: 'stripe',
				supportsStyle: [ 'height', 'borderRadius' ],
			},
			paypal: {
				name: 'paypal',
				title: 'PayPal',
				description: 'Pay with PayPal',
				gatewayId: 'paypal',
				supportsStyle: [],
			},
		} );
	} );

	it( 'onCheckoutValidation observers are called when the checkout is in the "beforeProcessing" state', async () => {
		const callback = jest.fn();
		const callback2 = jest.fn();
		checkoutEvents.onCheckoutValidation( callback );
		checkoutEvents.onCheckoutValidation( callback2 );
		const { rerender } = render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		await act( () =>
			dispatch( checkoutStore ).__internalSetBeforeProcessing()
		);
		rerender(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		expect( callback ).toHaveBeenCalled();
		expect( callback2 ).toHaveBeenCalled();
	} );
	it( 'onCheckoutSuccess observers are called when the checkout is in the "afterProcessing" state and no error exists, onCheckoutFail observers are not called', async () => {
		const successCallback = jest.fn();
		const successCallback2 = jest.fn();
		const failCallback = jest.fn();
		checkoutEvents.onCheckoutSuccess( successCallback );
		checkoutEvents.onCheckoutSuccess( successCallback2 );
		checkoutEvents.onCheckoutFail( failCallback );
		const { rerender } = render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		await act( () =>
			dispatch( checkoutStore ).__internalSetAfterProcessing()
		);
		rerender(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		expect( successCallback ).toHaveBeenCalled();
		expect( successCallback2 ).toHaveBeenCalled();
		expect( failCallback ).not.toHaveBeenCalled();
	} );
	it( 'onCheckoutSuccess observers are not called when the checkout is in the "afterProcessing" state and an error exists, onCheckoutFail observers are called', async () => {
		const successCallback = jest.fn();
		const successCallback2 = jest.fn();
		const failCallback = jest.fn();
		checkoutEvents.onCheckoutSuccess( successCallback );
		checkoutEvents.onCheckoutSuccess( successCallback2 );
		checkoutEvents.onCheckoutFail( failCallback );
		const { rerender } = render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		await act( () => {
			dispatch( checkoutStore ).__internalSetHasError( true );
			dispatch( checkoutStore ).__internalSetAfterProcessing();
		} );
		rerender(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		expect( successCallback ).not.toHaveBeenCalled();
		expect( successCallback2 ).not.toHaveBeenCalled();
		expect( failCallback ).toHaveBeenCalled();
	} );
} );
