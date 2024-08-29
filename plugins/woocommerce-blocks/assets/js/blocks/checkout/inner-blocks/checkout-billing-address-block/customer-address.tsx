/**
 * External dependencies
 */
import { useState, useCallback, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutAddress, useStoreEvents } from '@woocommerce/base-context';
import type {
	AddressFormValues,
	FormFieldsConfig,
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
		billingAddress,
		setShippingAddress,
		setBillingAddress,
		useBillingAsShipping,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const [ editing, setEditing ] = useState( defaultEditing );

	// Forces editing state if store has errors.
	const { hasValidationErrors, invalidProps } = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
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
	}, [ editing, hasValidationErrors, invalidProps.length ] );

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
				fieldConfig={ addressFieldsConfig }
				isExpanded={ editing }
			/>
		),
		[ billingAddress, addressFieldsConfig, editing ]
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
					fieldConfig={ addressFieldsConfig }
					isEditing={ editing }
				/>
			</>
		),
		[ addressFieldsConfig, billingAddress, onChangeAddress, editing ]
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
