/**
 * Internal dependencies
 */
import TYPES from './action-types';

const notesReducer = (
	state = {
		errors: {},
		noteQueries: {},
		notes: {},
		requesting: {},
	},
	{
		error,
		isRequesting,
		isUpdating,
		noteFields,
		noteId,
		noteIds,
		notes,
		query,
		selector,
		type,
	}
) => {
	switch ( type ) {
		case TYPES.SET_NOTES:
			state = {
				...state,
				notes: {
					...state.notes,
					...notes.reduce( ( acc, item ) => {
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
					[ JSON.stringify( query ) ]: noteIds,
				},
			};
			break;
		case TYPES.SET_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					[ selector ]: error,
				},
			};
			break;
		case TYPES.SET_NOTE:
			state = {
				...state,
				notes: {
					...state.notes,
					[ noteId ]: noteFields,
				},
			};
			break;
		case TYPES.SET_NOTE_IS_UPDATING:
			state = {
				...state,
				notes: {
					...state.notes,
					[ noteId ]: {
						...state.notes[ noteId ],
						isUpdating,
					},
				},
			};
			break;
		case TYPES.SET_IS_REQUESTING:
			state = {
				...state,
				requesting: {
					...state.requesting,
					[ selector ]: isRequesting,
				},
			};
			break;
	}
	return state;
};

export default notesReducer;
