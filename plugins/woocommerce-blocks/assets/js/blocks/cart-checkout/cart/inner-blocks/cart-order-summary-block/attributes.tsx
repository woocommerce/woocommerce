/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export default {
	isShippingCalculatorEnabled: {
		type: 'boolean',
		default: getSetting( 'isShippingCalculatorEnabled', true ),
	},
	showRateAfterTaxName: {
		type: 'boolean',
		default: getSetting( 'displayCartPricesIncludingTax', false ),
	},
	lock: {
		type: 'object',
		default: {
			move: true,
			remove: true,
		},
	},
};
