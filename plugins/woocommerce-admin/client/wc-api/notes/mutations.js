/** @format */

const updateNote = operations => ( noteId, noteFields ) => {
	const resourceKey = 'note';
	operations.update( [ resourceKey ], { [ resourceKey ]: { noteId, ...noteFields } } );
};

export default {
	updateNote,
};
