/** @format */

const updateNote = operations => ( noteId, noteFields ) => {
	const resourceKey = 'note';
	operations.update( [ resourceKey ], { [ resourceKey ]: { noteId, ...noteFields } } );
};

const triggerNoteAction = operations => ( noteId, actionId ) => {
	const resourceKey = 'note-action';
	operations.update( [ resourceKey ], { [ resourceKey ]: { noteId, actionId } } );
};

export default {
	updateNote,
	triggerNoteAction,
};
