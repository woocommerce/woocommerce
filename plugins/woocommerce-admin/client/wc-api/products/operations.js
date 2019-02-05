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

function read( resourceNames, fetch = apiFetch ) {
	const filteredNames = resourceNames.filter( name => isResourcePrefix( name, 'product-query' ) );

	return filteredNames.map( async resourceName => {
		const query = getResourceIdentifier( resourceName );
		const url = `/wc/v3/products${ stringifyQuery( query ) }`;

		try {
			const products = await fetch( {
				path: url,
			} );

			const ids = products.map( product => product.id );
			const productResources = products.reduce( ( resources, product ) => {
				resources[ getResourceName( 'product', product.id ) ] = { data: product };
				return resources;
			}, {} );

			return {
				[ resourceName ]: {
					data: ids,
					totalCount: ids.length,
				},
				...productResources,
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

export default {
	read,
};
