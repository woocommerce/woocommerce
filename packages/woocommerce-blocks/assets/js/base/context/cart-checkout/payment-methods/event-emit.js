/**
 * Internal dependencies
 */
import {
	reducer,
	emitEvent,
	emitEventWithAbort,
	emitterCallback,
} from '../event-emit';

const EMIT_TYPES = {
	PAYMENT_PROCESSING: 'payment_processing',
};

/**
 * Receives a reducer dispatcher and returns an object with the
 * various event emitters for the payment processing events.
 *
 * Calling the event registration function with the callback will register it
 * for the event emitter and will return a dispatcher for removing the
 * registered callback (useful for implementation in `useEffect`).
 *
 * @param {Function} dispatcher The emitter reducer dispatcher.
 *
 * @return {Object} An object with the various payment event emitter
 *                  registration functions
 */
const emitterSubscribers = ( dispatcher ) => ( {
	onPaymentProcessing: emitterCallback(
		EMIT_TYPES.PAYMENT_PROCESSING,
		dispatcher
	),
} );

export {
	EMIT_TYPES,
	emitterSubscribers,
	reducer,
	emitEvent,
	emitEventWithAbort,
};
