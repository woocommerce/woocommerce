// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will likely run into this.
global.wp = {};

// Set up our settings global.
global.wc_product_block_data = {};

const wordPressPackages = [
	'blocks',
	'components',
	'date',
	'editor',
	'element',
	'i18n',
];

wordPressPackages.forEach( ( lib ) => {
	Object.defineProperty( global.wp, lib, {
		get: () => require( `@wordpress/${ lib }` ),
	} );
} );
