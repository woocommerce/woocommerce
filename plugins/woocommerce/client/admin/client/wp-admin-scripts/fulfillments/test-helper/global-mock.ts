/**
 * Internal dependencies
 */
import '../global.d.ts';

// This needs to be defined before importing the component
global.window.wcFulfillmentSettings = {
	providers: {
		ups: {
			label: 'UPS',
			icon: '',
			value: 'ups',
		},
		dhl: {
			label: 'DHL',
			icon: '',
			value: 'dhl',
		},
	},
	currency_symbols: {
		USD: '$',
		EUR: '€',
	},
	statuses: {
		fulfilled: 'Fulfilled',
		unfulfilled: 'Unfulfilled',
		partially_fulfilled: 'Partially Fulfilled',
		no_fulfillments: 'No Fulfillments',
	},
};
