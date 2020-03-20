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
