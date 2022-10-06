/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import TYPES from './action-types';
import { Note, NoteQuery } from './types';

export function setNote( noteId: number, noteFields: Note ) {
	return {
		type: TYPES.SET_NOTE,
		noteId,
		noteFields,
	};
}

export function setNoteIsUpdating( noteId: number, isUpdating: boolean ) {
	return {
		type: TYPES.SET_NOTE_IS_UPDATING,
		noteId,
		isUpdating,
	};
}

export function setNotes( notes: Note[] ) {
	return {
		type: TYPES.SET_NOTES,
		notes,
	};
}

export function setNotesQuery( query: NoteQuery, noteIds: number[] ) {
	return {
		type: TYPES.SET_NOTES_QUERY,
		query,
		noteIds,
	};
}

export function setError( selector: string, error: unknown ) {
	return {
		type: TYPES.SET_ERROR,
		error,
		selector,
	};
}

export function setIsRequesting( selector: string, isRequesting: boolean ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		selector,
		isRequesting,
	};
}

export function* updateNote( noteId: number, noteFields: Partial< Note > ) {
	yield setIsRequesting( 'updateNote', true );
	yield setNoteIsUpdating( noteId, true );

	try {
		const url = `${ NAMESPACE }/admin/notes/${ noteId }`;
		const note: Note = yield apiFetch( {
			path: url,
			method: 'PUT',
			data: noteFields,
		} );
		yield setNote( noteId, note );
		yield setIsRequesting( 'updateNote', false );
		yield setNoteIsUpdating( noteId, false );
	} catch ( error ) {
		yield setError( 'updateNote', error );
		yield setIsRequesting( 'updateNote', false );
		yield setNoteIsUpdating( noteId, false );
		throw new Error();
	}
}

export function* triggerNoteAction( noteId: number, actionId: number ) {
	yield setIsRequesting( 'triggerNoteAction', true );

	const url = `${ NAMESPACE }/admin/notes/${ noteId }/action/${ actionId }`;
	try {
		const result: Note = yield apiFetch( {
			path: url,
			method: 'POST',
		} );
		yield updateNote( noteId, result );
		yield setIsRequesting( 'triggerNoteAction', false );
	} catch ( error ) {
		yield setError( 'triggerNoteAction', error );
		yield setIsRequesting( 'triggerNoteAction', false );
		throw new Error();
	}
}

export function* removeNote( noteId: number ) {
	yield setIsRequesting( 'removeNote', true );
	yield setNoteIsUpdating( noteId, true );

	try {
		const url = `${ NAMESPACE }/admin/notes/delete/${ noteId }`;
		const response: Note = yield apiFetch( {
			path: url,
			method: 'DELETE',
		} );
		yield setNote( noteId, response );
		yield setIsRequesting( 'removeNote', false );
		return response;
	} catch ( error ) {
		yield setError( 'removeNote', error );
		yield setIsRequesting( 'removeNote', false );
		yield setNoteIsUpdating( noteId, false );
		throw new Error();
	}
}

export function* removeAllNotes(
	query: {
		status?: string;
	} = {}
) {
	yield setIsRequesting( 'removeAllNotes', true );

	try {
		const url = addQueryArgs(
			`${ NAMESPACE }/admin/notes/delete/all`,
			query
		);
		const notes: Note[] = yield apiFetch( {
			path: url,
			method: 'DELETE',
		} );
		yield setNotes( notes );
		yield setIsRequesting( 'removeAllNotes', false );
		return notes;
	} catch ( error ) {
		yield setError( 'removeAllNotes', error );
		yield setIsRequesting( 'removeAllNotes', false );
		throw new Error();
	}
}

export function* batchUpdateNotes(
	noteIds: string[],
	noteFields: Partial< Note >
) {
	yield setIsRequesting( 'batchUpdateNotes', true );

	try {
		const url = `${ NAMESPACE }/admin/notes/update`;
		const notes: Note[] = yield apiFetch( {
			path: url,
			method: 'PUT',
			data: {
				noteIds,
				...noteFields,
			},
		} );
		yield setNotes( notes );
		yield setIsRequesting( 'batchUpdateNotes', false );
	} catch ( error ) {
		yield setError( 'updateNote', error );
		yield setIsRequesting( 'batchUpdateNotes', false );
		throw new Error();
	}
}

export type Action = ReturnType<
	| typeof setNote
	| typeof setNoteIsUpdating
	| typeof setNotes
	| typeof setNotesQuery
	| typeof setError
	| typeof setIsRequesting
>;

export type ActionDispatchers = DispatchFromMap< {
	updateNote: typeof updateNote;
	triggerNoteAction: typeof triggerNoteAction;
	removeNote: typeof removeNote;
	removeAllNotes: typeof removeAllNotes;
	batchUpdateNotes: typeof batchUpdateNotes;
} >;
