/**
 * External dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutAddress,
	useStoreEvents,
	useCustomerData,
} from '@woocommerce/base-context';
import type { ShippingAddress } from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import { ADDRESS_FORM_KEYS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import AddressWrapper from '../../address-wrapper';
import AddressCard from '../../address-card';

const CustomerAddress = () => {
	const {
		shippingAddress,
		setShippingAddress,
		setBillingAddress,
		useShippingAsBilling,
		editingShippingAddress,
		setEditingShippingAddress,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const [ shouldAnimate, setShouldAnimate ] = useState( false );

	const { isInitialized } = useCustomerData();

	const { validationErrors } = useSelect(
		( select ) => {
			return {
				validationErrors:
					select( validationStore ).getValidationErrors(),
			};
		},
		[ shippingAddress ]
	);

	useEffect( () => {
		// Check if any shipping field has validation errors
		const hasValidationErrors = Object.keys( shippingAddress ).some(
			( key ) => {
				// Check if 'shipping_' + key exists in validationErrors
				return validationErrors[ `shipping_${ key }` ] !== undefined;
			}
		);

		// Forces editing state if store has errors,
		// but not on initial render when all fields are empty.
		if (
			isInitialized &&
			hasValidationErrors &&
			editingShippingAddress === false
		) {
			setEditingShippingAddress( true );
		}
	}, [
		editingShippingAddress,
		shippingAddress,
		isInitialized,
		validationErrors,
	] );

	const onChangeAddress = useCallback(
		( values: ShippingAddress ) => {
			setShippingAddress( values );
			if ( useShippingAsBilling ) {
				setBillingAddress( values );
				dispatchCheckoutEvent( 'set-billing-address' );
			}
			dispatchCheckoutEvent( 'set-shipping-address' );
		},
		[
			dispatchCheckoutEvent,
			setBillingAddress,
			setShippingAddress,
			useShippingAsBilling,
		]
	);

	const handleEditClick = useCallback( () => {
		setShouldAnimate( true );
		setEditingShippingAddress( true );
	}, [ setEditingShippingAddress ] );

	return (
		<AddressWrapper
			isEditing={ editingShippingAddress }
			shouldAnimate={ shouldAnimate }
			addressCard={
				<AddressCard
					address={ shippingAddress }
					target="shipping"
					onEdit={ handleEditClick }
					isExpanded={ editingShippingAddress }
				/>
			}
			addressForm={
				<Form< ShippingAddress >
					id="shipping"
					addressType="shipping"
					onChange={ onChangeAddress }
					values={ shippingAddress }
					fields={ ADDRESS_FORM_KEYS }
					isEditing={ editingShippingAddress }
				/>
			}
		/>
	);
};

export default CustomerAddress;
