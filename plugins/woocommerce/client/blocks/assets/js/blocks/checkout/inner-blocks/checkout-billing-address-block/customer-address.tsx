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
	const { hasValidationErrors, invalidProps } = useSelect( ( select ) => {
		const store = select( validationStore );
		return {
			hasValidationErrors: store.hasValidationErrors(),
			invalidProps: Object.keys( billingAddress )
				.filter( ( key ) => {
					return (
						key !== 'email' &&
						store.getValidationError( 'billing_' + key ) !==
							undefined
					);
				} )
				.filter( Boolean ),
		};
	} );

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

	const renderAddressCardComponent = useCallback(
		() => (
			<AddressCard
				address={ billingAddress }
				target="billing"
				onEdit={ () => {
					setEditing( true );
				} }
				isExpanded={ editing }
			/>
		),
		[ billingAddress, editing, setEditing ]
	);

	const renderAddressFormComponent = useCallback(
		() => (
			<>
				<Form
					id="billing"
					addressType="billing"
					onChange={ onChangeAddress }
					values={ billingAddress }
					fields={ ADDRESS_FORM_KEYS }
					isEditing={ editing }
				/>
			</>
		),
		[ billingAddress, onChangeAddress, editing ]
	);

	return (
		<AddressWrapper
			isEditing={ editing }
			addressCard={ renderAddressCardComponent }
			addressForm={ renderAddressFormComponent }
		/>
	);
};

export default CustomerAddress;
