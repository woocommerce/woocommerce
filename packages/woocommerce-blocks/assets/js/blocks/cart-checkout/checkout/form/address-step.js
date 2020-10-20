/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import PropTypes from 'prop-types';
import { useCheckoutAddress } from '@woocommerce/base-hooks';
import { useShippingDataContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import BillingFieldsStep from './billing-fields-step';
import ContactFieldsStep from './contact-fields-step';
import ShippingFieldsStep from './shipping-fields-step';
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
				...defaultAddressFields.company,
				hidden: ! showCompanyField,
				required: requireCompanyField,
			},
			address_2: {
				...defaultAddressFields.address_2,
				hidden: ! showApartmentField,
			},
		};
	}, [
		defaultAddressFields,
		showCompanyField,
		requireCompanyField,
		showApartmentField,
	] );

	return (
		<>
			<ContactFieldsStep
				emailValue={ billingFields.email }
				onChangeEmail={ setEmail }
				allowCreateAccount={ allowCreateAccount }
			/>
			{ needsShipping && (
				<ShippingFieldsStep
					addressFieldsConfig={ addressFieldsConfig }
					billingFields={ billingFields }
					defaultAddressFields={ defaultAddressFields }
					requirePhoneField={ requirePhoneField }
					setPhone={ setPhone }
					setShippingAsBilling={ setShippingAsBilling }
					setShippingFields={ setShippingFields }
					shippingAsBilling={ shippingAsBilling }
					shippingFields={ shippingFields }
					showPhoneField={ showPhoneField }
				/>
			) }
			{ showBillingFields && (
				<BillingFieldsStep
					addressFieldsConfig={ addressFieldsConfig }
					billingFields={ billingFields }
					defaultAddressFields={ defaultAddressFields }
					setBillingFields={ setBillingFields }
				/>
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
};

export default AddressStep;
