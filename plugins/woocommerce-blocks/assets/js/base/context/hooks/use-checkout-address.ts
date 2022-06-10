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
import { useDispatch, useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
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
	const { useShippingAsBilling } = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).getCheckoutState()
	);
	const { setUseShippingAsBilling } = useDispatch( CHECKOUT_STORE_KEY );
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
