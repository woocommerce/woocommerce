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
import { isResourcePrefix, getResourceIdentifier } from '../../utils';
import { NAMESPACE } from '../../constants';
import { SWAGGERNAMESPACE } from 'store/constants';

const statEndpoints = [ 'orders', 'revenue', 'products' ];
// TODO: Remove once swagger endpoints are phased out.
const swaggerEndpoints = [ 'categories', 'coupons', 'taxes' ];

function read( resourceNames, fetch = apiFetch ) {
	const filteredNames = resourceNames.filter( name =>
		isResourcePrefix( name, 'report-stats-query' )
	);

	return filteredNames.map( async resourceName => {
		const { endpoint, query } = getResourceIdentifier( resourceName );

		let apiPath = endpoint + stringifyQuery( query );

		if ( swaggerEndpoints.indexOf( endpoint ) >= 0 ) {
			apiPath = SWAGGERNAMESPACE + 'reports/' + endpoint + '/stats' + stringifyQuery( query );
		}

		if ( statEndpoints.indexOf( endpoint ) >= 0 ) {
			apiPath = NAMESPACE + '/reports/' + endpoint + '/stats' + stringifyQuery( query );
		}

		try {
			const response = await fetch( {
				parse: false,
				path: apiPath,
			} );

			const report = await response.json();
			// TODO: exclude these if using swagger?
			const totalResults = parseInt( response.headers.get( 'x-wp-total' ) );
			const totalPages = parseInt( response.headers.get( 'x-wp-totalpages' ) );

			return {
				[ resourceName ]: {
					data: report,
					totalResults,
					totalPages,
				},
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

export default {
	read,
};
