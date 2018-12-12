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
	const filteredNames = resourceNames.filter( name => isResourcePrefix( name, 'customers-query' ) );

	return filteredNames.map( async resourceName => {
		const query = getResourceIdentifier( resourceName );
		const url = `${ NAMESPACE }/customers${ stringifyQuery( query ) }`;

		try {
			const customers = await fetch( { path: url } );
			const ids = customers.map( customer => customer.id );
			const customerResources = customers.reduce( ( resources, customer ) => {
				resources[ getResourceName( 'customer', customer.id ) ] = { data: customer };
				return resources;
			}, {} );

			return {
				[ resourceName ]: {
					data: ids,
				},
				...customerResources,
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

export default {
	read,
};
