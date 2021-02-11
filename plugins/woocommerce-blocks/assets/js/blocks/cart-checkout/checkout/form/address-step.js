/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import PropTypes from 'prop-types';
import { useCheckoutAddress } from '@woocommerce/base-hooks';
import { useShippingDataContext } from '@woocommerce/base-context';
import { AddressForm } from '@woocommerce/base-components/cart-checkout';

/**
 * Internal dependencies
 */
import BillingFieldsStep from './billing-fields-step';
import ContactFieldsStep from './contact-fields-step';
import ShippingFieldsStep from './shipping-fields-step';
import PhoneNumber from './phone-number';
import './style.scss';

const AddressStep = ( {
	requireCompanyField,
	requirePhoneField,
	showApartmentField,
	showCompanyField,
	showPhoneField,
	allowCreateAccount,
} ) => {
	const {
		defaultAddressFields,
		billingFields,
		setBillingFields,
		setEmail,
		setPhone,
		setShippingAsBilling,
		setShippingFields,
		shippingAsBilling,
		shippingFields,
		showBillingFields,
	} = useCheckoutAddress();
	const { needsShipping } = useShippingDataContext();

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

	return (
		<>
			<ContactFieldsStep
				emailValue={ billingFields.email }
				onChangeEmail={ setEmail }
				allowCreateAccount={ allowCreateAccount }
			/>
			{ needsShipping && (
				<ShippingFieldsStep
					shippingAsBilling={ shippingAsBilling }
					setShippingAsBilling={ setShippingAsBilling }
				>
					<AddressForm
						id="shipping"
						type="shipping"
						onChange={ setShippingFields }
						values={ shippingFields }
						fields={ Object.keys( defaultAddressFields ) }
						fieldConfig={ addressFieldsConfig }
					/>
					{ showPhoneField && (
						<PhoneNumber
							isRequired={ requirePhoneField }
							value={ billingFields.phone }
							onChange={ setPhone }
						/>
					) }
				</ShippingFieldsStep>
			) }
			{ showBillingFields && (
				<BillingFieldsStep>
					<AddressForm
						id="billing"
						type="billing"
						onChange={ setBillingFields }
						values={ billingFields }
						fields={ Object.keys( defaultAddressFields ) }
						fieldConfig={ addressFieldsConfig }
					/>
					{ showPhoneField && ! needsShipping && (
						<PhoneNumber
							isRequired={ requirePhoneField }
							value={ billingFields.phone }
							onChange={ setPhone }
						/>
					) }
				</BillingFieldsStep>
			) }
		</>
	);
};

AddressStep.propTypes = {
	requireCompanyField: PropTypes.bool.isRequired,
	requirePhoneField: PropTypes.bool.isRequired,
	showApartmentField: PropTypes.bool.isRequired,
	showCompanyField: PropTypes.bool.isRequired,
	showPhoneField: PropTypes.bool.isRequired,
	allowCreateAccount: PropTypes.bool.isRequired,
};

export default AddressStep;
