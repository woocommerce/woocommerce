/**
 * External dependencies
 */
import defaultAddressFields from '@woocommerce/base-components/cart-checkout/address-form/default-address-fields';
import { useState, useEffect, useCallback } from '@wordpress/element';
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
		// Uses shipping address or billing fields depending on shippingAsBilling checkbox, but ensures
		// billing only fields are also included.
		const newBillingData = {
			...( shippingAsBilling ? shippingAddress : billingFields ),
			email: billingFields.email || billingData.email,
			phone: billingFields.phone || billingData.phone,
		};

		if ( ! isEqual( newBillingData, billingData ) ) {
			setBillingData( newBillingData );
		}
	}, [
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
	const setBillingFields = useCallback(
		( newValues ) =>
			void updateBillingFields( ( prevState ) => ( {
				...prevState,
				...newValues,
			} ) ),
		[]
	);

	const setEmail = ( value ) => void setBillingFields( { email: value } );
	const setPhone = ( value ) => void setBillingFields( { phone: value } );

	return {
		defaultAddressFields,
		shippingFields: shippingAddress,
		setShippingFields: setShippingAddress,
		billingFields,
		setBillingFields,
		setEmail,
		setPhone,
		shippingAsBilling,
		setShippingAsBilling,
		showBillingFields: ! needsShipping || ! shippingAsBilling,
	};
};
