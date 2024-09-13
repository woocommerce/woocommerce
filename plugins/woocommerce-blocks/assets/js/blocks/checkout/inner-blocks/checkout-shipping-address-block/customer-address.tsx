/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import type {
	FormFieldsConfig,
	AddressFormValues,
} from '@woocommerce/settings';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	CHECKOUT_STORE_KEY,
	VALIDATION_STORE_KEY,
} from '@woocommerce/block-data';
import { ADDRESS_FORM_KEYS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import AddressWrapper from '../../address-wrapper';
import AddressCard from '../../address-card';

const CustomerAddress = ( {
	addressFieldsConfig,
}: {
	addressFieldsConfig: FormFieldsConfig;
} ) => {
	const {
		shippingAddress,
		setShippingAddress,
		setBillingAddress,
		useShippingAsBilling,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const editing = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return store.getEditingShippingAddress();
	} );

	const setEditing =
		useDispatch( CHECKOUT_STORE_KEY ).setEditingShippingAddress;

	// Forces editing state if store has errors.
	const { hasValidationErrors, invalidProps } = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
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
	} );

	useEffect( () => {
		if ( invalidProps.length > 0 && editing === false ) {
			setEditing( true );
		}
	}, [ editing, hasValidationErrors, invalidProps.length, setEditing ] );

	const onChangeAddress = useCallback(
		( values: AddressFormValues ) => {
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
				fieldConfig={ addressFieldsConfig }
				isExpanded={ editing }
			/>
		),
		[ shippingAddress, addressFieldsConfig, editing, setEditing ]
	);

	const renderAddressFormComponent = useCallback(
		() => (
			<Form< AddressFormValues >
				id="shipping"
				addressType="shipping"
				onChange={ onChangeAddress }
				values={ shippingAddress }
				fields={ ADDRESS_FORM_KEYS }
				fieldConfig={ addressFieldsConfig }
				isEditing={ editing }
			/>
		),
		[ addressFieldsConfig, onChangeAddress, shippingAddress, editing ]
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
