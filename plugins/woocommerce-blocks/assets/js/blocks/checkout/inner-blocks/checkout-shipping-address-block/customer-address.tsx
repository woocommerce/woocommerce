/**
 * External dependencies
 */
import { useState, useCallback, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import type {
	FormFieldsConfig,
	AddressFormValues,
} from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { ADDRESS_FORM_KEYS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import AddressWrapper from '../../address-wrapper';
import AddressCard from '../../address-card';

const CustomerAddress = ( {
	addressFieldsConfig,
	defaultEditing = false,
}: {
	addressFieldsConfig: FormFieldsConfig;
	defaultEditing?: boolean;
} ) => {
	const {
		shippingAddress,
		setShippingAddress,
		setBillingAddress,
		useShippingAsBilling,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const [ editing, setEditing ] = useState( defaultEditing );

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
		[ shippingAddress, addressFieldsConfig, editing ]
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
