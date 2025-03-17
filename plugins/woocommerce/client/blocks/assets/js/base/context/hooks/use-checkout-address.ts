/**
 * External dependencies
 */
import {
	defaultFields,
	FormFields,
	ShippingAddress,
	BillingAddress,
	getSetting,
} from '@woocommerce/settings';
import { useCallback } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useCustomerData } from './use-customer-data';
import { useShippingData } from './shipping/use-shipping-data';
import { useEditorContext } from '../providers/editor-context';

interface CheckoutAddress {
	shippingAddress: ShippingAddress;
	billingAddress: BillingAddress;
	setShippingAddress: ( data: Partial< ShippingAddress > ) => void;
	setBillingAddress: ( data: Partial< BillingAddress > ) => void;
	setEmail: ( value: string ) => void;
	useShippingAsBilling: boolean;
	editingBillingAddress: boolean;
	editingShippingAddress: boolean;
	setUseShippingAsBilling: ( useShippingAsBilling: boolean ) => void;
	setEditingBillingAddress: ( isEditing: boolean ) => void;
	setEditingShippingAddress: ( isEditing: boolean ) => void;
	defaultFields: FormFields;
	showShippingFields: boolean;
	showBillingFields: boolean;
	forcedBillingAddress: boolean;
	useBillingAsShipping: boolean;
	needsShipping: boolean;
	showShippingMethods: boolean;
}

/**
 * Custom hook for exposing address related functionality for the checkout address form.
 */
export const useCheckoutAddress = (): CheckoutAddress => {
	const { isEditor, getPreviewData } = useEditorContext();
	const { needsShipping } = useShippingData();
	const {
		useShippingAsBilling,
		prefersCollection,
		editingBillingAddress,
		editingShippingAddress,
	} = useSelect( ( select ) => ( {
		useShippingAsBilling: select( checkoutStore ).getUseShippingAsBilling(),
		prefersCollection: select( checkoutStore ).prefersCollection(),
		editingBillingAddress:
			select( checkoutStore ).getEditingBillingAddress(),
		editingShippingAddress:
			select( checkoutStore ).getEditingShippingAddress(),
	} ) );
	const {
		__internalSetUseShippingAsBilling,
		setEditingBillingAddress,
		setEditingShippingAddress,
	} = useDispatch( checkoutStore );
	const {
		billingAddress,
		setBillingAddress,
		shippingAddress,
		setShippingAddress,
	} = useCustomerData();

	const setEmail = useCallback(
		( value: string ) =>
			void setBillingAddress( {
				email: value,
			} ),
		[ setBillingAddress ]
	);

	const forcedBillingAddress: boolean = getSetting(
		'forcedBillingAddress',
		false
	);
	return {
		shippingAddress,
		billingAddress,
		setShippingAddress,
		setBillingAddress,
		setEmail,
		defaultFields: isEditor
			? ( getPreviewData( 'defaultFields', defaultFields ) as FormFields )
			: defaultFields,
		useShippingAsBilling,
		setUseShippingAsBilling: __internalSetUseShippingAsBilling,
		editingBillingAddress,
		editingShippingAddress,
		setEditingBillingAddress,
		setEditingShippingAddress,
		needsShipping,
		showShippingFields:
			! forcedBillingAddress && needsShipping && ! prefersCollection,
		showShippingMethods: needsShipping && ! prefersCollection,
		showBillingFields:
			! needsShipping || ! useShippingAsBilling || !! prefersCollection,
		forcedBillingAddress,
		useBillingAsShipping: forcedBillingAddress || !! prefersCollection,
	};
};
