/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
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
		editingShippingAddress: editing,
		setEditingShippingAddress: setEditing,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();

	// Forces editing state if store has errors.
	const { hasValidationErrors, invalidProps } = useSelect(
		( select ) => {
			const store = select( validationStore );
			return {
				hasValidationErrors: store.hasValidationErrors(),
				invalidProps: Object.keys( shippingAddress )
					.filter( ( key ) => {
						return (
							store.getValidationError( 'shipping_' + key ) !==
							undefined
						);
					} )
					.filter( Boolean ),
			};
		},
		[ shippingAddress ]
	);

	useEffect( () => {
		if ( invalidProps.length > 0 && editing === false ) {
			setEditing( true );
		}
	}, [ editing, hasValidationErrors, invalidProps.length, setEditing ] );

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

	const renderAddressCardComponent = useCallback(
		() => (
			<AddressCard
				address={ shippingAddress }
				target="shipping"
				onEdit={ () => {
					setEditing( true );
				} }
				isExpanded={ editing }
			/>
		),
		[ shippingAddress, editing, setEditing ]
	);

	const renderAddressFormComponent = useCallback(
		() => (
			<Form< ShippingAddress >
				id="shipping"
				addressType="shipping"
				onChange={ onChangeAddress }
				values={ shippingAddress }
				fields={ ADDRESS_FORM_KEYS }
				isEditing={ editing }
			/>
		),
		[ onChangeAddress, shippingAddress, editing ]
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
