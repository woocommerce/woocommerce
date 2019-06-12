/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { isResourcePrefix, getResourceIdentifier, getResourceName } from '../utils';
import { NAMESPACE } from '../constants';

function read( resourceNames, fetch = apiFetch ) {
	return [ ...readNotes( resourceNames, fetch ), ...readNoteQueries( resourceNames, fetch ) ];
}

function update( resourceNames, data, fetch = apiFetch ) {
	return [
		...updateNote( resourceNames, data, fetch ),
		...triggerAction( resourceNames, data, fetch ),
	];
}

function readNoteQueries( resourceNames, fetch ) {
	const filteredNames = resourceNames.filter( name => isResourcePrefix( name, 'note-query' ) );

	return filteredNames.map( async resourceName => {
		const query = getResourceIdentifier( resourceName );
		const url = `${ NAMESPACE }/admin/notes${ stringifyQuery( query ) }`;

		try {
			const response = await fetch( {
				parse: false,
				path: url,
			} );

			const notes = await response.json();
			const totalCount = parseInt( response.headers.get( 'x-wp-total' ) );
			const ids = notes.map( note => note.id );
			const noteResources = notes.reduce( ( resources, note ) => {
				resources[ getResourceName( 'note', note.id ) ] = { data: note };
				return resources;
			}, {} );

			return {
				[ resourceName ]: {
					data: ids,
					totalCount,
				},
				...noteResources,
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

function readNotes( resourceNames, fetch ) {
	const filteredNames = resourceNames.filter( name => isResourcePrefix( name, 'note' ) );
	return filteredNames.map( resourceName => readNote( resourceName, fetch ) );
}

function readNote( resourceName, fetch ) {
	const id = getResourceIdentifier( resourceName );
	const url = `${ NAMESPACE }/admin/notes/${ id }`;

	return fetch( { path: url } )
		.then( note => {
			return { [ resourceName ]: { data: note } };
		} )
		.catch( error => {
			return { [ resourceName ]: { error } };
		} );
}

function updateNote( resourceNames, data, fetch ) {
	const resourceName = 'note';
	if ( resourceNames.includes( resourceName ) ) {
		const { noteId, ...noteFields } = data[ resourceName ];
		const url = `${ NAMESPACE }/admin/notes/${ noteId }`;
		return [
			fetch( { path: url, method: 'PUT', data: noteFields } )
				.then( note => {
					return { [ resourceName + ':' + noteId ]: { data: note } };
				} )
				.catch( error => {
					return { [ resourceName + ':' + noteId ]: { error } };
				} ),
		];
	}
	return [];
}

function triggerAction( resourceNames, data, fetch ) {
	const resourceName = 'note-action';
	if ( resourceNames.includes( resourceName ) ) {
		const { noteId, actionId } = data[ resourceName ];
		const url = `${ NAMESPACE }/admin/notes/${ noteId }/action/${ actionId }`;
		return [
			fetch( { path: url, method: 'POST' } )
				.then( note => {
					return { [ 'note:' + noteId ]: { data: note } };
				} )
				.catch( error => {
					return { [ 'note:' + noteId ]: { error } };
				} ),
		];
	}
	return [];
}

export default {
	read,
	update,
	triggerAction,
};
