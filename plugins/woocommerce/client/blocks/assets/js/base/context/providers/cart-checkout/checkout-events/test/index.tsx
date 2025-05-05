/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { checkoutEvents } from '@woocommerce/blocks-checkout-events';

/**
 * Internal dependencies
 */
import { CheckoutEventsProvider } from '../index';

describe( 'CheckoutEventsContext', () => {
	beforeEach( () => {
		dispatch( checkoutStore ).__internalSetIdle();
	} );
	it( 'onCheckoutValidation observers are called when the checkout is in the "beforeProcessing" state', async () => {
		const callback = jest.fn();
		const callback2 = jest.fn();
		checkoutEvents.onCheckoutValidation( callback );
		checkoutEvents.onCheckoutValidation( callback2 );
		const { rerender } = render(
			<CheckoutEventsProvider redirectUrl="local" />
		);
		await act( () =>
			dispatch( checkoutStore ).__internalSetBeforeProcessing()
		);
		rerender( <CheckoutEventsProvider redirectUrl="local" /> );
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
			<CheckoutEventsProvider redirectUrl="local" />
		);
		await act( () =>
			dispatch( checkoutStore ).__internalSetAfterProcessing()
		);
		rerender( <CheckoutEventsProvider redirectUrl="local" /> );
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
			<CheckoutEventsProvider redirectUrl="local" />
		);
		await act( () => {
			dispatch( checkoutStore ).__internalSetHasError( true );
			dispatch( checkoutStore ).__internalSetAfterProcessing();
		} );
		rerender( <CheckoutEventsProvider redirectUrl="local" /> );
		expect( successCallback ).not.toHaveBeenCalled();
		expect( successCallback2 ).not.toHaveBeenCalled();
		expect( failCallback ).toHaveBeenCalled();
	} );
} );
