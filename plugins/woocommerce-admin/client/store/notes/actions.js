/** @format */

export default {
	setNotes( notes, query ) {
		return {
			type: 'SET_NOTES',
			notes,
			query: query || {},
		};
	},
	setNotesError( query ) {
		return {
			type: 'SET_NOTES_ERROR',
			query: query || {},
		};
	},
};
