/**
 * Internal dependencies
 */
import type { RegisteredBlocks } from './types';

const registeredBlocks: RegisteredBlocks = {
	'woocommerce/checkout-fields-block': [],
	'woocommerce/checkout-totals-block': [],
	'woocommerce/checkout-contact-information-block': [
		{
			block: 'core/paragraph',
			component: null,
			force: false,
		},
	],
	'woocommerce/checkout-shipping-address-block': [
		{
			block: 'core/paragraph',
			component: null,
			force: false,
		},
	],
	'woocommerce/checkout-billing-address-block': [
		{
			block: 'core/paragraph',
			component: null,
			force: false,
		},
	],
	'woocommerce/checkout-shipping-methods-block': [
		{
			block: 'core/paragraph',
			component: null,
			force: false,
		},
	],
	'woocommerce/checkout-payment-methods-block': [
		{
			block: 'core/paragraph',
			component: null,
			force: false,
		},
	],
};

export { registeredBlocks };
