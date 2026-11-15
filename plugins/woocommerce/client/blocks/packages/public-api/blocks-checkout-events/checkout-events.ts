/**
 * Internal dependencies
 */
import { createEmitter } from './event-emitter';

export const CHECKOUT_EVENTS = {
	/**
	 * Event emitted after checkout processing if there are no errors in the data store.
	 */
	CHECKOUT_SUCCESS: 'checkout_success',
	/**
	 * Event emitted after checkout processing if there is an error in the data store.
	 */
	CHECKOUT_FAIL: 'checkout_fail',
	/**
	 * Event emitted when the checkout form is validated, before processing starts.
	 */
	CHECKOUT_VALIDATION: 'checkout_validation',
};

export const checkoutEventsEmitter = createEmitter();

export const checkoutEvents = {
	onCheckoutValidation: checkoutEventsEmitter.createSubscribeFunction(
		CHECKOUT_EVENTS.CHECKOUT_VALIDATION
	),
	onCheckoutSuccess: checkoutEventsEmitter.createSubscribeFunction(
		CHECKOUT_EVENTS.CHECKOUT_SUCCESS
	),
	onCheckoutFail: checkoutEventsEmitter.createSubscribeFunction(
		CHECKOUT_EVENTS.CHECKOUT_FAIL
	),
};
