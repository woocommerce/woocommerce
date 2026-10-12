/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { dispatch, select } from '@wordpress/data';
import { paymentStore, cartStore } from '@woocommerce/block-data';
import { previewCart } from '@woocommerce/resource-previews';
import {
	noticeContexts,
	PAYMENT_METHOD_REQUIRED_NOTICE_ID,
} from '@woocommerce/base-context';
import {
	registerPaymentMethod,
	__experimentalDeRegisterPaymentMethod,
} from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import PaymentMethodOptions from '../payment-method-options';

const METHODS = [ 'cod', 'bacs' ];

// A component, not a bare element, so the payment method interface props cloned onto it don't land on a DOM node.
const PaymentContent = () => <div>A payment method</div>;

/**
 * Registers two gateways that can pay, lets the store initialise, then leaves nothing selected,
 * which is the state a shopper sees when the store does not preselect a payment method.
 */
const seedStoreWithNothingSelected = async () => {
	METHODS.forEach( ( name ) => {
		registerPaymentMethod( {
			name,
			label: name,
			content: <PaymentContent />,
			edit: <PaymentContent />,
			icons: null,
			canMakePayment: () => true,
			supports: { features: [ 'products' ] },
			ariaLabel: name,
		} );
	} );
	await act( async () => {
		dispatch( cartStore ).receiveCart( previewCart );
		await dispatch(
			paymentStore
		).__internalUpdateAvailablePaymentMethods();
		dispatch( paymentStore ).__internalSetActivePaymentMethod( '' );
	} );
};

describe( 'PaymentMethodOptions', () => {
	beforeEach( async () => {
		await seedStoreWithNothingSelected();
	} );

	afterEach( () => {
		// The component is still mounted here, so these store updates need act().
		act( () => {
			METHODS.forEach( ( name ) =>
				__experimentalDeRegisterPaymentMethod( name )
			);
			select( 'core/notices' )
				.getNotices( noticeContexts.PAYMENTS )
				.forEach( ( notice ) =>
					dispatch( 'core/notices' ).removeNotice(
						notice.id,
						noticeContexts.PAYMENTS
					)
				);
		} );
	} );

	it( 'clears the "select a payment method" error as soon as the shopper picks one', async () => {
		act( () => {
			dispatch( 'core/notices' ).createErrorNotice(
				'Please select a payment method.',
				{
					id: PAYMENT_METHOD_REQUIRED_NOTICE_ID,
					context: noticeContexts.PAYMENTS,
				}
			);
			// An unrelated payment error must survive the pick.
			dispatch( 'core/notices' ).createErrorNotice(
				'Your card was declined.',
				{ id: 'other-error', context: noticeContexts.PAYMENTS }
			);
		} );
		render( <PaymentMethodOptions /> );
		expect(
			select( 'core/notices' ).getNotices( noticeContexts.PAYMENTS )
		).toHaveLength( 2 );

		// eslint-disable-next-line testing-library/no-unnecessary-act -- The store updates the click triggers settle after the event, so act() keeps React quiet.
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'radio', {
					name: ( name ) => name.startsWith( 'bacs' ),
				} )
			);
		} );

		await waitFor( () =>
			expect(
				select( 'core/notices' )
					.getNotices( noticeContexts.PAYMENTS )
					.map( ( notice ) => notice.id )
			).toEqual( [ 'other-error' ] )
		);
		expect( select( paymentStore ).getActivePaymentMethod() ).toBe(
			'bacs'
		);
	} );
} );
