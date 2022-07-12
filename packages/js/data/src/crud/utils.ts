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
	urlParameters: IdType[] = []
) => {
	const pattern = /\{(.*?)}/;
	const path = urlParameters.reduce( ( str, param ) => {
		return str.toString().replace( pattern, param.toString() );
	}, templatePath );

	const regex = new RegExp( pattern );
	if ( regex.test( path.toString() ) ) {
		throw new Error( 'Not all URL parameters were replaced' );
	}

	return addQueryArgs( path.toString(), query );
};

/**
 * Get a key from an item ID and optional parent.
 *
 * @param  id        Item ID.
 * @param  parent_id Optional parent ID.
 * @return string
 */
export const getKey = ( id: IdType, parent_id: IdType | null | undefined ) => {
	if ( ! parent_id ) {
		return id;
	}

	return `${ parent_id }/${ id }`;
};

/**
 * Parse an ID query into a ID string.
 *
 * @param  query Id Query
 * @return string ID.
 */
export const parseId = ( query: IdQuery ) => {
	if ( typeof query === 'string' || typeof query === 'number' ) {
		return {
			id: query,
			parent_id: null,
			key: query,
		};
	}

	const key = getKey( query.id, query.parent_id );
	return { ...query, key };
};
