/**
 * Internal dependencies
 */
import '../global.d.ts';

// Mock scrollIntoView method for testing
Object.defineProperty( HTMLElement.prototype, 'scrollIntoView', {
	value: jest.fn(),
	writable: true,
} );

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
	fulfillment_statuses: {
		fulfilled: {
			label: 'Fulfilled',
			is_fulfilled: true,
			background_color: '#f0f0f0',
			text_color: '#6c757d',
		},
		unfulfilled: {
			label: 'Unfulfilled',
			is_fulfilled: false,
			background_color: '#fff3cd',
			text_color: '#856404',
		},
	},
	order_fulfillment_statuses: {
		fulfilled: {
			label: 'Fulfilled',
			is_fulfilled: true,
			background_color: '#d4edda',
			text_color: '#155724',
		},
		unfulfilled: {
			label: 'Unfulfilled',
			is_fulfilled: false,
			background_color: '#f8d7da',
			text_color: '#721c24',
		},
		partially_fulfilled: {
			label: 'Partially Fulfilled',
			is_fulfilled: false,
			background_color: '#fff3cd',
			text_color: '#856404',
		},
		no_fulfillments: {
			label: 'No Fulfillments',
			is_fulfilled: false,
			background_color: '#f0f0f0',
			text_color: '#6c757d',
		},
	},
};
