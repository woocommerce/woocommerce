/**
 * External dependencies
 */
import { defaultAddressFields } from '@woocommerce/settings';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	useShippingDataContext,
	useCustomerDataContext,
	useCheckoutContext,
} from '../providers/cart-checkout';

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
 * Custom hook for exposing address related functionality for the checkout address form.
 */
export const useCheckoutAddress = () => {
	const { customerId } = useCheckoutContext();
	const { needsShipping } = useShippingDataContext();
	const {
		billingData,
		setBillingData,
		shippingAddress,
		setShippingAddress,
	} = useCustomerDataContext();

	// This tracks the state of the "shipping as billing" address checkbox. It's
	// initial value is true (if shipping is needed), however, if the user is
	// logged in and they have a different billing address, we can toggle this off.
	const [ shippingAsBilling, setShippingAsBilling ] = useState(
		() =>
			needsShipping &&
			( ! customerId || isSameAddress( shippingAddress, billingData ) )
	);

	const currentShippingAsBilling = useRef( shippingAsBilling );
	const previousBillingData = useRef( billingData );

	/**
	 * Sets shipping address data, and also billing if using the same address.
	 */
	const setShippingFields = useCallback(
		( value ) => {
			setShippingAddress( value );

			if ( shippingAsBilling ) {
				setBillingData( value );
			}
		},
		[ shippingAsBilling, setShippingAddress, setBillingData ]
	);

	/**
	 * Sets billing address data, and also shipping if shipping is disabled.
	 */
	const setBillingFields = useCallback(
		( value ) => {
			setBillingData( value );

			if ( ! needsShipping ) {
				setShippingAddress( value );
			}
		},
		[ needsShipping, setShippingAddress, setBillingData ]
	);

	// When the "Use same address" checkbox is toggled we need to update the current billing address to reflect this;
	// that is either setting the billing address to the shipping address, or restoring the billing address to it's
	// previous state.
	useEffect( () => {
		if ( currentShippingAsBilling.current !== shippingAsBilling ) {
			if ( shippingAsBilling ) {
				previousBillingData.current = billingData;
				setBillingData( shippingAddress );
			} else {
				setBillingData( {
					...previousBillingData.current,
					email: undefined,
					phone: undefined,
				} );
			}
			currentShippingAsBilling.current = shippingAsBilling;
		}
	}, [ shippingAsBilling, setBillingData, shippingAddress, billingData ] );

	const setEmail = ( value ) =>
		void setBillingData( {
			email: value,
		} );
	const setPhone = ( value ) =>
		void setBillingData( {
			phone: value,
		} );

	// Note that currentShippingAsBilling is returned rather than the current state of shippingAsBilling--this is so that
	// the billing fields are not rendered before sync (billing field values are debounced and would be outdated)
	return {
		defaultAddressFields,
		shippingFields: shippingAddress,
		setShippingFields,
		billingFields: billingData,
		setBillingFields,
		setEmail,
		setPhone,
		shippingAsBilling,
		setShippingAsBilling,
		showBillingFields:
			! needsShipping || ! currentShippingAsBilling.current,
	};
};
