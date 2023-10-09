/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';
import { AddressForm } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import type {
	BillingAddress,
	AddressField,
	AddressFields,
} from '@woocommerce/settings';

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
	hasAddress,
	forceEditing = false,
}: {
	addressFieldsConfig: Record< keyof AddressFields, Partial< AddressField > >;
	showPhoneField: boolean;
	requirePhoneField: boolean;
	hasAddress: boolean;
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

	const [ editing, setEditing ] = useState( ! hasAddress || forceEditing );
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
			/>
		),
		[ billingAddress ]
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
