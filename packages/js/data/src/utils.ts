/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { BaseQueryParams } from './types/query-params';
import { fetchWithHeaders } from './controls';

export function getResourceName(
	prefix: string,
	identifier: Record< string, unknown > | string
) {
	const identifierString = JSON.stringify(
		identifier,
		Object.keys( identifier ).sort()
	);
	return `${ prefix }:${ identifierString }`;
}

/**
 * Generate a resource name for order totals count.
 *
 * It omits query parameters from the identifier that don't affect
 * totals values like pagination and response field filtering.
 *
 * @param {string} prefix Resource name prefix.
 * @param {Object} query  Query for order totals count.
 * @return {string} Resource name for order totals.
 */
export function getTotalCountResourceName(
	prefix: string,
	query: Record< string, unknown >
) {
	const { _fields, page, per_page, ...totalsQuery } = query;

	return getResourceName( prefix, totalsQuery );
}

export function getResourcePrefix( resourceName: string ) {
	const hasPrefixIndex = resourceName.indexOf( ':' );
	return hasPrefixIndex < 0
		? resourceName
		: resourceName.substring( 0, hasPrefixIndex );
}

export function isResourcePrefix( resourceName: string, prefix: string ) {
	const resourcePrefix = getResourcePrefix( resourceName );
	return resourcePrefix === prefix;
}

export function getResourceIdentifier( resourceName: string ) {
	const identifierString = resourceName.substring(
		resourceName.indexOf( ':' ) + 1
	);
	return JSON.parse( identifierString );
}

export function* request< Query extends BaseQueryParams, DataType >(
	namespace: string,
	query: Partial< Query >
) {
	const url: string = addQueryArgs( namespace, query );
	const isUnboundedRequest = query.per_page === -1;
	const fetch = isUnboundedRequest ? apiFetch : fetchWithHeaders;
	const response: DataType[] | ( { data: DataType[] } & Response ) =
		yield fetch( {
			path: url,
			method: 'GET',
		} );
	if ( isUnboundedRequest && ! ( 'data' in response ) ) {
		return { items: response, totalCount: response.length };
	}
	if ( ! isUnboundedRequest && 'data' in response ) {
		const totalCount = parseInt(
			response.headers.get( 'x-wp-total' ) || '',
			10
		);

		return { items: response.data, totalCount };
	}
}
