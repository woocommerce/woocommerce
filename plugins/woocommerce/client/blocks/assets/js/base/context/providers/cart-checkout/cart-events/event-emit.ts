/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	emitterCallback,
	reducer,
	emitEvent,
	emitEventWithAbort,
	ActionType,
} from '../../../event-emit';

// These events are emitted when the Cart status is BEFORE_PROCESSING and AFTER_PROCESSING
// to enable third parties to hook into the cart process
const EVENTS = {
	PROCEED_TO_CHECKOUT: 'cart_proceed_to_checkout',
};

type EventEmittersType = Record< string, ReturnType< typeof emitterCallback > >;

/**
 * Receives a reducer dispatcher and returns an object with the
 * various event emitters for the payment processing events.
 *
 * Calling the event registration function with the callback will register it
 * for the event emitter and will return a dispatcher for removing the
 * registered callback (useful for implementation in `useEffect`).
 *
 * @param {Function} observerDispatch The emitter reducer dispatcher.
 * @return {Object} An object with the various payment event emitter registration functions
 */
const useEventEmitters = (
	observerDispatch: React.Dispatch< ActionType >
): EventEmittersType => {
	const eventEmitters = useMemo(
		() => ( {
			onProceedToCheckout: emitterCallback(
				EVENTS.PROCEED_TO_CHECKOUT,
				observerDispatch
			),
		} ),
		[ observerDispatch ]
	);
	return eventEmitters;
};

export { EVENTS, useEventEmitters, reducer, emitEvent, emitEventWithAbort };
