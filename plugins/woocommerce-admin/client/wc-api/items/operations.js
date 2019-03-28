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
			const response = await fetch( {
				parse: false,
				path: url,
			} );

			const items = await response.json();
			const ids = items.map( item => item.id );
			const totalCount = parseInt( response.headers.get( 'x-wp-total' ) );
			const itemResources = items.reduce( ( resources, item ) => {
				resources[ getResourceName( `${ prefix }-item`, item.id ) ] = { data: item };
				return resources;
			}, {} );

			return {
				[ resourceName ]: {
					data: ids,
					totalCount,
				},
				...itemResources,
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

function update( resourceNames, data, fetch = apiFetch ) {
	const updateableTypes = [ 'products' ];
	const filteredNames = resourceNames.filter( name => {
		return updateableTypes.includes( name );
	} );

	return filteredNames.map( async resourceName => {
		const { id, ...itemData } = data[ resourceName ];
		const url = NAMESPACE + `/${ resourceName }/${ id }`;

		return fetch( { path: url, method: 'PUT', data: itemData } )
			.then( item => {
				return { [ resourceName + ':' + id ]: { data: item } };
			} )
			.catch( error => {
				return { [ resourceName + ':' + id ]: { error } };
			} );
	} );
}

export default {
	read,
	update,
};
