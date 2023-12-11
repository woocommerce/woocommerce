// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will likely run into this.
global.wp = {};
require( '@wordpress/data' );
// wcSettings is required by @woocommerce/* packages
global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	shippingMethodsExist: true,
	currency: {
		code: 'USD',
		precision: 2,
		symbol: '&#36;',
	},
	currentUserIsAdmin: false,
	date: {
		dow: 0,
	},
	hasFilterableProducts: true,
	orderStatuses: {
		pending: 'Pending payment',
		processing: 'Processing',
		'on-hold': 'On hold',
		completed: 'Completed',
		cancelled: 'Cancelled',
		refunded: 'Refunded',
		failed: 'Failed',
	},
	placeholderImgSrc: 'placeholder.jpg',
	productCount: 101,
	locale: {
		siteLocale: 'en_US',
		userLocale: 'en_US',
		weekdaysShort: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
	},
	countries: {
		AT: 'Austria',
		CA: 'Canada',
		GB: 'United Kingdom (UK)',
	},
	countryData: {
		AT: {
			states: {},
			allowBilling: true,
			allowShipping: true,
			locale: {
				postcode: { priority: 65 },
				state: { required: false, hidden: true },
			},
		},
		CA: {
			states: {
				ON: 'Ontario',
			},
			allowBilling: true,
			allowShipping: true,
			locale: {
				postcode: { label: 'Postal code' },
				state: { label: 'Province' },
			},
		},
		GB: {
			states: {},
			allowBilling: true,
			allowShipping: true,
			locale: {
				postcode: { label: 'Postcode' },
				state: { label: 'County', required: false },
			},
		},
	},
	storePages: {
		myaccount: {
			id: 0,
			title: '',
			permalink: '',
		},
		shop: {
			id: 0,
			title: '',
			permalink: '',
		},
		cart: {
			id: 0,
			title: '',
			permalink: '',
		},
		checkout: {
			id: 0,
			title: '',
			permalink: 'https://local/checkout/',
		},
		privacy: {
			id: 0,
			title: '',
			permalink: '',
		},
		terms: {
			id: 0,
			title: '',
			permalink: '',
		},
	},
	attributes: [
		{
			attribute_id: '1',
			attribute_name: 'color',
			attribute_label: 'Color',
			attribute_type: 'select',
			attribute_orderby: 'menu_order',
			attribute_public: 0,
		},
		{
			attribute_id: '2',
			attribute_name: 'size',
			attribute_label: 'Size',
			attribute_type: 'select',
			attribute_orderby: 'menu_order',
			attribute_public: 0,
		},
	],
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

global.__webpack_public_path__ = '';
