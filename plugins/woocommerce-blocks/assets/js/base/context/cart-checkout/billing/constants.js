/**
 * @typedef {import('@woocommerce/type-defs/billing').BillingData} BillingData
 * @typedef {import('@woocommerce/type-defs/contexts').BillingDataContext} BillingDataContext
 */

/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * @type {BillingData}
 */
const HYDRATED_BILLING_DATA = getSetting( 'billingData' );

/**
 * @type {BillingData}
 */
export const DEFAULT_BILLING_DATA = {
	first_name: '',
	last_name: '',
	company: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
	country: '',
	email: '',
	phone: '',
};

/**
 * @type {BillingData}
 */
export const DEFAULT_STATE = {
	...DEFAULT_BILLING_DATA,
	...HYDRATED_BILLING_DATA,
};

/**
 * @type {BillingDataContext}
 */
export const DEFAULT_BILLING_CONTEXT_DATA = {
	billingData: DEFAULT_BILLING_DATA,
	setBillingData: () => null,
};
