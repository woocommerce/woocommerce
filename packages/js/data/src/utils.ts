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
	identifier: Record< string, unknown >
) {
	const identifierString = JSON.stringify(
		identifier,
		Object.keys( identifier ).sort()
	);
	return `${ prefix }:${ identifierString }`;
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
	const response:
		| DataType[]
		| ( { data: DataType[] } & Response ) = yield fetch( {
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
