/**
 * External dependencies
 */
import { controls } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { receiveCollection, receiveCollectionError } from './actions';
import { SCHEMA_STORE_KEY } from '../schema';
import { STORE_KEY, DEFAULT_EMPTY_ARRAY } from './constants';
import { apiFetchWithHeadersControl } from '../shared-controls';

/**
 * Read a Last-Modified header as a Unix timestamp in seconds.
 *
 * The Store API sends an HTTP-date; a bare Unix timestamp is still accepted.
 *
 * @param {string} value Header value.
 * @return {number} Unix timestamp in seconds, or 0 when the value cannot be read.
 */
const parseLastModifiedHeader = ( value ) => {
	if ( /^\d+$/.test( value ) ) {
		return parseInt( value, 10 );
	}
	const time = Date.parse( value );
	return Number.isNaN( time ) ? 0 : Math.floor( time / 1000 );
};

/**
 * Check if the store needs invalidating due to a change in last modified headers.
 *
 * @param {number} timestamp Last update timestamp.
 */
function* invalidateModifiedCollection( timestamp ) {
	const lastModified = yield controls.resolveSelect(
		STORE_KEY,
		'getCollectionLastModified'
	);

	if ( ! lastModified ) {
		yield controls.dispatch( STORE_KEY, 'receiveLastModified', timestamp );
	} else if ( timestamp > lastModified ) {
		yield controls.dispatch( STORE_KEY, 'invalidateResolutionForStore' );
		yield controls.dispatch( STORE_KEY, 'receiveLastModified', timestamp );
	}
}

/**
 * Resolver for retrieving a collection via a api route.
 *
 * @param {string} namespace
 * @param {string} resourceName
 * @param {Object} query
 * @param {Array}  ids
 */
export function* getCollection( namespace, resourceName, query, ids ) {
	const route = yield controls.resolveSelect(
		SCHEMA_STORE_KEY,
		'getRoute',
		namespace,
		resourceName,
		ids
	);
	const queryString = addQueryArgs( '', query );
	if ( ! route ) {
		yield receiveCollection( namespace, resourceName, queryString, ids );
		return;
	}

	try {
		const { response = DEFAULT_EMPTY_ARRAY, headers } =
			yield apiFetchWithHeadersControl( { path: route + queryString } );

		if ( headers && headers.get && headers.has( 'last-modified' ) ) {
			const lastModified = parseLastModifiedHeader(
				headers.get( 'last-modified' )
			);
			// Do any invalidation before the collection is received to prevent
			// this query running again.
			if ( lastModified ) {
				yield invalidateModifiedCollection( lastModified );
			}
		}

		yield receiveCollection( namespace, resourceName, queryString, ids, {
			items: response,
			headers,
		} );
	} catch ( error ) {
		yield receiveCollectionError(
			namespace,
			resourceName,
			queryString,
			ids,
			error
		);
	}
}

/**
 * Resolver for retrieving a specific collection header for the given arguments
 *
 * Note: This triggers the `getCollection` resolver if it hasn't been resolved
 * yet.
 *
 * @param {string} header
 * @param {string} namespace
 * @param {string} resourceName
 * @param {Object} query
 * @param {Array}  ids
 */
export function* getCollectionHeader(
	header,
	namespace,
	resourceName,
	query,
	ids
) {
	// feed the correct number of args in for the select so we don't resolve
	// unnecessarily. Any undefined args will be excluded. This is important
	// because resolver resolution is cached by both number and value of args.
	const args = [ namespace, resourceName, query, ids ].filter(
		( arg ) => typeof arg !== 'undefined'
	);
	// we call this simply to do any resolution of the collection if necessary.
	yield controls.resolveSelect( STORE_KEY, 'getCollection', ...args );
}
