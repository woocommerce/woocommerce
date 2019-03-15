// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will likely run into this.
global.wp = {};

// Set up our settings global.
global.wc_product_block_data = {
	placeholderImgSrc: 'placeholder.png',
	thumbnail_size: '300',
};

// wcSettings is required by @woocommerce/* packages
global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	locale: 'en-US',
	currency: { code: 'USD', precision: 2, symbol: '&#36;' },
	date: {
		dow: 0,
	},
	orderStatuses: {
		pending: 'Pending payment',
		processing: 'Processing',
		'on-hold': 'On hold',
		completed: 'Completed',
		cancelled: 'Cancelled',
		refunded: 'Refunded',
		failed: 'Failed',
	},
	l10n: {
		userLocale: 'en_US',
		weekdaysShort: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
	},
};

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
