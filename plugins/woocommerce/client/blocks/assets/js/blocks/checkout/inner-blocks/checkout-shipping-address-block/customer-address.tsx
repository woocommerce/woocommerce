/**
 * External dependencies
 */
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import type { ShippingAddress } from '@woocommerce/settings';
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
		shippingAddress,
		setShippingAddress,
		setBillingAddress,
		useShippingAsBilling,
		editingShippingAddress: editing,
		setEditingShippingAddress: setEditing,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const [ shouldAnimate, setShouldAnimate ] = useState( false );

	// Forces editing state if store has errors.
	const { hasValidationErrors, validationErrors } = useSelect(
		( select ) => {
			const store = select( validationStore );
			
			// Get all validation errors for shipping fields
			const errors = Object.keys( shippingAddress ).reduce( ( acc, key ) => {
				const error = store.getValidationError( 'shipping_' + key );
				if ( error !== undefined ) {
					acc[ key ] = error;
				}
				return acc;
			}, {} as Record< string, FieldValidationStatus > );
			
			// Check for any shipping-specific validation errors (including hidden ones)
			const shippingValidationErrors = Object.keys( errors ).length > 0;

			return {
				hasValidationErrors: shippingValidationErrors,
				validationErrors: errors,
			};
		},
		[ shippingAddress ]
	);

	const invalidProps = useMemo( () => {
		return Object.keys( validationErrors )
			.filter( ( key ) => {
				const error = validationErrors[ key ];
				return ! error?.hidden;
			} )
			.filter( Boolean );
	}, [ validationErrors ] );

	useEffect( () => {
		if ( hasValidationErrors && editing === false ) {
			setEditing( true );
		}
	}, [ editing, hasValidationErrors, setEditing ] );

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
		setEditing( true );
	}, [ setEditing ] );

	return (
		<AddressWrapper
			isEditing={ editing }
			shouldAnimate={ shouldAnimate }
			addressCard={
				<AddressCard
					address={ shippingAddress }
					target="shipping"
					onEdit={ handleEditClick }
					isExpanded={ true }
				/>
			}
			addressForm={
				<Form< ShippingAddress >
					id="shipping"
					addressType="shipping"
					onChange={ onChangeAddress }
					values={ shippingAddress }
					fields={ ADDRESS_FORM_KEYS }
					isEditing={ editing }
				/>
			}
		/>
	);
};

export default CustomerAddress;
