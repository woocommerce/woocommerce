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
import { getResourceIdentifier, getResourcePrefix, getResourceName } from '../utils';
import { NAMESPACE } from '../constants';

const typeEndpointMap = {
	'items-query-categories': 'products/categories',
	'items-query-customers': 'customers',
	'items-query-coupons': 'coupons',
	'items-query-orders': 'orders',
	'items-query-products': 'products',
	'items-query-taxes': 'taxes',
};

function read( resourceNames, fetch = apiFetch ) {
	const filteredNames = resourceNames.filter( name => {
		const prefix = getResourcePrefix( name );
		return Boolean( typeEndpointMap[ prefix ] );
	} );

	return filteredNames.map( async resourceName => {
		const prefix = getResourcePrefix( resourceName );
		const endpoint = typeEndpointMap[ prefix ];
		const query = getResourceIdentifier( resourceName );
		const url = NAMESPACE + `/${ endpoint }${ stringifyQuery( query ) }`;

		try {
			const items = await fetch( {
				path: url,
			} );

			const ids = items.map( item => item.id );
			const itemResources = items.reduce( ( resources, item ) => {
				resources[ getResourceName( `${ prefix }-item`, item.id ) ] = { data: item };
				return resources;
			}, {} );

			return {
				[ resourceName ]: {
					data: ids,
					totalCount: ids.length,
				},
				...itemResources,
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

export default {
	read,
};
