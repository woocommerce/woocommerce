/**
 * External dependencies
 */

import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Action } from './actions';
import { NoteState, Note } from './types';

const reducer: Reducer< NoteState, Action > = (
	state = {
		errors: {},
		noteQueries: {},
		notes: {},
		requesting: {},
	},
	action
) => {
	switch ( action.type ) {
		case TYPES.SET_NOTES:
			state = {
				...state,
				notes: {
					...state.notes,
					...action.notes.reduce< {
						[ id: string ]: Note;
					} >( ( acc, item ) => {
						acc[ item.id ] = item;
						return acc;
					}, {} ),
				},
			};
			break;
		case TYPES.SET_NOTES_QUERY:
			state = {
				...state,
				noteQueries: {
					...state.noteQueries,
					[ JSON.stringify( action.query ) ]: action.noteIds,
				},
			};
			break;
		case TYPES.SET_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					[ action.selector ]: action.error,
				},
			};
			break;
		case TYPES.SET_NOTE:
			state = {
				...state,
				notes: {
					...state.notes,
					[ action.noteId ]: action.noteFields,
				},
			};
			break;
		case TYPES.SET_NOTE_IS_UPDATING:
			state = {
				...state,
				notes: {
					...state.notes,
					[ action.noteId ]: {
						...state.notes[ action.noteId ],
						isUpdating: action.isUpdating,
					},
				},
			};
			break;
		case TYPES.SET_IS_REQUESTING:
			state = {
				...state,
				requesting: {
					...state.requesting,
					[ action.selector ]: action.isRequesting,
				},
			};
			break;
	}
	return state;
};

export default reducer;
export type State = ReturnType< typeof reducer >;
