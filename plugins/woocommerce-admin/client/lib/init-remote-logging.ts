/**
 * External dependencies
 */
import { init, log } from '@woocommerce/remote-logging';

init( {
	errorRateLimitMs: 60000, // 1 minute
} );

setTimeout( () => {
	throw new Error( 'Synchronous error' );
}, 0 );

// new Promise( ( resolve, reject ) => {
// 	reject( new Error( 'Asynchronous error' ) );
// } );

// log( 'info', 'This is an info message', {
// 	extra: {
// 		foo: 'bar',
// 	},
// } );
