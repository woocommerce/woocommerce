/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export const blockName = 'woocommerce/cart';
export const blockAttributes = {
	isPreview: {
		type: 'boolean',
		default: false,
		save: false,
	},
	hasDarkControls: {
		type: 'boolean',
		default: getSetting( 'hasDarkEditorStyleSupport', false ),
	},
	// Deprecated - here for v1 migration support
	isShippingCalculatorEnabled: {
		type: 'boolean',
		default: getSetting( 'isShippingCalculatorEnabled', true ),
	},
	checkoutPageId: {
		type: 'number',
		default: 0,
	},
	showRateAfterTaxName: {
		type: 'boolean',
		default: true,
	},
	align: {
		type: 'string',
	},
};
