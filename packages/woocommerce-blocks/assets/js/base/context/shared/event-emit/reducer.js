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
 * @param {string} action.type The type of action.
 * @param {string} action.eventType What type of event is emitted.
 * @param {string} action.id The id for the event.
 * @param {function(any):any} action.callback The registered callback for the event.
 * @param {number} action.priority The priority for the event.
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
