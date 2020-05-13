/**
 * External dependencies
 */
import defaultAddressFields from '@woocommerce/base-components/cart-checkout/address-form/default-address-fields';
import { useState, useEffect } from '@wordpress/element';
import {
	useShippingDataContext,
	useBillingDataContext,
	useCheckoutContext,
} from '@woocommerce/base-context';
import { isEqual } from 'lodash';

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
 * Custom hook for tracking address field state on checkout and persisting it to
 * context globally on change.
 */
export const useCheckoutAddress = () => {
	const { customerId } = useCheckoutContext();
	const {
		shippingAddress,
		setShippingAddress,
		needsShipping,
	} = useShippingDataContext();
	const { billingData, setBillingData } = useBillingDataContext();

	// These are the local states of address fields, which are persisted
	// globally when changed. They default to the global shipping address which
	// is populated from the current customer data or default location.
	const [ shippingFields, updateShippingFields ] = useState(
		shippingAddress
	);
	const [ billingFields, updateBillingFields ] = useState( billingData );

	// This tracks the state of the "shipping as billing" address checkbox. It's
	// initial value is true (if shipping is needed), however, if the user is
	// logged in and they have a different billing address, we can toggle this off.
	const [ shippingAsBilling, setShippingAsBilling ] = useState(
		() =>
			needsShipping &&
			( ! customerId || isSameAddress( shippingAddress, billingData ) )
	);

	// Pushes to global state when changes are made locally.
	useEffect( () => {
		if ( ! isEqual( shippingFields, shippingAddress ) ) {
			setShippingAddress( shippingFields );
		}

		// Uses shipping or billing fields depending on shippingAsBilling checkbox, but ensures
		// billing only fields are also included.
		const newBillingData = {
			...( shippingAsBilling ? shippingFields : billingFields ),
			email: billingFields.email,
			phone: billingFields.phone,
		};

		if ( ! isEqual( newBillingData, billingData ) ) {
			setBillingData( newBillingData );
		}
	}, [
		shippingFields,
		billingFields,
		shippingAsBilling,
		billingData,
		shippingAddress,
		setBillingData,
		setShippingAddress,
	] );

	/**
	 * Wrapper for updateBillingFields (from useState) which handles merging.
	 *
	 * @param {Object} newValues New values to store to state.
	 */
	const setBillingFields = ( newValues ) =>
		void updateBillingFields( ( prevState ) => ( {
			...prevState,
			...newValues,
		} ) );

	/**
	 * Wrapper for updateShippingFields (from useState) which handles merging.
	 *
	 * @param {Object} newValues New values to store to state.
	 */
	const setShippingFields = ( newValues ) =>
		void updateShippingFields( ( prevState ) => ( {
			...prevState,
			...newValues,
		} ) );

	const setEmail = ( value ) => void setBillingFields( { email: value } );
	const setPhone = ( value ) => void setBillingFields( { phone: value } );

	return {
		defaultAddressFields,
		shippingFields,
		setShippingFields,
		billingFields,
		setBillingFields,
		setEmail,
		setPhone,
		shippingAsBilling,
		setShippingAsBilling,
		showBillingFields: ! needsShipping || ! shippingAsBilling,
	};
};
