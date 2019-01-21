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
	const filteredNames = resourceNames.filter( name => isResourcePrefix( name, 'category-query' ) );

	return filteredNames.map( async resourceName => {
		const query = getResourceIdentifier( resourceName );
		const url = NAMESPACE + `/products/categories${ stringifyQuery( query ) }`;

		try {
			const categories = await fetch( {
				path: url,
			} );

			const ids = categories.map( category => category.id );
			const categoryResources = categories.reduce( ( resources, category ) => {
				resources[ getResourceName( 'category', category.id ) ] = { data: category };
				return resources;
			}, {} );

			return {
				[ resourceName ]: {
					data: ids,
					totalCount: ids.length,
				},
				...categoryResources,
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

export default {
	read,
};
