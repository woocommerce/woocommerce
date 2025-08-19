/**
 * External dependencies
 */
import { useCallback, useEffect, useMemo } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
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

	const areAllFieldsEmpty = useMemo( () => {
		const billingFieldKeys = Object.keys( billingAddress ).filter(
			( key ) => key !== 'email'
		);
		return billingFieldKeys.every( ( key ) => {
			const value = billingAddress[ key as keyof typeof billingAddress ];
			return ! value || value === '';
		} );
	}, [ billingAddress ] );

	const hasValidationErrors = useSelect(
		( select ) => {
			const store = select( validationStore );
			const billingFieldKeys = Object.keys( billingAddress ).filter(
				( key ) => key !== 'email'
			);
			// Check if any billing field has validation errors
			return billingFieldKeys.some( ( key ) => {
				const error = store.getValidationError( 'billing_' + key );
				return error !== undefined;
			} );
		},
		[ billingAddress ]
	);

	useEffect( () => {
		// Forces editing state if store has errors,
		// but not on initial render when all fields are empty.
		if (
			hasValidationErrors &&
			editingBillingAddress === false &&
			! areAllFieldsEmpty
		) {
			setEditingBillingAddress( true );
		}
	}, [ editingBillingAddress, hasValidationErrors, billingAddress ] );

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
