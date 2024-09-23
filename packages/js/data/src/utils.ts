/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch, select } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { BaseQueryParams } from './types/query-params';
import { fetchWithHeaders } from './controls';
import { USER_STORE_NAME } from './user';
import { WCUser } from './user/types';
function replacer( _: string, value: unknown ) {
	if ( value ) {
		if ( Array.isArray( value ) ) {
			return [ ...value ].sort();
		}
		if ( typeof value === 'object' ) {
			return Object.entries( value )
				.sort()
				.reduce(
					( current, [ propKey, propVal ] ) => ( {
						...current,
						[ propKey ]: propVal,
					} ),
					{}
				);
		}
	}
	return value;
}

export function getResourceName( prefix: string, ...identifier: unknown[] ) {
	const identifierString = JSON.stringify( identifier, replacer ).replace(
		/\\"/g,
		'"'
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
	const { _fields, page, per_page, order, orderby, ...totalsQuery } = query;

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

/**
 * Utility function to check if the current user has a specific capability.
 *
 * @param {string} capability - The capability to check (e.g. 'manage_woocommerce').
 * @throws {Error} If the user does not have the required capability.
 */
export function* checkUserCapability( capability: string ) {
	const currentUser: WCUser< 'capabilities' > = yield select(
		USER_STORE_NAME,
		'getCurrentUser'
	);

	if ( ! currentUser.capabilities[ capability ] ) {
		throw new Error( `User does not have ${ capability } capability.` );
	}
}
