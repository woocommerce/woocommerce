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
	billingData: BillingAddress;
	setShippingAddress: ( data: Partial< EnteredAddress > ) => void;
	setBillingData: ( data: Partial< EnteredAddress > ) => void;
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
	const {
		useShippingAsBilling,
		setUseShippingAsBilling,
	} = useCheckoutContext();
	const {
		billingData,
		setBillingData,
		shippingAddress,
		setShippingAddress,
	} = useCustomerData();

	const setEmail = useCallback(
		( value ) =>
			void setBillingData( {
				email: value,
			} ),
		[ setBillingData ]
	);

	const setBillingPhone = useCallback(
		( value ) =>
			void setBillingData( {
				phone: value,
			} ),
		[ setBillingData ]
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
		billingData,
		setShippingAddress,
		setBillingData,
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
