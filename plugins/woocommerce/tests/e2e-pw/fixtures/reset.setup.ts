/**
 * Internal dependencies
 */
import { test as setup } from './fixtures';
import type { RestApiResponse } from './fixtures';

/**
 * REST API error interface.
 */
interface RestApiError {
	data?: {
		message?: string;
	};
}

setup( 'reset site', async ( { restApi } ): Promise< void > => {
	setup.skip(
		process.env.DISABLE_SITE_RESET !== undefined,
		'Reset disabled by DISABLE_SITE_RESET environment variable'
	);

	try {
		const response = ( await restApi.get(
			`wc-cleanup/v1/reset`
		) ) as RestApiResponse;

		if ( response.statusCode === 200 ) {
			console.log( 'Site reset successful', response.statusCode );
		} else {
			console.error( 'ERROR! Site reset failed:', response.statusCode );
		}
	} catch ( error ) {
		const apiError = error as RestApiError;
		console.error(
			'ERROR! Site reset failed:',
			apiError.data?.message || error
		);
	}
} );
