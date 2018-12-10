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
import { getResourceIdentifier, getResourcePrefix } from '../../utils';
import { NAMESPACE } from '../../constants';
import { SWAGGERNAMESPACE } from 'store/constants';

const statEndpoints = [ 'orders', 'revenue', 'products' ];
// TODO: Remove once swagger endpoints are phased out.
const swaggerEndpoints = [ 'categories', 'coupons', 'taxes' ];

const typeEndpointMap = {
	'report-stats-query-orders': 'orders',
	'report-stats-query-revenue': 'revenue',
	'report-stats-query-products': 'products',
	'report-stats-query-categories': 'categories',
	'report-stats-query-coupons': 'coupons',
	'report-stats-query-taxes': 'taxes',
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

		const fetchArgs = {
			parse: false,
		};

		if ( swaggerEndpoints.indexOf( endpoint ) >= 0 ) {
			fetchArgs.url = SWAGGERNAMESPACE + 'reports/' + endpoint + '/stats' + stringifyQuery( query );
		} else if ( statEndpoints.indexOf( endpoint ) >= 0 ) {
			fetchArgs.path = NAMESPACE + '/reports/' + endpoint + '/stats' + stringifyQuery( query );
		} else {
			fetchArgs.path = endpoint + stringifyQuery( query );
		}

		try {
			const response = await fetch( fetchArgs );
			const report = await response.json();
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
