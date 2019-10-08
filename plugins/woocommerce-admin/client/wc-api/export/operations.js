/** @format */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getResourcePrefix } from '../utils';
import { NAMESPACE } from '../constants';

function update( resourceNames, data, fetch = apiFetch ) {
	return [ ...initiateExport( resourceNames, data, fetch ) ];
}

function initiateExport( resourceNames, data, fetch ) {
	const filteredNames = resourceNames.filter( name => {
		return name.startsWith( 'report-export-' );
	} );

	return filteredNames.map( async resourceName => {
		const prefix = getResourcePrefix( resourceName );
		const reportType = prefix.split( '-' ).pop();
		const url = NAMESPACE + '/reports/' + reportType + '/export';

		try {
			const result = await fetch( {
				path: url,
				method: 'POST',
				data: {
					report_args: data[ resourceName ],
					email: true,
				},
			} );

			return {
				[ resourceName ]: { [ result.status ]: result.message },
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

export default {
	update,
};
