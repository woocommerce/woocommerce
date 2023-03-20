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

console.time( 'Plugin request took' );
await fetch( config.API_URL, {
	method: 'POST',
	body: '{"plugins":"woocommerce-payments,woocommerce-services,mailpoet,google-listings-and-ads,pinterest-for-woocommerce,tiktok-for-business"}',
	headers: {
		'Content-Type': 'application/json',
	},
} );

console.timeEnd( 'Plugin request took' );
