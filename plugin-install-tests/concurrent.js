/**
 * External dependencies
 */
import fetch from 'node-fetch';

/**
 * Internal dependencies
 */
import { clean } from './clean.js';
import config from './config.js';

clean();

const requestOptionsFor = ( plugin ) => {
	return {
		method: 'POST',
		body: '{"plugins":"' + plugin + '" }',
		headers: {
			'Content-Type': 'application/json',
		},
	};
};

console.time( 'Concurrent Requests took' );

try {
	await Promise.all( [
		fetch( config.API_URL, requestOptionsFor( 'woocommerce-payments' ) ),
		fetch( config.API_URL, requestOptionsFor( 'woocommerce-services' ) ),
		fetch( config.API_URL, requestOptionsFor( 'mailpoet' ) ),
		fetch( config.API_URL, requestOptionsFor( 'google-listings-and-ads' ) ),
		fetch(
			config.API_URL,
			requestOptionsFor( 'pinterest-for-woocommerce' )
		),
		fetch( config.API_URL, requestOptionsFor( 'tiktok-for-business' ) ),
	] );
} catch ( err ) {
	console.log( err );
}

console.timeEnd( 'Concurrent Requests took' );
