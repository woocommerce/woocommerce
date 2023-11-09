/**
 * External dependencies
 */
import { useState, useCallback, useEffect } from '@wordpress/element';
import { AddressForm } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutAddress,
	useStoreEvents,
	useEditorContext,
} from '@woocommerce/base-context';
import type {
	ShippingAddress,
	AddressField,
	AddressFields,
} from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import AddressWrapper from '../../address-wrapper';
import PhoneNumber from '../../phone-number';
import AddressCard from '../../address-card';

const CustomerAddress = ( {
	addressFieldsConfig,
	showPhoneField,
	requirePhoneField,
}: {
	addressFieldsConfig: Record< keyof AddressFields, Partial< AddressField > >;
	showPhoneField: boolean;
	requirePhoneField: boolean;
} ) => {
	const {
		defaultAddressFields,
		shippingAddress,
		setShippingAddress,
		setBillingAddress,
		setShippingPhone,
		setBillingPhone,
		useShippingAsBilling,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const { isEditor } = useEditorContext();
	const hasAddress = !! (
		shippingAddress.address_1 &&
		( shippingAddress.first_name || shippingAddress.last_name )
	);
	const [ editing, setEditing ] = useState( ! hasAddress || isEditor );

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
	}, [ editing, hasValidationErrors, invalidProps.length ] );

	const addressFieldKeys = Object.keys(
		defaultAddressFields
	) as ( keyof AddressFields )[];

	const onChangeAddress = useCallback(
		( values: Partial< ShippingAddress > ) => {
			setShippingAddress( values );
			if ( useShippingAsBilling ) {
				// Sync billing with shipping. Ensure unwanted properties are omitted.
				const { ...syncBilling } = values;

				if ( ! showPhoneField ) {
					delete syncBilling.phone;
				}

				setBillingAddress( syncBilling );
				dispatchCheckoutEvent( 'set-billing-address' );
			}
			dispatchCheckoutEvent( 'set-shipping-address' );
		},
		[
			dispatchCheckoutEvent,
			setBillingAddress,
			setShippingAddress,
			useShippingAsBilling,
			showPhoneField,
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
				showPhoneField={ showPhoneField }
			/>
		),
		[ shippingAddress, showPhoneField ]
	);

	const renderAddressFormComponent = useCallback(
		() => (
			<>
				<AddressForm
					id="shipping"
					type="shipping"
					onChange={ onChangeAddress }
					values={ shippingAddress }
					fields={ addressFieldKeys }
					fieldConfig={ addressFieldsConfig }
				/>
				{ showPhoneField && (
					<PhoneNumber
						id="shipping-phone"
						errorId={ 'shipping_phone' }
						isRequired={ requirePhoneField }
						value={ shippingAddress.phone }
						onChange={ ( value ) => {
							setShippingPhone( value );
							dispatchCheckoutEvent( 'set-phone-number', {
								step: 'shipping',
							} );
							if ( useShippingAsBilling ) {
								setBillingPhone( value );
								dispatchCheckoutEvent( 'set-phone-number', {
									step: 'billing',
								} );
							}
						} }
					/>
				) }
			</>
		),
		[
			addressFieldKeys,
			addressFieldsConfig,
			dispatchCheckoutEvent,
			onChangeAddress,
			requirePhoneField,
			setBillingPhone,
			setShippingPhone,
			shippingAddress,
			showPhoneField,
			useShippingAsBilling,
		]
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
