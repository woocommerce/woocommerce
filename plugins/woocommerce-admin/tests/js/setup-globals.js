/** @format */

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

Object.defineProperty( global.wp, 'element', {
	get: () => require( 'gutenberg/packages/element' ),
} );

Object.defineProperty( global.wp, 'dom', {
	get: () => require( 'gutenberg/packages/dom' ),
} );

global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	locale: 'en-US',
	currency: { code: 'USD', precision: 2, symbol: '&#36;' },
};

Object.defineProperty( global.wp, 'date', {
	get: () => require( 'gutenberg/packages/date' ),
} );
