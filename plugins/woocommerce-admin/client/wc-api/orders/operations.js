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
	return [ ...readOrders( resourceNames, fetch ), ...readOrderQueries( resourceNames, fetch ) ];
}

function readOrderQueries( resourceNames, fetch ) {
	const filteredNames = resourceNames.filter( name => isResourcePrefix( name, 'order-query' ) );

	return filteredNames.map( resourceName => {
		const query = getResourceIdentifier( resourceName );
		const url = `${ NAMESPACE }/orders${ stringifyQuery( query ) }`;

		return fetch( { path: url } )
			.then( orders => {
				const ids = orders.map( order => order.id );
				const orderResources = orders.reduce( ( resources, order ) => {
					resources[ getResourceName( 'order', order.id ) ] = { data: order };
					return resources;
				}, {} );

				return {
					[ resourceName ]: { data: ids },
					...orderResources,
				};
			} )
			.catch( error => {
				return { [ resourceName ]: { error } };
			} );
	} );
}

function readOrders( resourceNames, fetch ) {
	const filteredNames = resourceNames.filter( name => isResourcePrefix( name, 'order' ) );
	return filteredNames.map( resourceName => readOrder( resourceName, fetch ) );
}

function readOrder( resourceName, fetch ) {
	const id = getResourceIdentifier( resourceName );
	const url = `${ NAMESPACE }/orders/${ id }`;

	return fetch( { path: url } )
		.then( order => {
			return { [ resourceName ]: { data: order } };
		} )
		.catch( error => {
			return { [ resourceName ]: { error } };
		} );
}

export default {
	read,
};
