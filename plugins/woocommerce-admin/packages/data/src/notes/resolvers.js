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

export function* getNotes( query = {} ) {
	const url = addQueryArgs( `${ NAMESPACE }/admin/notes`, query );

	try {
		const notes = yield apiFetch( {
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
