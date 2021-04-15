/**
 * External dependencies
 */
import { setLocaleData } from '@wordpress/i18n';
import { registerStore } from '@wordpress/data';

// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will
// likely run into this.
global.wp = {
	shortcode: {
		next() {},
		regexp: jest.fn().mockReturnValue( new RegExp() ),
	},
};

global.wc = {};

const wordPressPackages = [ 'element', 'date', 'data' ];

const wooCommercePackages = [
	'components',
	'csv',
	'currency',
	'date',
	'navigation',
	'number',
	'data',
];

global.wcTracks = {
	isEnabled: false,
};

// aliases
global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	countries: [],
	currency: {
		code: 'USD',
		precision: 2,
		symbol: '$',
		symbolPosition: 'left',
		decimalSeparator: '.',
		priceFormat: '%1$s%2$s',
		thousandSeparator: ',',
	},
	defaultDateRange: 'period=month&compare=previous_year',
	date: {
		dow: 0,
	},
	locale: {
		siteLocale: 'en_US',
		userLocale: 'en_US',
		weekdaysShort: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
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
	wcAdminSettings: {
		woocommerce_actionable_order_statuses: [],
		woocommerce_excluded_report_order_statuses: [],
	},
	dataEndpoints: {
		countries: [],
		performanceIndicators: [
			{
				chart: 'total_sales',
				label: 'Total Sales',
				stat: 'revenue/total_sales',
			},
			{
				chart: 'net_revenue',
				label: 'Net Sales',
				stat: 'revenue/net_revenue',
			},
			{
				chart: 'orders_count',
				label: 'Orders',
				stat: 'orders/orders_count',
			},
			{
				chart: 'items_sold',
				label: 'Items Sold',
				stat: 'products/items_sold',
			},
		],
	},
};

wordPressPackages.forEach( ( lib ) => {
	Object.defineProperty( global.wp, lib, {
		get: () => require( `@wordpress/${ lib }` ),
	} );
} );

wooCommercePackages.forEach( ( lib ) => {
	Object.defineProperty( global.wc, lib, {
		get: () => require( `@woocommerce/${ lib }` ),
	} );
} );

const config = require( '../../config/development.json' );

// Check if test is jsdom or node
if ( global.window ) {
	window.wcAdminFeatures = config && config.features ? config.features : {};
}

setLocaleData(
	{ '': { domain: 'woocommerce-admin', lang: 'en_US' } },
	'woocommerce-admin'
);

// Mock core/notices store for components dispatching core notices
registerStore( 'core/notices', {
	reducer: () => {
		return {};
	},
	actions: {
		createNotice: () => {},
	},
	selectors: {},
} );
