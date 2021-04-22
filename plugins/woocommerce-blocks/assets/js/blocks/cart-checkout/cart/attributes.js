/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const blockAttributes = {
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
};

export default blockAttributes;
