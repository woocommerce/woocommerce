/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { omit } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getResourcePrefix, getResourceIdentifier } from '../utils';
import { NAMESPACE } from '../constants';

const typeEndpointMap = {
	'import-status': 'reports/import/status',
	'import-totals': 'reports/import/totals',
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
			path: NAMESPACE + `/${ endpoint }${ stringifyQuery( omit( query, [ 'timestamp' ] ) ) }`,
		};

		try {
			const response = await fetch( fetchArgs );
			const data = await response.json();

			return {
				[ resourceName ]: { data },
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

export default {
	read,
};
