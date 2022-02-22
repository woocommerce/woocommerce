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
import type {
	BillingAddress,
	AddressField,
	AddressFields,
} from '@woocommerce/settings';

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
		billingData,
		setBillingData,
		setBillingPhone,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const { isEditor } = useEditorContext();

	// Clears data if fields are hidden.
	useEffect( () => {
		if ( ! showPhoneField ) {
			setBillingPhone( '' );
		}
	}, [ showPhoneField, setBillingPhone ] );

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
	}, [
		showCompanyField,
		requireCompanyField,
		showApartmentField,
	] ) as Record< keyof AddressFields, Partial< AddressField > >;

	const AddressFormWrapperComponent = isEditor ? Noninteractive : Fragment;

	return (
		<AddressFormWrapperComponent>
			<AddressForm
				id="billing"
				type="billing"
				onChange={ ( values: Partial< BillingAddress > ) => {
					setBillingData( values );
					dispatchCheckoutEvent( 'set-billing-address' );
				} }
				values={ billingData }
				fields={
					Object.keys(
						defaultAddressFields
					) as ( keyof AddressFields )[]
				}
				fieldConfig={ addressFieldsConfig }
			/>
			{ showPhoneField && (
				<PhoneNumber
					isRequired={ requirePhoneField }
					value={ billingData.phone }
					onChange={ ( value ) => {
						setBillingPhone( value );
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
