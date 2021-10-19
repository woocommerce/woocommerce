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

const EMIT_TYPES = {
	CHECKOUT_VALIDATION_BEFORE_PROCESSING:
		'checkout_validation_before_processing',
	CHECKOUT_AFTER_PROCESSING_WITH_SUCCESS:
		'checkout_after_processing_with_success',
	CHECKOUT_AFTER_PROCESSING_WITH_ERROR:
		'checkout_after_processing_with_error',
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
			onCheckoutAfterProcessingWithSuccess: emitterCallback(
				EMIT_TYPES.CHECKOUT_AFTER_PROCESSING_WITH_SUCCESS,
				observerDispatch
			),
			onCheckoutAfterProcessingWithError: emitterCallback(
				EMIT_TYPES.CHECKOUT_AFTER_PROCESSING_WITH_ERROR,
				observerDispatch
			),
			onCheckoutValidationBeforeProcessing: emitterCallback(
				EMIT_TYPES.CHECKOUT_VALIDATION_BEFORE_PROCESSING,
				observerDispatch
			),
		} ),
		[ observerDispatch ]
	);
	return eventEmitters;
};

export { EMIT_TYPES, useEventEmitters, reducer, emitEvent, emitEventWithAbort };
