/**
 * External dependencies
 */
import { reject } from 'lodash';
import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import onSubKey from './utils/on-sub-key';
import { Action } from './actions';
import { Notices } from './types';

/**
 * Reducer returning the next notices state. The notices state is an array of notice objects
 *
 * @param {Array}  state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
const notices: Reducer< Notices, Action > = ( state = [], action ) => {
	switch ( action.type ) {
		case 'CREATE_NOTICE':
			// Avoid duplicates on ID.
			return [
				...reject( state, { id: action.notice.id } ),
				action.notice,
			];

		case 'REMOVE_NOTICE':
			return reject( state, { id: action.id } );
	}
	return state;
};

export type State = {
	[ context: string ]: Notices;
};

// Creates a combined reducer object where each key is a context, its value an array of notice objects.
export default onSubKey( 'context' )( notices );
