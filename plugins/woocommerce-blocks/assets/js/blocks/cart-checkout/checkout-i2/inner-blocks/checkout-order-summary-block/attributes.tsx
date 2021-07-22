/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export default {
	showRateAfterTaxName: {
		type: 'boolean',
		default: getSetting( 'displayCartPricesIncludingTax', false ),
	},
};
