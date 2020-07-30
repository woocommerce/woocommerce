/**
 * External dependencies
 */
import { uniqueId } from 'lodash';

export const TYPES = {
	ADD_EVENT_CALLBACK: 'add_event_callback',
	REMOVE_EVENT_CALLBACK: 'remove_event_callback',
};

export const actions = {
	addEventCallback: ( eventType, callback, priority = 10 ) => {
		return {
			id: uniqueId(),
			type: TYPES.ADD_EVENT_CALLBACK,
			eventType,
			callback,
			priority,
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
export const reducer = (
	state = {},
	{ type, eventType, id, callback, priority }
) => {
	const newEvents = new Map( state[ eventType ] );
	switch ( type ) {
		case TYPES.ADD_EVENT_CALLBACK:
			newEvents.set( id, { priority, callback } );
			return {
				...state,
				[ eventType ]: newEvents,
			};
		case TYPES.REMOVE_EVENT_CALLBACK:
			newEvents.delete( id );
			return {
				...state,
				[ eventType ]: newEvents,
			};
	}
	return state;
};
