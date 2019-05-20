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
import { isResourcePrefix, getResourceIdentifier } from '../utils';
import { NAMESPACE } from '../constants';

function read( resourceNames, fetch = apiFetch ) {
	const filteredNames = resourceNames.filter( name => isResourcePrefix( name, 'import-totals' ) );

	return filteredNames.map( async resourceName => {
		const query = getResourceIdentifier( resourceName );
		const fetchArgs = {
			parse: false,
			path: NAMESPACE + '/reports/import/totals' + stringifyQuery( query ),
		};

		try {
			const response = await fetch( fetchArgs );
			const totals = await response.json();

			return {
				[ resourceName ]: totals,
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

export default {
	read,
};
