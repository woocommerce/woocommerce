/**
 * Internal dependencies
 */
import {
	emitterCallback,
	reducer,
	emitEvent,
	emitEventWithAbort,
} from '../../shared/event-emit';

const EMIT_TYPES = {
	CHECKOUT_BEFORE_PROCESSING: 'checkout_before_processing',
	CHECKOUT_AFTER_PROCESSING_WITH_SUCCESS:
		'checkout_after_processing_with_success',
	CHECKOUT_AFTER_PROCESSING_WITH_ERROR:
		'checkout_after_processing_with_error',
};

/**
 * Receives a reducer dispatcher and returns an object with the
 * callback registration function for the checkout emit
 * events.
 *
 * Calling the event registration function with the callback will register it
 * for the event emitter and will return a dispatcher for removing the
 * registered callback (useful for implementation in `useEffect`).
 *
 * @param {Function} dispatcher The emitter reducer dispatcher.
 *
 * @return {Object} An object with the checkout emmitter registration
 */
const emitterObservers = ( dispatcher ) => ( {
	onCheckoutAfterProcessingWithSuccess: emitterCallback(
		EMIT_TYPES.CHECKOUT_AFTER_PROCESSING_WITH_SUCCESS,
		dispatcher
	),
	onCheckoutAfterProcessingWithError: emitterCallback(
		EMIT_TYPES.CHECKOUT_AFTER_PROCESSING_WITH_ERROR,
		dispatcher
	),
	onCheckoutBeforeProcessing: emitterCallback(
		EMIT_TYPES.CHECKOUT_BEFORE_PROCESSING,
		dispatcher
	),
} );

export { EMIT_TYPES, emitterObservers, reducer, emitEvent, emitEventWithAbort };
