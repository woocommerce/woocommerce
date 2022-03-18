/**
 * External dependencies
 */
import createSelector from 'rememo';

export const getNotes = createSelector(
	( state, query ) => {
		const noteIds = state.noteQueries[ JSON.stringify( query ) ] || [];
		return noteIds.map( ( id ) => state.notes[ id ] );
	},
	( state, query ) => [
		state.noteQueries[ JSON.stringify( query ) ],
		state.notes,
	]
);

export const getNotesError = ( state, selector ) => {
	return state.errors[ selector ] || false;
};

export const isNotesRequesting = ( state, selector ) => {
	return state.requesting[ selector ] || false;
};
