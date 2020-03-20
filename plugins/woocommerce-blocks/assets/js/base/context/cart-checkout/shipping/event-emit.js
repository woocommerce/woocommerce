/**
 * Internal dependencies
 */
import { actions, reducer, emitEvent } from '../event-emit';

const EMIT_TYPES = {
	SHIPPING_RATES_SUCCESS: 'shipping_rates_success',
	SHIPPING_RATES_FAIL: 'shipping_rates_fail',
	SHIPPING_RATE_SELECT_SUCCESS: 'shipping_rate_select_success',
	SHIPPING_RATE_SELECT_FAIL: 'shipping_rate_select_fail',
};

/**
 * Receives a reducer dispatcher and returns an object with the onSuccess and
 * onFail callback registration points for the shipping option emit events.
 *
 * Calling the event registration function with the callback will register it
 * for the event emitter and will return a dispatcher for removing the
 * registered callback (useful for implementation in `useEffect`).
 *
 * @param {Function} dispatcher A reducer dispatcher
 * @return {Object} An object with `onSuccess` and `onFail` emitter registration.
 */
const emitterSubscribers = ( dispatcher ) => ( {
	onSuccess: ( callback ) => {
		const action = actions.addEventCallback(
			EMIT_TYPES.SHIPPING_RATES_SUCCESS,
			callback
		);
		dispatcher( action );
		return () => {
			dispatcher(
				actions.removeEventCallback(
					EMIT_TYPES.SHIPPING_RATES_SUCCESS,
					action.id
				)
			);
		};
	},
	onFail: ( callback ) => {
		const action = actions.removeEventCallback(
			EMIT_TYPES.SHIPPING_RATES_FAIL,
			callback
		);
		dispatcher( action );
		return () => {
			dispatcher( EMIT_TYPES.SHIPPING_RATES_FAIL, action.id );
		};
	},
	onSelectSuccess: ( callback ) => {
		const action = actions.addEventCallback(
			EMIT_TYPES.SHIPPING_RATE_SELECT_SUCCESS,
			callback
		);
		dispatcher( action );
		return () => {
			dispatcher(
				actions.removeEventCallback(
					EMIT_TYPES.SHIPPING_RATE_SELECT_SUCCESS,
					action.id
				)
			);
		};
	},
	onSelectFail: ( callback ) => {
		const action = actions.addEventCallback(
			EMIT_TYPES.SHIPPING_RATE_SELECT_FAIL,
			callback
		);
		dispatcher( action );
		return () => {
			dispatcher(
				actions.removeEventCallback(
					EMIT_TYPES.SHIPPING_RATE_SELECT_FAIL,
					action.id
				)
			);
		};
	},
} );

export { EMIT_TYPES, emitterSubscribers, reducer, emitEvent };
