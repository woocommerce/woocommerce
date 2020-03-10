/**
 * Internal dependencies
 */
import { actions, reducer, emitEvent } from '../event_emit';

const EMIT_TYPES = {
	CHECKOUT_COMPLETE_WITH_SUCCESS: 'checkout_complete',
	CHECKOUT_COMPLETE_WITH_ERROR: 'checkout_complete_error',
	CHECKOUT_PROCESSING: 'checkout_processing',
};

/**
 * Receives a reducer dispatcher and returns an object with the
 * onCheckoutComplete callback registration function for the checkout emit
 * events.
 *
 * Calling the event registration function with the callback will register it
 * for the event emitter and will return a dispatcher for removing the
 * registered callback (useful for implementation in `useEffect`).
 *
 * @param {Function} dispatcher The emitter reducer dispatcher.
 *
 * @return {Object} An object with the `onCheckoutComplete` emmitter registration
 */
const emitterSubscribers = ( dispatcher ) => ( {
	onCheckoutCompleteSuccess: ( callback ) => {
		const action = actions.addEventCallback(
			EMIT_TYPES.CHECKOUT_COMPLETE_WITH_SUCCESS,
			callback
		);
		dispatcher( action );
		return () => {
			dispatcher(
				actions.removeEventCallback(
					EMIT_TYPES.CHECKOUT_COMPLETE_WITH_SUCCESS,
					action.id
				)
			);
		};
	},
	onCheckoutCompleteError: ( callback ) => {
		const action = actions.addEventCallback(
			EMIT_TYPES.CHECKOUT_COMPLETE_WITH_ERROR,
			callback
		);
		dispatcher( action );
		return () => {
			dispatcher(
				actions.removeEventCallback(
					EMIT_TYPES.CHECKOUT_COMPLETE_WITH_ERROR,
					action.id
				)
			);
		};
	},
	onCheckoutProcessing: ( callback ) => {
		const action = actions.addEventCallback(
			EMIT_TYPES.CHECKOUT_PROCESSING,
			callback
		);
		dispatcher( action );
		return () => {
			dispatcher(
				actions.removeEventCallback(
					EMIT_TYPES.CHECKOUT_PROCESSING,
					action.id
				)
			);
		};
	},
} );

export { EMIT_TYPES, emitterSubscribers, reducer, emitEvent };
