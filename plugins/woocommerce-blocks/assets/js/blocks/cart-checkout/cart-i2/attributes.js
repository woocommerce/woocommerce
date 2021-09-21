/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export const blockName = 'woocommerce/cart-i2';
export const blockAttributes = {
	isPreview: {
		type: 'boolean',
		default: false,
		save: false,
	},
	isShippingCalculatorEnabled: {
		type: 'boolean',
		default: getSetting( 'isShippingCalculatorEnabled', true ),
	},
	checkoutPageId: {
		type: 'number',
		default: 0,
	},
	hasDarkControls: {
		type: 'boolean',
		default: getSetting( 'hasDarkEditorStyleSupport', false ),
	},
	showRateAfterTaxName: {
		type: 'boolean',
		default: true,
	},
};
