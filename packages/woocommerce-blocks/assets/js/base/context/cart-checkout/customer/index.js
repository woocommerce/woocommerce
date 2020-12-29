/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';
import { useCustomerData } from '@woocommerce/base-hooks';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').CustomerDataContext} CustomerDataContext
 * @typedef {import('@woocommerce/type-defs/billing').BillingData} BillingData
 * @typedef {import('@woocommerce/type-defs/shipping').ShippingAddress} ShippingAddress
 */

/**
 * @type {BillingData}
 */
const defaultBillingData = {
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
 * @type {ShippingAddress}
 */
export const defaultShippingAddress = {
	first_name: '',
	last_name: '',
	company: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
	country: '',
};

/**
 * Creates CustomerDataContext
 */
const CustomerDataContext = createContext( {
	billingData: defaultBillingData,
	shippingAddress: defaultShippingAddress,
	setBillingData: () => null,
	setShippingAddress: () => null,
} );

/**
 * @return {CustomerDataContext} Returns data and functions related to customer billing and shipping addresses.
 */
export const useCustomerDataContext = () => {
	return useContext( CustomerDataContext );
};

/**
 * Customer Data context provider.
 *
 * @param {Object}  props          Incoming props for the provider.
 * @param {Object}  props.children The children being wrapped.
 */
export const CustomerDataProvider = ( { children } ) => {
	const {
		billingData,
		shippingAddress,
		setBillingData,
		setShippingAddress,
	} = useCustomerData();

	/**
	 * @type {CustomerDataContext}
	 */
	const contextValue = {
		billingData,
		shippingAddress,
		setBillingData,
		setShippingAddress,
	};

	return (
		<CustomerDataContext.Provider value={ contextValue }>
			{ children }
		</CustomerDataContext.Provider>
	);
};
