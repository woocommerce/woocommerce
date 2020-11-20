/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { useStoreNotices, useStoreCart } from '@woocommerce/base-hooks';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useDebounce } from 'use-debounce';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import { shouldUpdateAddressStore } from './utils';

/**
 * This is a custom hook for syncing customer address data (billing and shipping) with the server.
 */
export const useCustomerData = () => {
	const { updateCustomerData } = useDispatch( storeKey );
	const { addErrorNotice, removeNotice } = useStoreNotices();
	const {
		billingAddress: cartBillingData,
		shippingAddress: cartShippingAddress,
	} = useStoreCart();

	const [ customerData, setCustomerData ] = useState( {
		billingData: cartBillingData,
		shippingAddress: cartShippingAddress,
	} );
	const currentCustomerData = useRef( customerData );
	const [ debouncedCustomerData ] = useDebounce( customerData, 400, {
		equalityFn: ( prevData, newData ) => {
			return ! (
				isShallowEqual( prevData.billingData, newData.billingData ) ||
				isShallowEqual(
					prevData.shippingAddress,
					newData.shippingAddress
				)
			);
		},
	} );

	/**
	 * Set billing data.
	 *
	 * Contains special handling for email and phone so those fields are not overwritten if simply updating address.
	 */
	const setBillingData = useCallback( ( newData ) => {
		setCustomerData( ( prevState ) => {
			return {
				...prevState,
				billingData: {
					...prevState.billingData,
					...newData,
				},
			};
		} );
	}, [] );

	const setShippingAddress = useCallback( ( newData ) => {
		setCustomerData( ( prevState ) => ( {
			...prevState,
			shippingAddress: newData,
		} ) );
	}, [] );

	useEffect( () => {
		if (
			! isShallowEqual(
				currentCustomerData.current.billingData,
				cartBillingData
			)
		) {
			currentCustomerData.current.billingData = cartBillingData;
		}
		if (
			! isShallowEqual(
				currentCustomerData.current.shippingAddress,
				cartShippingAddress
			)
		) {
			currentCustomerData.current.shippingAddress = cartShippingAddress;
		}
	}, [ cartBillingData, cartShippingAddress ] );

	useEffect( () => {
		if (
			! (
				shouldUpdateAddressStore(
					currentCustomerData.current.billingData,
					debouncedCustomerData.billingData
				) ||
				shouldUpdateAddressStore(
					currentCustomerData.current.shippingAddress,
					debouncedCustomerData.shippingAddress
				)
			)
		) {
			return;
		}
		removeNotice( 'address' );
		updateCustomerData( {
			billing_address: debouncedCustomerData.billingData,
			shipping_address: debouncedCustomerData.shippingAddress,
		} ).catch( ( error ) => {
			addErrorNotice( error.message, {
				id: 'address',
			} );
		} );
	}, [
		debouncedCustomerData,
		addErrorNotice,
		removeNotice,
		updateCustomerData,
	] );

	return {
		billingData: customerData.billingData,
		shippingAddress: customerData.shippingAddress,
		setBillingData,
		setShippingAddress,
	};
};
