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

Object.defineProperty( global.wp, 'element', {
	get: () => require( '@wordpress/element' ),
} );

Object.defineProperty( global.wp, 'date', {
	get: () => require( '@wordpress/date' ),
} );

global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	locale: 'en-US',
	currency: { code: 'USD', precision: 2, symbol: '&#36;' },
	date: {
		dow: 0,
	},
};

setLocaleData( { '': { domain: 'wc-admin', lang: 'en_US' } }, 'wc-admin' );
