/**
 * External dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';
import { defaultAddressFields } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { useCustomerData } from '../../../hooks/use-customer-data';
import { useCheckoutContext } from '../checkout-state';
import { useStoreCart } from '../../../hooks/cart/use-store-cart';

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
	phone: '',
};

/**
 * Creates CustomerDataContext
 */
const CustomerDataContext = createContext( {
	billingData: defaultBillingData,
	shippingAddress: defaultShippingAddress,
	setBillingData: () => null,
	setShippingAddress: () => null,
	shippingAsBilling: true,
	setShippingAsBilling: () => null,
} );

/**
 * @return {CustomerDataContext} Returns data and functions related to customer billing and shipping addresses.
 */
export const useCustomerDataContext = () => {
	return useContext( CustomerDataContext );
};

/**
 * Compare two addresses and see if they are the same.
 *
 * @param {Object} address1 First address.
 * @param {Object} address2 Second address.
 */
const isSameAddress = ( address1, address2 ) => {
	return Object.keys( defaultAddressFields ).every(
		( field ) => address1[ field ] === address2[ field ]
	);
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
	const { cartNeedsShipping: needsShipping } = useStoreCart();
	const { customerId } = useCheckoutContext();
	const [ shippingAsBilling, setShippingAsBilling ] = useState(
		() =>
			needsShipping &&
			( ! customerId || isSameAddress( shippingAddress, billingData ) )
	);

	/**
	 * @type {CustomerDataContext}
	 */
	const contextValue = {
		billingData,
		shippingAddress,
		setBillingData,
		setShippingAddress,
		shippingAsBilling,
		setShippingAsBilling,
	};

	return (
		<CustomerDataContext.Provider value={ contextValue }>
			{ children }
		</CustomerDataContext.Provider>
	);
};
