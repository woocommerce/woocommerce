/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { dispatch, select } from '@wordpress/data';
import {
	paymentStore,
	cartStore,
	validationStore,
} from '@woocommerce/block-data';
import { previewCart } from '@woocommerce/resource-previews';
import {
	registerPaymentMethod,
	registerExpressPaymentMethod,
	__experimentalDeRegisterPaymentMethod,
	__experimentalDeRegisterExpressPaymentMethod,
} from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import PaymentMethods from '../payment-methods';

const METHODS = [ 'cod', 'bacs' ];
const EXPRESS_METHOD = 'dummy-express';
const ERROR_ID = 'payment-method';
const MESSAGE = 'Please select a payment method.';

// A component, not a bare element, so the payment method interface props cloned onto it don't land on a DOM node.
const PaymentContent = () => <div>A payment method</div>;

/**
 * Registers two gateways that can pay, lets the store initialise, then leaves nothing selected,
 * which is the state a shopper sees when the server decided to preselect nothing.
 */
const seedStoreWithNothingSelected = async ( {
	withExpress = false,
}: { withExpress?: boolean } = {} ) => {
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
	if ( withExpress ) {
		registerExpressPaymentMethod( {
			name: EXPRESS_METHOD,
			title: 'Dummy express',
			description: 'Dummy express',
			gatewayId: EXPRESS_METHOD,
			content: <PaymentContent />,
			edit: <PaymentContent />,
			canMakePayment: () => true,
			paymentMethodId: EXPRESS_METHOD,
			supports: { features: [ 'products' ] },
		} );
	}
	await act( async () => {
		dispatch( cartStore ).receiveCart( previewCart );
		await dispatch(
			paymentStore
		).__internalUpdateAvailablePaymentMethods();
		dispatch( paymentStore ).__internalSetActivePaymentMethod( '' );
	} );
};

const getError = () => select( validationStore ).getValidationError( ERROR_ID );

describe( 'PaymentMethods: asking the shopper to select a payment method', () => {
	afterEach( () => {
		// The component is still mounted here, so these store updates need act().
		act( () => {
			METHODS.forEach( ( name ) =>
				__experimentalDeRegisterPaymentMethod( name )
			);
			__experimentalDeRegisterExpressPaymentMethod( EXPRESS_METHOD );
			dispatch( validationStore ).clearValidationError( ERROR_ID );
		} );
	} );

	it( 'registers a hidden error while nothing is selected', async () => {
		await seedStoreWithNothingSelected();

		render( <PaymentMethods /> );

		await waitFor( () =>
			expect( getError() ).toEqual( { message: MESSAGE, hidden: true } )
		);
		expect( screen.queryByText( MESSAGE ) ).not.toBeInTheDocument();
	} );

	it( 'shows the error inline once all errors are revealed', async () => {
		await seedStoreWithNothingSelected();

		render( <PaymentMethods /> );
		await waitFor( () => expect( getError() ).toBeDefined() );
		const firstOption = screen.getByRole( 'radio', {
			name: ( name ) => name.startsWith( 'cod' ),
		} );
		expect( firstOption.closest( '.has-error' ) ).toBeNull();

		act( () => {
			dispatch( validationStore ).showAllValidationErrors();
		} );

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			MESSAGE
		);
		// The checkout focuses the first input inside `.has-error` after Place order, as it does for a field.
		expect( firstOption.closest( '.has-error' ) ).not.toBeNull();
	} );

	it( 'clears the error when the shopper picks a method', async () => {
		await seedStoreWithNothingSelected();

		render( <PaymentMethods /> );
		await waitFor( () => expect( getError() ).toBeDefined() );

		// eslint-disable-next-line testing-library/no-unnecessary-act -- The store updates the click triggers settle after the event, so act() keeps React quiet.
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'radio', {
					name: ( name ) => name.startsWith( 'bacs' ),
				} )
			);
		} );

		await waitFor( () => expect( getError() ).toBeUndefined() );
		expect( select( paymentStore ).getActivePaymentMethod() ).toBe(
			'bacs'
		);
	} );

	it( 'registers nothing while a method is selected', async () => {
		await seedStoreWithNothingSelected();
		act( () => {
			dispatch( paymentStore ).__internalSetActivePaymentMethod( 'bacs' );
		} );

		render( <PaymentMethods /> );
		await screen.findByRole( 'radio', {
			name: ( name ) => name.startsWith( 'bacs' ),
		} );

		expect( getError() ).toBeUndefined();
		expect(
			screen
				.getByRole( 'radio', {
					name: ( name ) => name.startsWith( 'cod' ),
				} )
				.closest( '.has-error' )
		).toBeNull();
	} );

	it( 'does not register the error while an express method is active', async () => {
		await seedStoreWithNothingSelected( { withExpress: true } );
		act( () => {
			dispatch( paymentStore ).__internalSetActivePaymentMethod(
				EXPRESS_METHOD
			);
		} );

		render( <PaymentMethods /> );
		await waitFor( () =>
			expect(
				select( paymentStore ).isExpressPaymentMethodActive()
			).toBe( true )
		);

		expect( getError() ).toBeUndefined();
	} );

	it( 'clears the error on unmount', async () => {
		await seedStoreWithNothingSelected();

		const { unmount } = render( <PaymentMethods /> );
		await waitFor( () => expect( getError() ).toBeDefined() );
		unmount();

		expect( getError() ).toBeUndefined();
	} );
} );
