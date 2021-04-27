/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	currentlyRunning: {},
	errorMessages: [],
	messages: {},
	status: '',
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.ADD_MESSAGE:
			if ( ! action.status ) {
				action.status = 'info';
			}
			return {
				...state,
				messages: {
					...state.messages,
					[ action.source ]: {
						message: action.message,
						status: action.status,
					},
				},
			};
		case TYPES.REMOVE_MESSAGE:
			const messages = { ...state.messages };
			delete messages[ action.source ];
			return {
				...state,
				messages,
			};
		case TYPES.SET_STATUS:
			return {
				...state,
				status: action.status,
			};
		case TYPES.ADD_CURRENTLY_RUNNING:
			return {
				...state,
				currentlyRunning: {
					...state,
					[ action.command ]: true,
				},
			};
		case TYPES.REMOVE_CURRENTLY_RUNNING:
			return {
				...state,
				currentlyRunning: {
					...state,
					[ action.command ]: false,
				},
			};
		default:
			return state;
	}
};

export default reducer;
