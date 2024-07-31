/**
 * External dependencies
 */
import { init } from '@woocommerce/remote-logging';

export const initRemoteLogging = () => {
	init( {
		errorRateLimitMs: 60000, // 1 minute
	} );

	// Throw an error to test remote logging.
	new Promise( ( resolve, reject ) => {
		reject( new Error( 'Asynchronous error' ) );
	} );
};
