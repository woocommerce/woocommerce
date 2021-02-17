// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will likely run into this.
global.wp = {};

// wcSettings is required by @woocommerce/* packages
global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	shippingMethodsExist: true,
	countries: [],
	currency: {
		code: 'USD',
		precision: 2,
		symbol: '&#36;',
	},
	currentUserIsAdmin: false,
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
	locale: {
		siteLocale: 'en_US',
		userLocale: 'en_US',
		weekdaysShort: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
	},
	shippingCountries: {
		AT: 'Austria',
		CA: 'Canada',
		GB: 'United Kingdom (UK)',
	},
	shippingStates: {
		CA: {
			ON: 'Ontario',
		},
	},
	countryLocale: {
		GB: {
			postcode: { label: 'Postcode' },
			state: { label: 'County', required: false },
		},
		AT: {
			postcode: { priority: 65 },
			state: { required: false, hidden: true },
		},
		CA: {
			postcode: { label: 'Postal code' },
			state: { label: 'Province' },
		},
	},
};

global.jQuery = () => ( {
	on: () => void null,
	off: () => void null,
} );

global.IntersectionObserver = function () {
	return {
		observe: () => void null,
		unobserve: () => void null,
	};
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
