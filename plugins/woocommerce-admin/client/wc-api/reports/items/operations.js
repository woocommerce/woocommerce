/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getResourceIdentifier, getResourcePrefix } from '../../utils';
import { NAMESPACE } from '../../constants';

const typeEndpointMap = {
	'report-items-query-orders': 'orders',
	'report-items-query-revenue': 'revenue',
	'report-items-query-products': 'products',
	'report-items-query-categories': 'categories',
	'report-items-query-coupons': 'coupons',
	'report-items-query-taxes': 'taxes',
	'report-items-query-variations': 'variations',
	'report-items-query-downloads': 'downloads',
	'report-items-query-customers': 'customers',
	'report-items-query-stock': 'stock',
	'report-items-query-performance-indicators': 'performance-indicators',
};

function read( resourceNames, fetch = apiFetch ) {
	const filteredNames = resourceNames.filter( ( name ) => {
		const prefix = getResourcePrefix( name );
		return Boolean( typeEndpointMap[ prefix ] );
	} );

	return filteredNames.map( async ( resourceName ) => {
		const prefix = getResourcePrefix( resourceName );
		const endpoint = typeEndpointMap[ prefix ];
		const query = getResourceIdentifier( resourceName );
		const fetchArgs = {
			parse: false,
			path: addQueryArgs( `${ NAMESPACE }/reports/${ endpoint }`, query ),
		};

		try {
			const response = await fetch( fetchArgs );
			const report = await response.json();
			const totalResults = parseInt(
				response.headers.get( 'x-wp-total' ),
				10
			);
			const totalPages = parseInt(
				response.headers.get( 'x-wp-totalpages' ),
				10
			);

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
