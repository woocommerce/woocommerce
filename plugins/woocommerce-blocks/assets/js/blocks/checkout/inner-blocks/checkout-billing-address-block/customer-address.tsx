/**
 * External dependencies
 */
import { useState, useCallback, useEffect } from '@wordpress/element';
import { AddressForm } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import type {
	BillingAddress,
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
	forceEditing = false,
}: {
	addressFieldsConfig: Record< keyof AddressFields, Partial< AddressField > >;
	showPhoneField: boolean;
	requirePhoneField: boolean;
	forceEditing?: boolean;
} ) => {
	const {
		defaultAddressFields,
		billingAddress,
		setShippingAddress,
		setBillingAddress,
		setBillingPhone,
		setShippingPhone,
		useBillingAsShipping,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const hasAddress = !! (
		billingAddress.address_1 &&
		( billingAddress.first_name || billingAddress.last_name )
	);
	const [ editing, setEditing ] = useState( ! hasAddress || forceEditing );

	// Forces editing state if store has errors.
	const { hasValidationErrors, invalidProps } = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return {
			hasValidationErrors: store.hasValidationErrors(),
			invalidProps: Object.keys( billingAddress )
				.filter( ( key ) => {
					return (
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
	}, [ editing, hasValidationErrors, invalidProps.length ] );

	const addressFieldKeys = Object.keys(
		defaultAddressFields
	) as ( keyof AddressFields )[];

	const onChangeAddress = useCallback(
		( values: Partial< BillingAddress > ) => {
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
				showPhoneField={ showPhoneField }
			/>
		),
		[ billingAddress, showPhoneField ]
	);

	const renderAddressFormComponent = useCallback(
		() => (
			<>
				<AddressForm
					id="billing"
					type="billing"
					onChange={ onChangeAddress }
					values={ billingAddress }
					fields={ addressFieldKeys }
					fieldConfig={ addressFieldsConfig }
				/>
				{ showPhoneField && (
					<PhoneNumber
						id="billing-phone"
						errorId={ 'billing_phone' }
						isRequired={ requirePhoneField }
						value={ billingAddress.phone }
						onChange={ ( value ) => {
							setBillingPhone( value );
							dispatchCheckoutEvent( 'set-phone-number', {
								step: 'billing',
							} );
							if ( useBillingAsShipping ) {
								setShippingPhone( value );
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
			billingAddress,
			dispatchCheckoutEvent,
			onChangeAddress,
			requirePhoneField,
			setBillingPhone,
			setShippingPhone,
			showPhoneField,
			useBillingAsShipping,
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
