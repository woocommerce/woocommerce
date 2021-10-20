/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export default {
	showRateAfterTaxName: {
		type: 'boolean',
		default: getSetting( 'displayCartPricesIncludingTax', false ),
	},
	className: {
		type: 'string',
		default: '',
	},
	lock: {
		type: 'object',
		default: {
			move: true,
			remove: true,
		},
	},
};
