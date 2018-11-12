/** @format */

/**
 * External dependencies
 */
import { setLocaleData } from '@wordpress/i18n';

// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will
// likely run into this.
global.wp = {
	shortcode: {
		next() {},
		regexp: jest.fn().mockReturnValue( new RegExp() ),
	},
};

global.wc = {};

const wordPressPackages = [
	'element',
	'date',
];

const wooCommercePackages = [
	'components',
	'csv',
	'currency',
	'date',
	'navigation',
];

wordPressPackages.forEach( lib => {
	Object.defineProperty( global.wp, lib, {
		get: () => require( `@wordpress/${ lib }` ),
	} );
} );

wooCommercePackages.forEach( lib => {
	Object.defineProperty( global.wc, lib, {
		get: () => require( `@woocommerce/${ lib }` ),
	} );
} );

global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	locale: 'en-US',
	currency: { code: 'USD', precision: 2, symbol: '&#36;' },
	date: {
		dow: 0,
	},
	orderStatuses: {
		'wc-pending': 'Pending payment',
		'wc-processing': 'Processing',
		'wc-on-hold': 'On hold',
		'wc-completed': 'Completed',
		'wc-cancelled': 'Cancelled',
		'wc-refunded': 'Refunded',
		'wc-failed': 'Failed',
	},
};

setLocaleData( { '': { domain: 'wc-admin', lang: 'en_US' } }, 'wc-admin' );
