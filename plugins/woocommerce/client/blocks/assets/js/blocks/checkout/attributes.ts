/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export const blockName = 'woocommerce/checkout';
export const blockAttributes = {
	hasDarkControls: {
		type: 'boolean',
		default: getSetting( 'hasDarkEditorStyleSupport', false ),
	},
	showRateAfterTaxName: {
		type: 'boolean',
		default: getSetting( 'displayCartPricesIncludingTax', false ),
	},
};

/**
 * @deprecated here for v1 migration support
 */
export const deprecatedAttributes = {
	showOrderNotes: {
		type: 'boolean',
		default: true,
	},
	showPolicyLinks: {
		type: 'boolean',
		default: true,
	},
	showReturnToCart: {
		type: 'boolean',
		default: true,
	},
	cartPageId: {
		type: 'number',
		default: 0,
	},
	showCompanyField: {
		type: 'boolean',
		default: false,
	},
	requireCompanyField: {
		type: 'boolean',
		default: false,
	},
	showApartmentField: {
		type: 'boolean',
		default: true,
	},
	requireApartmentField: {
		type: 'boolean',
		default: false,
	},
	showPhoneField: {
		type: 'boolean',
		default: true,
	},
	requirePhoneField: {
		type: 'boolean',
		default: false,
	},
};
