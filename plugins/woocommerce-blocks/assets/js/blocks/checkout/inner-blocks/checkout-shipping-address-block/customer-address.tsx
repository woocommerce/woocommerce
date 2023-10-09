/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';
import { AddressForm } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import type {
	ShippingAddress,
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
}: {
	addressFieldsConfig: Record< keyof AddressFields, Partial< AddressField > >;
	showPhoneField: boolean;
	requirePhoneField: boolean;
	hasAddress: boolean;
} ) => {
	const {
		defaultAddressFields,
		shippingAddress,
		setShippingAddress,
		setBillingAddress,
		setShippingPhone,
		useShippingAsBilling,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();

	const [ editing, setEditing ] = useState( ! hasAddress );
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
			/>
		),
		[ shippingAddress ]
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
			setShippingPhone,
			shippingAddress,
			showPhoneField,
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
