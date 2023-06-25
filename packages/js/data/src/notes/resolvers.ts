/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setNotes, setNotesQuery, setError } from './actions';
import { NoteQuery, Note } from './types';

export function* getNotes( query: NoteQuery = {} ) {
	const url = addQueryArgs( `${ NAMESPACE }/admin/notes`, query );

	try {
		const notes: Note[] = yield apiFetch( {
			path: url,
		} );

		yield setNotes( notes );
		yield setNotesQuery(
			query,
			notes.map( ( note ) => note.id )
		);
	} catch ( error ) {
		yield setError( 'getNotes', error );
	}
}
