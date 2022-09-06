/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';
import { sanitize } from 'dompurify';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setNotes, setNotesQuery, setError } from './actions';
import { NoteQuery, Note } from './types';

let notesExceededWarningShown = false;

export function* getNotes( query: NoteQuery = {} ) {
	const url = addQueryArgs( `${ NAMESPACE }/admin/notes`, query );

	try {
		const notes: Note[] = yield apiFetch( {
			path: url,
		} );

		if ( ! notesExceededWarningShown ) {
			const noteNames = notes.reduce< string[] >( ( filtered, note ) => {
				const content = sanitize( note.content, {
					ALLOWED_TAGS: [],
				} );
				if ( content.length > 320 ) {
					filtered.push( note.name );
				}
				return filtered;
			}, [] );

			if ( noteNames.length ) {
				/* eslint-disable no-console */
				console.warn(
					sprintf(
						/* translators: %s = link to developer blog */
						__(
							'WooCommerce Admin will soon limit inbox note contents to 320 characters. For more information, please visit %s. The following notes currently exceeds that limit:',
							'woocommerce'
						),
						'https://developer.woocommerce.com/?p=10749'
					) +
						'\n' +
						noteNames
							.map( ( name, idx ) => {
								return `  ${ idx + 1 }. ${ name }`;
							} )
							.join( '\n' )
				);
				/* eslint-enable no-console */
				notesExceededWarningShown = true;
			}
		}

		yield setNotes( notes );
		yield setNotesQuery(
			query,
			notes.map( ( note ) => note.id )
		);
	} catch ( error ) {
		yield setError( 'getNotes', error );
	}
}
