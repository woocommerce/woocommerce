/**
 * External dependencies
 */
import { useMemo, useEffect, Fragment } from '@wordpress/element';
import {
	useCheckoutAddress,
	useStoreEvents,
	useEditorContext,
} from '@woocommerce/base-context';
import { AddressForm } from '@woocommerce/base-components/cart-checkout';
import Noninteractive from '@woocommerce/base-components/noninteractive';

/**
 * Internal dependencies
 */
import PhoneNumber from '../../phone-number';

const Block = ( {
	showCompanyField = false,
	showApartmentField = false,
	showPhoneField = false,
	requireCompanyField = false,
	requirePhoneField = false,
}: {
	showCompanyField: boolean;
	showApartmentField: boolean;
	showPhoneField: boolean;
	requireCompanyField: boolean;
	requirePhoneField: boolean;
} ): JSX.Element => {
	const {
		defaultAddressFields,
		billingFields,
		setBillingFields,
		setPhone,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const { isEditor } = useEditorContext();

	// Clears data if fields are hidden.
	useEffect( () => {
		if ( ! showPhoneField ) {
			setPhone( '' );
		}
	}, [ showPhoneField, setPhone ] );

	const addressFieldsConfig = useMemo( () => {
		return {
			company: {
				hidden: ! showCompanyField,
				required: requireCompanyField,
			},
			address_2: {
				hidden: ! showApartmentField,
			},
		};
	}, [ showCompanyField, requireCompanyField, showApartmentField ] );

	const AddressFormWrapperComponent = isEditor ? Noninteractive : Fragment;

	return (
		<AddressFormWrapperComponent>
			<AddressForm
				id="billing"
				type="billing"
				onChange={ ( values: Record< string, unknown > ) => {
					setBillingFields( values );
					dispatchCheckoutEvent( 'set-billing-address' );
				} }
				values={ billingFields }
				fields={ Object.keys( defaultAddressFields ) }
				fieldConfig={ addressFieldsConfig }
			/>
			{ showPhoneField && (
				<PhoneNumber
					isRequired={ requirePhoneField }
					value={ billingFields.phone }
					onChange={ ( value ) => {
						setPhone( value );
						dispatchCheckoutEvent( 'set-phone-number', {
							step: 'billing',
						} );
					} }
				/>
			) }
		</AddressFormWrapperComponent>
	);
};

export default Block;
