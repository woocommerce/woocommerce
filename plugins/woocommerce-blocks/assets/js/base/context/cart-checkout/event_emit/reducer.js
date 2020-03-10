/**
 * External dependencies
 */
import { omit, uniqueId } from 'lodash';

export const TYPES = {
	ADD_EVENT_CALLBACK: 'add_event_callback',
	REMOVE_EVENT_CALLBACK: 'remove_event_callback',
};

export const actions = {
	addEventCallback: ( eventType, callback ) => {
		return {
			id: uniqueId(),
			type: TYPES.ADD_EVENT_CALLBACK,
			eventType,
			callback,
		};
	},
	removeEventCallback: ( eventType, id ) => {
		return {
			id,
			type: TYPES.REMOVE_EVENT_CALLBACK,
			eventType,
		};
	},
};

/**
 * Emits events on registered observers for the provided type and passes along
 * the provided data.
 *
 * @param {Object} observers The registered observers to omit to.
 * @param {string} eventType The event type being emitted.
 * @param {*}      data      Data passed along to the observer when it is
 *                           invoked.
 */
export const emitEvent = ( observers, eventType, data ) => {
	const observersByType = observers[ eventType ] || [];
	let hasError = false;
	observersByType.forEach( ( observer ) => {
		if ( typeof observer === 'function' ) {
			hasError = !! observer( data, hasError );
		}
	} );
	return hasError;
};

/**
 * Handles actions for emmitters
 *
 * @param {Object} state  Current state.
 * @param {Object} action Incoming action object
 */
export const reducer = ( state = {}, { type, eventType, id, callback } ) => {
	switch ( type ) {
		case TYPES.ADD_EVENT_CALLBACK:
			return {
				...state,
				[ eventType ]: {
					...state[ eventType ],
					[ id ]: callback,
				},
			};
		case TYPES.REMOVE_EVENT_CALLBACK:
			return {
				...state,
				[ eventType ]: omit( state[ eventType ], [ id ] ),
			};
	}
	return state;
};
