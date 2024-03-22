// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will likely run into this.
global.wp = {};
require( '@wordpress/data' );
// wcSettings is required by @woocommerce/* packages
global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	addressFormats: {
		default:
			'{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
		JP: '{postcode}\n{state} {city} {address_1}\n{address_2}\n{company}\n{last_name} {first_name}\n{country}',
		CA: '{company}\n{name}\n{address_1}\n{address_2}\n{city} {state_code} {postcode}\n{country}',
	},
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
	defaultFields: {
		first_name: {
			label: 'First name',
			optionalLabel: 'First name (optional)',
			autocomplete: 'given-name',
			autocapitalize: 'sentences',
			required: true,
			hidden: false,
			index: 10,
		},
		last_name: {
			label: 'Last name',
			optionalLabel: 'Last name (optional)',
			autocomplete: 'family-name',
			autocapitalize: 'sentences',
			required: true,
			hidden: false,
			index: 20,
		},
		company: {
			label: 'Company',
			optionalLabel: 'Company (optional)',
			autocomplete: 'organization',
			autocapitalize: 'sentences',
			required: false,
			hidden: false,
			index: 30,
		},
		address_1: {
			label: 'Address',
			optionalLabel: 'Address (optional)',
			autocomplete: 'address-line1',
			autocapitalize: 'sentences',
			required: true,
			hidden: false,
			index: 40,
		},
		address_2: {
			label: 'Apartment, suite, etc.',
			optionalLabel: 'Apartment, suite, etc. (optional)',
			autocomplete: 'address-line2',
			autocapitalize: 'sentences',
			required: false,
			hidden: false,
			index: 50,
		},
		country: {
			label: 'Country/Region',
			optionalLabel: 'Country/Region (optional)',
			autocomplete: 'country',
			required: true,
			hidden: false,
			index: 60,
		},
		city: {
			label: 'City',
			optionalLabel: 'City (optional)',
			autocomplete: 'address-level2',
			autocapitalize: 'sentences',
			required: true,
			hidden: false,
			index: 70,
		},
		state: {
			label: 'State/County',
			optionalLabel: 'State/County (optional)',
			autocomplete: 'address-level1',
			autocapitalize: 'sentences',
			required: true,
			hidden: false,
			index: 80,
		},
		postcode: {
			label: 'Postal code',
			optionalLabel: 'Postal code (optional)',
			autocomplete: 'postal-code',
			autocapitalize: 'characters',
			required: true,
			hidden: false,
			index: 90,
		},
		phone: {
			label: 'Phone',
			optionalLabel: 'Phone (optional)',
			autocomplete: 'tel',
			type: 'tel',
			required: true,
			hidden: false,
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
		observe: () => void null,
		unobserve: () => void null,
	};
};

global.__webpack_public_path__ = '';
