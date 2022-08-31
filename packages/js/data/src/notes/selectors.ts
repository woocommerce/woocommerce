/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { NoteState, NoteQuery } from './types';

export const getNotes = createSelector(
	( state: NoteState, query: NoteQuery ) => {
		const noteIds = state.noteQueries[ JSON.stringify( query ) ] || [];
		return noteIds.map( ( id ) => state.notes[ id ] );
	},
	( state: NoteState, query: NoteQuery ) => [
		state.noteQueries[ JSON.stringify( query ) ],
		state.notes,
	]
);

export const getNotesError = ( state: NoteState, selector: string ) => {
	return state.errors[ selector ] || false;
};

export const isNotesRequesting = ( state: NoteState, selector: string ) => {
	return state.requesting[ selector ] || false;
};
