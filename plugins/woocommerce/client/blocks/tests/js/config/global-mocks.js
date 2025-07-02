const { webcrypto } = require( 'node:crypto' );

global.crypto = webcrypto;

global.TextEncoder = require( 'util' ).TextEncoder;
global.TextDecoder = require( 'util' ).TextDecoder;

/**
 * Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will
 * likely run into this.
 */
global.wp = {};

require( '@wordpress/data' );

/**
 * wcSettings is required by @woocommerce/* packages.
 */
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
		ES: 'Spain',
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
			format: '{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}',
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
			format: '{company}\n{name}\n{address_1}\n{address_2}\n{city} {state_code} {postcode}\n{country}',
		},
		JP: {
			allowBilling: true,
			allowShipping: true,
			states: {
				JP28: 'Hyogo',
			},
			locale: {
				last_name: { priority: 10 },
				first_name: { priority: 20 },
				postcode: {
					priority: 65,
				},
				state: {
					label: 'Prefecture',
					priority: 66,
				},
				city: { priority: 67 },
				address_1: { priority: 68 },
				address_2: { priority: 69 },
			},
			format: '{postcode}\n{state} {city} {address_1}\n{address_2}\n{company}\n{last_name} {first_name}\n{country}',
		},
		GB: {
			allowBilling: true,
			allowShipping: true,
			states: {},
			locale: {
				postcode: { label: 'Postcode' },
				state: { label: 'County', required: false },
			},
		},
		ES: {
			allowBilling: true,
			allowShipping: true,
			states: {
				B: 'Barcelona',
				M: 'Madrid',
			},
			locale: {
				postcode: {
					required: false,
					hidden: true,
				},
				state: {
					label: 'Province',
				},
			},
			format: '{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}',
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
	defaultFields: {
		email: {
			label: 'Email address',
			optionalLabel: 'Email address (optional)',
			required: true,
			hidden: false,
			autocomplete: 'email',
			autocapitalize: 'none',
			type: 'email',
			index: 0,
		},
		country: {
			label: 'Country/Region',
			optionalLabel: 'Country/Region (optional)',
			required: true,
			hidden: false,
			autocomplete: 'country',
			index: 1,
		},
		first_name: {
			label: 'First name',
			optionalLabel: 'First name (optional)',
			required: true,
			hidden: false,
			autocomplete: 'given-name',
			autocapitalize: 'sentences',
			index: 10,
		},
		last_name: {
			label: 'Last name',
			optionalLabel: 'Last name (optional)',
			required: true,
			hidden: false,
			autocomplete: 'family-name',
			autocapitalize: 'sentences',
			index: 20,
		},
		company: {
			label: 'Company',
			optionalLabel: 'Company (optional)',
			required: false,
			hidden: true,
			autocomplete: 'organization',
			autocapitalize: 'sentences',
			index: 30,
		},
		address_1: {
			label: 'Address',
			optionalLabel: 'Address (optional)',
			required: true,
			hidden: false,
			autocomplete: 'address-line1',
			autocapitalize: 'sentences',
			index: 40,
		},
		address_2: {
			label: 'Apartment, suite, etc.',
			optionalLabel: 'Apartment, suite, etc. (optional)',
			required: false,
			hidden: false,
			autocomplete: 'address-line2',
			autocapitalize: 'sentences',
			index: 50,
		},
		city: {
			label: 'City',
			optionalLabel: 'City (optional)',
			required: true,
			hidden: false,
			autocomplete: 'address-level2',
			autocapitalize: 'sentences',
			index: 70,
		},
		state: {
			label: 'State/County',
			optionalLabel: 'State/County (optional)',
			required: true,
			hidden: false,
			autocomplete: 'address-level1',
			autocapitalize: 'sentences',
			index: 80,
		},
		postcode: {
			label: 'Postal code',
			optionalLabel: 'Postal code (optional)',
			required: true,
			hidden: false,
			autocomplete: 'postal-code',
			autocapitalize: 'characters',
			index: 90,
		},
		phone: {
			label: 'Phone',
			optionalLabel: 'Phone (optional)',
			required: false,
			hidden: true,
			type: 'tel',
			autocomplete: 'tel',
			autocapitalize: 'characters',
			index: 100,
		},
	},
	checkoutData: {
		order_id: 100,
		status: 'checkout-draft',
		order_key: 'wc_order_mykey',
		order_number: '100',
		customer_id: 1,
	},
};

global.jQuery = () => ( {
	on: () => void null,
	off: () => void null,
} );

global.IntersectionObserver = function () {
	return {
		root: null,
		rootMargin: '',
		thresholds: [],
		observe: () => void null,
		unobserve: () => void null,
		disconnect: () => void null,
		takeRecords: () => [],
	};
};

global.ResizeObserver = require( 'resize-observer-polyfill' );

global.__webpack_public_path__ = '';

Object.defineProperty( window, 'matchMedia', {
	writable: true,
	value: jest.fn().mockImplementation( ( query ) => ( {
		matches: false,
		media: query,
		onchange: null,
		addListener: jest.fn(), // Deprecated
		removeListener: jest.fn(), // Deprecated
		addEventListener: jest.fn(),
		removeEventListener: jest.fn(),
		dispatchEvent: jest.fn(),
	} ) ),
} );

/**
 * The following mock is for block integration tests that might render
 * components leveraging DOMRect. For example, the Cover block which now renders
 * its ResizableBox control via the BlockPopover component.
 */
if ( ! window.DOMRect ) {
	window.DOMRect = class DOMRect {};
}

/**
 * client-zip is meant to be used in a browser and is therefore released as an
 * ES6 module only, in order to use it in node environment, we need to mock it.
 * See: https://github.com/Touffy/client-zip/issues/28
 */
jest.mock( 'client-zip', () => ( {
	downloadZip: jest.fn(),
} ) );

/*
 * Enables `window.fetch()` in Jest tests.
 */
require( 'jest-fetch-mock' ).enableMocks();
