/**
 * Internal dependencies
 */
import { createEmitter } from './event-emitter';

export const CART_EVENTS = {
	/**
	 * Event emitted when the user clicks "Proceed to Checkout" in the cart.
	 * Observers can return an error/fail response to prevent navigation.
	 */
	PROCEED_TO_CHECKOUT: 'cart_proceed_to_checkout',
};

export const cartEventsEmitter = createEmitter();

export const cartEvents = {
	onProceedToCheckout: cartEventsEmitter.createSubscribeFunction(
		CART_EVENTS.PROCEED_TO_CHECKOUT
	),
};
