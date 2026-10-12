/**
 * External dependencies
 */
import { waitFor } from '@testing-library/react';
import { dispatch, select } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { checkoutEvents } from '@woocommerce/blocks-checkout-events';
import { responseTypes } from '@woocommerce/types';

const CONTEXT = 'wc/checkout/payments';

const paymentNotices = () => select( 'core/notices' ).getNotices( CONTEXT );

describe( '__internalEmitValidateEvent', () => {
	let unsubscribe: () => void = () => void 0;

	afterEach( () => {
		unsubscribe();
		paymentNotices().forEach( ( notice ) =>
			dispatch( 'core/notices' ).removeNotice( notice.id, CONTEXT )
		);
	} );

	it( 'creates the notice under the id an observer asks for, looking like any other validation error', async () => {
		unsubscribe = checkoutEvents.onCheckoutValidation(
			() => ( {
				type: responseTypes.ERROR,
				errorMessage: 'Please select a payment method.',
				context: CONTEXT,
				id: 'wc-payment-method-required',
			} ),
			0
		);

		// The thunk does not return the emit promise, so wait for the notice to land.
		await dispatch( checkoutStore ).__internalEmitValidateEvent( {
			setValidationErrors: () => void 0,
		} );
		await waitFor( () => expect( paymentNotices() ).toHaveLength( 1 ) );

		expect( paymentNotices()[ 0 ] ).toMatchObject( {
			id: 'wc-payment-method-required',
			content: 'Please select a payment method.',
			isDismissible: true,
		} );
	} );

	it( 'keeps generating the id when an observer passes none', async () => {
		unsubscribe = checkoutEvents.onCheckoutValidation(
			() => ( {
				type: responseTypes.ERROR,
				errorMessage: 'Something is wrong with your payment.',
				context: CONTEXT,
			} ),
			0
		);

		await dispatch( checkoutStore ).__internalEmitValidateEvent( {
			setValidationErrors: () => void 0,
		} );
		await waitFor( () => expect( paymentNotices() ).toHaveLength( 1 ) );

		expect( paymentNotices()[ 0 ].isDismissible ).toBe( true );
		expect( paymentNotices()[ 0 ].id ).not.toBe(
			'wc-payment-method-required'
		);
	} );
} );
