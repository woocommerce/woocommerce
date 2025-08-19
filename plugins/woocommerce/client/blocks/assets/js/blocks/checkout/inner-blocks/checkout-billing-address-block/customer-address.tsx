/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import type { AddressFormValues } from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import { ADDRESS_FORM_KEYS } from '@woocommerce/block-settings';
import type { FieldValidationStatus } from '@woocommerce/types';

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
		editingBillingAddress: editing,
		setEditingBillingAddress: setEditing,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();

	// Forces editing state if store has errors.
	const { hasValidationErrors } = useSelect(
		( select ) => {
			const store = select( validationStore );

			// Get all validation errors for billing fields
			const errors = Object.keys( billingAddress ).reduce(
				( acc, key ) => {
					if ( key !== 'email' ) {
						const error = store.getValidationError(
							'billing_' + key
						);
						if ( error !== undefined ) {
							acc[ key ] = error;
						}
					}
					return acc;
				},
				{} as Record< string, FieldValidationStatus >
			);

			// Check for any billing-specific validation errors (including hidden ones)
			const billingValidationErrors = Object.keys( errors ).length > 0;

			return {
				hasValidationErrors: billingValidationErrors,
				validationErrors: errors,
			};
		},
		[ billingAddress ]
	);

	useEffect( () => {
		if ( hasValidationErrors && editing === false ) {
			setEditing( true );
		}
	}, [ editing, hasValidationErrors, setEditing ] );

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
			isEditing={ editing }
			addressCard={
				<AddressCard
					address={ billingAddress }
					target="billing"
					onEdit={ () => {
						setEditing( true );
					} }
					isExpanded={ editing }
				/>
			}
			addressForm={
				<Form
					id="billing"
					addressType="billing"
					onChange={ onChangeAddress }
					values={ billingAddress }
					fields={ ADDRESS_FORM_KEYS }
					isEditing={ editing }
				/>
			}
		/>
	);
};

export default CustomerAddress;
