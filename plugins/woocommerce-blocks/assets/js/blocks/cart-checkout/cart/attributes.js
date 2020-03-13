/**
 * External dependencies
 */
import {
	IS_SHIPPING_CALCULATOR_ENABLED,
	IS_SHIPPING_COST_HIDDEN,
} from '@woocommerce/block-settings';

const blockAttributes = {
	isShippingCalculatorEnabled: {
		type: 'boolean',
		default: IS_SHIPPING_CALCULATOR_ENABLED,
	},
	isShippingCostHidden: {
		type: 'boolean',
		default: IS_SHIPPING_COST_HIDDEN,
	},
};

export default blockAttributes;
