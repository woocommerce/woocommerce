/**
 * External dependencies
 */
import { responseTypes } from '@woocommerce/types';
/**
 * Internal dependencies
 */
import { checkoutEvents, checkoutEventsEmitter } from '../checkout-events';

describe( 'Checkout events emitter v2', () => {
	it( 'allows callbacks to subscribe to events using helper functions', async () => {
		const callback = jest.fn();
		checkoutEvents.onCheckoutValidation( callback );
		checkoutEvents.onCheckoutSuccess( callback );
		checkoutEvents.onCheckoutFail( callback );
		await checkoutEventsEmitter.emit( 'checkout_validation', 'test data' );
		await checkoutEventsEmitter.emit( 'checkout_success', 'test data' );
		await checkoutEventsEmitter.emit( 'checkout_fail', 'test data' );
		expect( callback ).toHaveBeenCalledWith( 'test data' );
		expect( callback ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'allows callbacks to subscribe to events with different priorities', async () => {
		const executionOrder: string[] = [];
		const callback = jest.fn( () => {
			executionOrder.push( 'callback1' );
			return { type: responseTypes.SUCCESS };
		} );
		const callback2 = jest.fn( () => {
			executionOrder.push( 'callback2' );
			return { type: responseTypes.SUCCESS };
		} );
		checkoutEvents.onCheckoutValidation( callback, 10 );
		checkoutEvents.onCheckoutValidation( callback2, 5 );
		await checkoutEventsEmitter.emit( 'checkout_validation', 'test data' );
		expect( executionOrder ).toEqual( [ 'callback2', 'callback1' ] );
	} );

	it( 'allows callbacks to be unsubscribed', async () => {
		const callback = jest.fn();
		const unsubscribe = checkoutEvents.onCheckoutValidation( callback );
		await checkoutEventsEmitter.emit( 'checkout_validation', 'test data' );
		unsubscribe();
		await checkoutEventsEmitter.emit( 'checkout_validation', 'test data' );
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );
} );
