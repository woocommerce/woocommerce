/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutAddress,
	useStoreEvents,
	useCustomerData,
} from '@woocommerce/base-context';
import type { AddressFormValues } from '@woocommerce/settings';
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
		billingAddress,
		setShippingAddress,
		setBillingAddress,
		useBillingAsShipping,
		editingBillingAddress,
		setEditingBillingAddress,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const { isInitialized } = useCustomerData();

	const { validationErrors } = useSelect(
		( select ) => {
			return {
				validationErrors:
					select( validationStore ).getValidationErrors(),
			};
		},
		[ billingAddress ]
	);

	useEffect( () => {
		// Check if any billing field has validation errors
		const hasValidationErrors = Object.keys( billingAddress ).some(
			( key ) => {
				// Check if 'billing_' + key exists in validationErrors
				return validationErrors[ `billing_${ key }` ] !== undefined;
			}
		);

		// Forces editing state if store has errors,
		// but not on initial render when all fields are empty.
		if (
			isInitialized &&
			hasValidationErrors &&
			editingBillingAddress === false
		) {
			setEditingBillingAddress( true );
		}
	}, [
		editingBillingAddress,
		billingAddress,
		isInitialized,
		validationErrors,
	] );

	const onChangeAddress = useCallback(
		( values: AddressFormValues ) => {
			setBillingAddress( values );
			if ( useBillingAsShipping ) {
				setShippingAddress( values );
				dispatchCheckoutEvent( 'set-shipping-address' );
			}
			dispatchCheckoutEvent( 'set-billing-address' );
		},
		[
			dispatchCheckoutEvent,
			setBillingAddress,
			setShippingAddress,
			useBillingAsShipping,
		]
	);

	return (
		<AddressWrapper
			isEditing={ editingBillingAddress }
			addressCard={
				<AddressCard
					address={ billingAddress }
					target="billing"
					onEdit={ () => {
						setEditingBillingAddress( true );
					} }
					isExpanded={ editingBillingAddress }
				/>
			}
			addressForm={
				<Form
					id="billing"
					addressType="billing"
					onChange={ onChangeAddress }
					values={ billingAddress }
					fields={ ADDRESS_FORM_KEYS }
					isEditing={ editingBillingAddress }
				/>
			}
		/>
	);
};

export default CustomerAddress;
