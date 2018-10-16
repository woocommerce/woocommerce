/** @format */

/**
 * External dependencies
 */
import { merge } from 'lodash';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import { getJsonString } from 'store/utils';

const DEFAULT_STATE = {};

export default function notesReducer( state = DEFAULT_STATE, action ) {
	const queryKey = getJsonString( action.query );

	switch ( action.type ) {
		case 'SET_NOTES':
			return merge( {}, state, {
				[ queryKey ]: action.notes,
			} );
		case 'SET_NOTES_ERROR':
			return merge( {}, state, {
				[ queryKey ]: ERROR,
			} );
	}

	return state;
}
