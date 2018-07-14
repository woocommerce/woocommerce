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

[
	'components',
	'utils',
	'blocks',
	'editor',
	'data',
	'core-data',
	'edit-post',
	'viewport',
	'plugins',
].forEach( entryPointName => {
	Object.defineProperty( global.wp, entryPointName, {
		get: () => require( 'gutenberg/' + entryPointName ),
	} );
} );

[
	'element',
	'dom',
	'keycodes',
	'deprecated',
].forEach( packageName => {
	Object.defineProperty( global.wp, packageName, {
		get: () => require( 'gutenberg/packages/' + packageName ),
	} );
} );

global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	locale: 'en-US',
	currency: { code: 'USD', precision: 2, symbol: '&#36;' },
	date: {
		dow: 0,
	},
};

Object.defineProperty( global.wp, 'date', {
	get: () => require( 'gutenberg/packages/date' ),
} );

setLocaleData( { '': { domain: 'wc-admin', lang: 'en_US' } }, 'wc-admin' );
