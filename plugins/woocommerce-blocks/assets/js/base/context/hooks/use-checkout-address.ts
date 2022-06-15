/**
 * External dependencies
 */
import {
	defaultAddressFields,
	AddressFields,
	EnteredAddress,
	ShippingAddress,
	BillingAddress,
} from '@woocommerce/settings';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useCheckoutContext } from '../providers/cart-checkout';
import { useCustomerData } from './use-customer-data';
import { useShippingData } from './shipping/use-shipping-data';

interface CheckoutAddress {
	shippingAddress: ShippingAddress;
	billingAddress: BillingAddress;
	setShippingAddress: ( data: Partial< EnteredAddress > ) => void;
	setBillingAddress: ( data: Partial< EnteredAddress > ) => void;
	setEmail: ( value: string ) => void;
	setBillingPhone: ( value: string ) => void;
	setShippingPhone: ( value: string ) => void;
	useShippingAsBilling: boolean;
	setUseShippingAsBilling: ( useShippingAsBilling: boolean ) => void;
	defaultAddressFields: AddressFields;
	showShippingFields: boolean;
	showBillingFields: boolean;
}

/**
 * Custom hook for exposing address related functionality for the checkout address form.
 */
export const useCheckoutAddress = (): CheckoutAddress => {
	const { needsShipping } = useShippingData();
	const { useShippingAsBilling, setUseShippingAsBilling } =
		useCheckoutContext();
	const {
		billingAddress,
		setBillingAddress,
		shippingAddress,
		setShippingAddress,
	} = useCustomerData();

	const setEmail = useCallback(
		( value ) =>
			void setBillingAddress( {
				email: value,
			} ),
		[ setBillingAddress ]
	);

	const setBillingPhone = useCallback(
		( value ) =>
			void setBillingAddress( {
				phone: value,
			} ),
		[ setBillingAddress ]
	);

	const setShippingPhone = useCallback(
		( value ) =>
			void setShippingAddress( {
				phone: value,
			} ),
		[ setShippingAddress ]
	);

	return {
		shippingAddress,
		billingAddress,
		setShippingAddress,
		setBillingAddress,
		setEmail,
		setBillingPhone,
		setShippingPhone,
		defaultAddressFields,
		useShippingAsBilling,
		setUseShippingAsBilling,
		showShippingFields: needsShipping,
		showBillingFields: ! needsShipping || ! useShippingAsBilling,
	};
};
