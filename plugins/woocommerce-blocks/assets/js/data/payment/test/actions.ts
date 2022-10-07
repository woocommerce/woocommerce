/**
 * Internal dependencies
 */
import * as setDefaultPaymentMethodFunctions from '../set-default-payment-method';
import { PAYMENT_STORE_KEY } from '..';
import { PlainPaymentMethods } from '../../../types';

const originalDispatch = jest.requireActual( '@wordpress/data' ).dispatch;

jest.mock( '../set-default-payment-method', () => ( {
	setDefaultPaymentMethod: jest.fn(),
} ) );

describe( 'payment data store actions', () => {
	const paymentMethods: PlainPaymentMethods = {
		'wc-payment-gateway-1': {
			name: 'wc-payment-gateway-1',
		},
		'wc-payment-gateway-2': {
			name: 'wc-payment-gateway-2',
		},
	};

	describe( 'setAvailablePaymentMethods', () => {
		it( 'Does not call setDefaultPaymentGateway if the current method is still available', () => {
			const actions = originalDispatch( PAYMENT_STORE_KEY );
			actions.__internalSetActivePaymentMethod(
				Object.keys( paymentMethods )[ 0 ]
			);
			actions.__internalSetAvailablePaymentMethods( paymentMethods );
			expect(
				setDefaultPaymentMethodFunctions.setDefaultPaymentMethod
			).not.toBeCalled();
		} );

		it( 'Resets the default gateway if the current method is no longer available', () => {
			const actions = originalDispatch( PAYMENT_STORE_KEY );
			actions.__internalSetActivePaymentMethod(
				Object.keys( paymentMethods )[ 0 ]
			);
			actions.__internalSetAvailablePaymentMethods( [
				paymentMethods[ Object.keys( paymentMethods )[ 0 ] ],
			] );
			expect(
				setDefaultPaymentMethodFunctions.setDefaultPaymentMethod
			).toBeCalled();
		} );
	} );
} );
