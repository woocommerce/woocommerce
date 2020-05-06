/**
 * @typedef {import('@woocommerce/type-defs/billing').BillingData} BillingData
 * @typedef {import('@woocommerce/type-defs/contexts').BillingDataContext} BillingDataContext
 */

/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { mapValues } from 'lodash';
import { decodeEntities } from '@wordpress/html-entities';

const checkoutData = getSetting( 'checkoutData', {} );

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

const billingAddress = mapValues( checkoutData.billing_address, ( value ) =>
	decodeEntities( value )
);

/**
 * @type {BillingData}
 */
export const DEFAULT_STATE = {
	...DEFAULT_BILLING_DATA,
	...billingAddress,
};

/**
 * @type {BillingDataContext}
 */
export const DEFAULT_BILLING_CONTEXT_DATA = {
	billingData: DEFAULT_BILLING_DATA,
	setBillingData: () => null,
};
