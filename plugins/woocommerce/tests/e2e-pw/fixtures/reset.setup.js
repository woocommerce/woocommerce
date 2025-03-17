/**
 * Internal dependencies
 */
import { test as setup } from './fixtures';

setup( 'reset site', async ( { restApi } ) => {
	setup.skip(
		process.env.DISABLE_SITE_RESET !== undefined,
		'Reset disabled by DISABLE_SITE_RESET environment variable'
	);

	try {
		const response = await restApi.get( `wc-cleanup/v1/reset` );

		if ( response.statusCode === 200 ) {
			console.log( 'Site reset successful', response.statusCode );
		} else {
			console.error( 'ERROR! Site reset failed:', response.statusCode );
		}
	} catch ( error ) {
		console.error(
			'ERROR! Site reset failed:',
			error.data?.message || error
		);
	}
} );
