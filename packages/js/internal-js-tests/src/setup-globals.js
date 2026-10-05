import ResizeObserver from 'resize-observer-polyfill';
import * as element from '@wordpress/element';
import * as data from '@wordpress/data';
import { vi } from 'vitest';

/**
 * External dependencies
 */
import { TextDecoder, TextEncoder } from 'node:util';
import { setLocaleData } from '@wordpress/i18n';
import { registerStore } from '@wordpress/data';
import 'regenerator-runtime/runtime';

/**
 * Internal dependencies
 */
import config from '../../../../plugins/woocommerce/client/admin/config/development.json';

if ( typeof global.TextEncoder === 'undefined' ) {
	global.TextEncoder = TextEncoder;
}

if ( typeof global.TextDecoder === 'undefined' ) {
	global.TextDecoder = TextDecoder;
}

// Due to the dependency @wordpress/compose which introduces the use of
// ResizeObserver this global mock is required for some tests to work.
global.ResizeObserver = ResizeObserver;

// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will
// likely run into this.
global.wp = {
	shortcode: {
		next() {},
		regexp: vi.fn().mockReturnValue( new RegExp() ),
	},
};

global.wc = {};

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
	admin: {
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
			performanceIndicators: [
				{
					chart: 'total_sales',
					label: 'Total sales',
					stat: 'revenue/total_sales',
				},
				{
					chart: 'net_revenue',
					label: 'Net sales',
					stat: 'revenue/net_revenue',
				},
				{
					chart: 'orders_count',
					label: 'Orders',
					stat: 'orders/orders_count',
				},
				{
					chart: 'items_sold',
					label: 'Items sold',
					stat: 'products/items_sold',
				},
			],
		},
	},
};

Object.assign( global.wp, { element, data } );

// Check if test is jsdom or node
if ( global.window ) {
	window.wcAdminFeatures = config && config.features ? config.features : {};
}

setLocaleData(
	{ '': { domain: 'woocommerce', lang: 'en_US' } },
	'woocommerce'
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
