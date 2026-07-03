/**
 * External dependencies
 */
import { PlainExpressPaymentMethods } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { setDefaultPaymentMethod as setDefaultPaymentMethodOriginal } from '../utils/set-default-payment-method';
import '../../checkout';
import { store as paymentStore } from '..';
import { PlainPaymentMethods } from '../../../types';
import { __internalSetRegisteredExpressPaymentMethods } from '../actions';

const originalDispatch = jest.requireActual( '@wordpress/data' ).dispatch;

jest.mock( '../utils/set-default-payment-method', () => ( {
	setDefaultPaymentMethod: jest.fn(),
} ) );

describe( 'payment data store actions', () => {
	const paymentMethods: PlainPaymentMethods = {
		'wc-payment-gateway-1': {
			name: 'wc-payment-gateway-1',
			title: 'Payment Gateway 1',
			description: 'A test payment gateway',
			gatewayId: 'wc-payment-gateway-1',
			supportsStyle: [],
		},
		'wc-payment-gateway-2': {
			name: 'wc-payment-gateway-2',
			title: 'Payment Gateway 2',
			description: 'Another test payment gateway',
			gatewayId: 'wc-payment-gateway-2',
			supportsStyle: [],
		},
	};

	const expressPaymentMethods: PlainExpressPaymentMethods = {
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

	describe( 'setAvailablePaymentMethods', () => {
		it( 'Does not call setDefaultPaymentGateway if the current method is still available', () => {
			const actions = originalDispatch( paymentStore );
			actions.__internalSetActivePaymentMethod(
				Object.keys( paymentMethods )[ 0 ]
			);
			actions.__internalSetAvailablePaymentMethods( paymentMethods );
			expect( setDefaultPaymentMethodOriginal ).not.toHaveBeenCalled();
		} );

		it( 'Resets the default gateway if the current method is no longer available', () => {
			const actions = originalDispatch( paymentStore );
			actions.__internalSetActivePaymentMethod(
				Object.keys( paymentMethods )[ 0 ]
			);
			actions.__internalSetAvailablePaymentMethods( [
				paymentMethods[ Object.keys( paymentMethods )[ 0 ] ],
			] );
			expect( setDefaultPaymentMethodOriginal ).toHaveBeenCalled();
		} );
	} );

	describe( '__internalSetRegisteredExpressPaymentMethods', () => {
		it( 'returns the correct action object', () => {
			const action = __internalSetRegisteredExpressPaymentMethods(
				expressPaymentMethods
			);

			expect( action ).toEqual( {
				type: 'SET_REGISTERED_EXPRESS_PAYMENT_METHODS',
				paymentMethods: expressPaymentMethods,
			} );
		} );

		it( 'handles empty payment methods object', () => {
			const emptyMethods: PlainExpressPaymentMethods = {};
			const action =
				__internalSetRegisteredExpressPaymentMethods( emptyMethods );

			expect( action ).toEqual( {
				type: 'SET_REGISTERED_EXPRESS_PAYMENT_METHODS',
				paymentMethods: {},
			} );
		} );
	} );
} );
