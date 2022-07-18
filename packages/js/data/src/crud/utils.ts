/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { IdQuery, IdType, ItemQuery } from './types';

/**
 * Get a REST path given a template path and URL params.
 *
 * @param  templatePath  Path with variable names.
 * @param  query         Item query.
 * @param  urlParameters Array of items to replace in the templatePath.
 * @return string REST path.
 */
export const getRestPath = (
	templatePath: string,
	query: Partial< ItemQuery >,
	parameters: {
		[ key: string ]: IdType;
	} = {}
) => {
	const path = Object.keys( parameters ).reduce( ( str, param ) => {
		return str
			.toString()
			.replace( `{${ param }}`, parameters[ param ].toString() );
	}, templatePath );

	const pattern = /\{${str}}/;
	const regex = new RegExp( pattern );
	if ( regex.test( path.toString() ) ) {
		throw new Error( 'Not all URL parameters were replaced' );
	}

	return addQueryArgs( path.toString(), query );
};

/**
 * Get a key from an item ID and optional parent.
 *
 * @param  query         Item Query.
 * @param  urlParameters Parameters used for URL.
 * @return string
 */
export const getKey = ( query: IdQuery, urlParameters: IdType[] = [] ) => {
	if ( typeof query === 'string' || typeof query === 'number' ) {
		return query;
	}

	if ( ! urlParameters.length ) {
		return query.id;
	}

	let prefix = '';
	urlParameters.forEach( ( param ) => {
		prefix = prefix + query[ param ] + '/';
	} );

	return `${ prefix }${ query.id }`;
};

/**
 * Get parameters from query.
 *
 * @param  query         Query object to look for params.
 * @param  parameterKeys Keys to look for.
 * @return object        Object of found parameters in query.
 */
export const getParamsFromQuery = (
	query: Partial< ItemQuery >,
	parameterKeys: IdType[]
) => {
	const params = {} as { [ key: string ]: IdType };
	parameterKeys.forEach( ( key ) => {
		params[ key ] = query[ key ] as IdType;
	} );
	return params;
};

/**
 * Parse an ID query into a ID string.
 *
 * @param  query Id Query
 * @return string ID.
 */
export const parseId = ( query: IdQuery, urlParameters: IdType[] = [] ) => {
	if ( typeof query === 'string' || typeof query === 'number' ) {
		return {
			id: query,
			key: query,
		};
	}

	const params = getParamsFromQuery( query, urlParameters );
	const key = getKey( query, urlParameters );
	return { id: query.id, ...params, key };
};

/**
 * Delete params from a query.
 *
 * @param  query         Query to delete from.
 * @param  parameterKeys Keys to delete.
 */
export const deleteParamsFromQuery = (
	query: Partial< ItemQuery >,
	parameterKeys: string[]
) => {
	parameterKeys.forEach( ( key ) => {
		delete query[ key ];
	} );
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const applyUrlParameters = < T extends ( ...args: any[] ) => unknown >(
	fn: T,
	urlParameters: IdType[]
) => {
	return ( ...args: Parameters< T > ) => {
		fn( ...args, urlParameters );
	};
};
