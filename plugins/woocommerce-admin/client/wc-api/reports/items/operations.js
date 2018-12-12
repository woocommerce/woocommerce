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

// TODO: Remove once swagger endpoints are phased out.
const swaggerEndpoints = [ 'categories', 'coupons', 'customers', 'taxes' ];

const typeEndpointMap = {
	'report-items-query-orders': 'orders',
	'report-items-query-revenue': 'revenue',
	'report-items-query-products': 'products',
	'report-items-query-categories': 'categories',
	'report-items-query-coupons': 'coupons',
	'report-items-query-taxes': 'taxes',
	'report-items-query-variations': 'variations',
	'report-items-query-customers': 'customers',
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
			fetchArgs.url = SWAGGERNAMESPACE + 'reports/' + endpoint + stringifyQuery( query );
		} else {
			fetchArgs.path = NAMESPACE + '/reports/' + endpoint + stringifyQuery( query );
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
