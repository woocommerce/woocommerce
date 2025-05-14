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
		editingBillingAddress: editing,
		setEditingBillingAddress: setEditing,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();

	// Forces editing state if store has errors.
	const { hasValidationErrors, getValidationErrorSelector } = useSelect(
		( select ) => {
			const store = select( validationStore );
			return {
				hasValidationErrors: store.hasValidationErrors(),
				getValidationErrorSelector: store.getValidationError,
			};
		},
		[]
	);

	const invalidProps = useMemo( () => {
		return Object.keys( billingAddress )
			.filter( ( key ) => {
				return (
					key !== 'email' &&
					getValidationErrorSelector( 'billing_' + key ) !== undefined
				);
			} )
			.filter( Boolean );
	}, [ billingAddress, getValidationErrorSelector ] );

	useEffect( () => {
		if ( invalidProps.length > 0 && editing === false ) {
			setEditing( true );
		}
	}, [ editing, hasValidationErrors, invalidProps.length, setEditing ] );

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
