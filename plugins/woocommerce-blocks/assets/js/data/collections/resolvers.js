/**
 * External dependencies
 */
import { select } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { receiveCollection, DEFAULT_EMPTY_ARRAY } from './actions';
import { STORE_KEY as SCHEMA_STORE_KEY } from '../schema/constants';
import { STORE_KEY } from './constants';
import { apiFetchWithHeaders } from './controls';

/**
 * Resolver for retrieving a collection via a api route.
 *
 * @param {string} namespace
 * @param {string} resourceName
 * @param {Object} query
 * @param {Array}  ids
 */
export function* getCollection( namespace, resourceName, query, ids ) {
	const route = yield select(
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
	const { items = DEFAULT_EMPTY_ARRAY, headers } = yield apiFetchWithHeaders(
		route + queryString
	);
	yield receiveCollection( namespace, resourceName, queryString, ids, {
		items,
		headers,
	} );
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
	//we call this simply to do any resolution of the collection if necessary.
	yield select( STORE_KEY, 'getCollection', ...args );
}
