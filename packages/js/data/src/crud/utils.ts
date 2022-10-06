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
 * @param  templatePath Path with variable names.
 * @param  query        Item query.
 * @param  parameters   Array of items to replace in the templatePath.
 * @return string REST path.
 */
export const getRestPath = (
	templatePath: string,
	query: Partial< ItemQuery >,
	parameters: IdType[]
) => {
	let path = templatePath;

	path.match( /{(.*?)}/g )?.forEach( ( str, i ) => {
		path = path.replace( str, parameters[ i ].toString() );
	} );

	const regex = new RegExp( /{|}/ );
	if ( regex.test( path.toString() ) ) {
		throw new Error( 'Not all URL parameters were replaced' );
	}

	return addQueryArgs( path, query );
};

/**
 * Get a key from an item ID and optional parent.
 *
 * @param  query         Item Query.
 * @param  urlParameters Parameters used for URL.
 * @return string
 */
export const getKey = ( query: IdQuery, urlParameters: IdType[] = [] ) => {
	const id =
		typeof query === 'string' || typeof query === 'number'
			? query
			: query.id;

	if ( ! urlParameters.length ) {
		return id;
	}

	let prefix = '';
	urlParameters.forEach( ( param ) => {
		prefix = param + '/';
	} );

	return `${ prefix }${ id }`;
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

	return {
		id: query.id,
		key: getKey( query, urlParameters ),
	};
};

/**
 * Create a new function that adds in the namespace.
 *
 * @param  fn        Function to wrap.
 * @param  namespace Namespace to pass to last argument of function.
 * @return Wrapped function
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const applyNamespace = < T extends ( ...args: any[] ) => unknown >(
	fn: T,
	namespace: string,
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	defaultArgs: any[] = []
) => {
	return ( ...args: Parameters< T > ) => {
		defaultArgs.forEach( ( defaultArg, index ) => {
			// skip first item, as that is the state.
			if ( args[ index + 1 ] === undefined ) {
				args[ index + 1 ] = defaultArg;
			}
		} );
		return fn( ...args, namespace );
	};
};

/**
 * Get the key names from a namespace string.
 *
 * @param  namespace Namespace to get keys from.
 * @return Array of keys.
 */
export const getNamespaceKeys = ( namespace: string ) => {
	const keys: string[] = [];

	namespace.match( /{(.*?)}/g )?.forEach( ( match ) => {
		const key = match.substr( 1, match.length - 2 );
		keys.push( key );
	} );

	return keys;
};

/**
 * Get URL parameters from namespace and provided query.
 *
 * @param  namespace Namespace string to replace params in.
 * @param  query     Query object with key values.
 * @return Array of URL parameter values.
 */
export const getUrlParameters = (
	namespace: string,
	query: IdQuery | Partial< ItemQuery >
) => {
	if ( typeof query !== 'object' ) {
		return [];
	}

	const params: IdType[] = [];
	const keys = getNamespaceKeys( namespace );
	keys.forEach( ( key ) => {
		if ( query.hasOwnProperty( key ) ) {
			params.push( query[ key ] as IdType );
		}
	} );

	return params;
};

/**
 * Clean a query of all namespaced params.
 *
 * @param  query     Query to clean.
 * @param  namespace
 * @return Cleaned query object.
 */
export const cleanQuery = (
	query: Partial< ItemQuery >,
	namespace: string
) => {
	const cleaned = { ...query };

	const keys = getNamespaceKeys( namespace );
	keys.forEach( ( key ) => {
		delete cleaned[ key ];
	} );

	return cleaned;
};
