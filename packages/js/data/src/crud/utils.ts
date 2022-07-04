/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { ItemQuery } from './types';

export const getRestPath = (
	templatePath: string,
	query: Partial< ItemQuery >,
	urlParameters: string[] = []
) => {
	const path = urlParameters.reduce( ( str, param ) => {
		return str.replace( /\{(.*?)}/, param );
	}, templatePath );
	return addQueryArgs( path, query );
};
