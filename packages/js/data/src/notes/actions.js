/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import TYPES from './action-types';

export function* triggerNoteAction( noteId, actionId ) {
	yield setIsRequesting( 'triggerNoteAction', true );

	const url = `${ NAMESPACE }/admin/notes/${ noteId }/action/${ actionId }`;
	try {
		const result = yield apiFetch( { path: url, method: 'POST' } );
		yield updateNote( noteId, result );
		yield setIsRequesting( 'triggerNoteAction', false );
	} catch ( error ) {
		yield setError( 'triggerNoteAction', error );
		yield setIsRequesting( 'triggerNoteAction', false );
		throw new Error();
	}
}

export function* removeNote( noteId ) {
	yield setIsRequesting( 'removeNote', true );
	yield setNoteIsUpdating( noteId, true );

	try {
		const url = `${ NAMESPACE }/admin/notes/delete/${ noteId }`;
		const response = yield apiFetch( { path: url, method: 'DELETE' } );
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

export function* removeAllNotes( query = {} ) {
	yield setIsRequesting( 'removeAllNotes', true );

	try {
		const url = addQueryArgs(
			`${ NAMESPACE }/admin/notes/delete/all`,
			query
		);
		const notes = yield apiFetch( { path: url, method: 'DELETE' } );
		yield setNotes( notes );
		yield setIsRequesting( 'removeAllNotes', false );
		return notes;
	} catch ( error ) {
		yield setError( 'removeAllNotes', error );
		yield setIsRequesting( 'removeAllNotes', false );
		throw new Error();
	}
}

export function* batchUpdateNotes( noteIds, noteFields ) {
	yield setIsRequesting( 'batchUpdateNotes', true );

	try {
		const url = `${ NAMESPACE }/admin/notes/update`;
		const notes = yield apiFetch( {
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

export function* updateNote( noteId, noteFields ) {
	yield setIsRequesting( 'updateNote', true );
	yield setNoteIsUpdating( noteId, true );

	try {
		const url = `${ NAMESPACE }/admin/notes/${ noteId }`;
		const note = yield apiFetch( {
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

export function setNote( noteId, noteFields ) {
	return {
		type: TYPES.SET_NOTE,
		noteId,
		noteFields,
	};
}

export function setNoteIsUpdating( noteId, isUpdating ) {
	return {
		type: TYPES.SET_NOTE_IS_UPDATING,
		noteId,
		isUpdating,
	};
}

export function setNotes( notes ) {
	return {
		type: TYPES.SET_NOTES,
		notes,
	};
}

export function setNotesQuery( query, noteIds ) {
	return {
		type: TYPES.SET_NOTES_QUERY,
		query,
		noteIds,
	};
}

export function setError( selector, error ) {
	return {
		type: TYPES.SET_ERROR,
		error,
		selector,
	};
}

export function setIsRequesting( selector, isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		selector,
		isRequesting,
	};
}
