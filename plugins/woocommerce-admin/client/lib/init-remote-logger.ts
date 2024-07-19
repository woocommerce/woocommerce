/**
 * External dependencies
 */
import { init } from '@woocommerce/remote-logging';

init( {
	rateLimitInterval: 60000, // 1 minute
} );

// setTimeout( () => {
// 	throw new Error( 'Test error' );
// }, 1000 );
