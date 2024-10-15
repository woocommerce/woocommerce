/**
 * External dependencies
 */
import { init } from '@woocommerce/remote-logging';

export const initRemoteLogging = () => {
	init( {
		errorRateLimitMs: 60000, // 1 minute
	} );
};
