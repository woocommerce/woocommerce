/** @format */

const DEFAULT_STATE = {
	notes: {},
	ids: [],
};

export default function notesReducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case 'SET_NOTES':
			const { notes } = action;
			const notesMap = notes.reduce( ( map, note ) => {
				map[ note.id ] = note;
				return map;
			}, {} );
			return {
				...state,
				notes: Object.assign( {}, state.notes, notesMap ),
			};
	}

	return state;
}
