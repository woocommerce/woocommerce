/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { useEffect, useMemo } from '@wordpress/element';
import {
	useCheckoutContext,
	useShippingDataContext,
} from '@woocommerce/base-context';
import {
	useStoreEvents,
	useCheckoutAddress,
} from '@woocommerce/base-context/hooks';
import { AddressForm } from '@woocommerce/base-components/cart-checkout';
import Form from '@woocommerce/base-components/form';

/**
 * Internal dependencies
 */
import BillingFieldsStep from './billing-fields-step';
import ContactFieldsStep from './contact-fields-step';
import ShippingFieldsStep from './shipping-fields-step';
import PhoneNumber from './phone-number';
import OrderNotesStep from './order-notes-step';
import PaymentMethodStep from './payment-method-step';
import ShippingOptionsStep from './shipping-options-step';
import './style.scss';

const CheckoutForm = ( {
	requireCompanyField,
	requirePhoneField,
	showApartmentField,
	showCompanyField,
	showOrderNotes,
	showPhoneField,
	allowCreateAccount,
} ) => {
	const { onSubmit } = useCheckoutContext();
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
	const { dispatchCheckoutEvent } = useStoreEvents();

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

	// Ignore changes to dispatchCheckoutEvent callback so this is ran on first mount only.
	useEffect( () => {
		dispatchCheckoutEvent( 'render-checkout-form' );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return (
		<Form className="wc-block-checkout__form" onSubmit={ onSubmit }>
			<ContactFieldsStep
				emailValue={ billingFields.email }
				onChangeEmail={ ( value ) => {
					setEmail( value );
					dispatchCheckoutEvent( 'set-email-address' );
				} }
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
						onChange={ ( values ) => {
							setShippingFields( values );
							dispatchCheckoutEvent( 'set-shipping-address' );
						} }
						values={ shippingFields }
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
									step: 'shipping',
								} );
							} }
						/>
					) }
				</ShippingFieldsStep>
			) }
			{ showBillingFields && (
				<BillingFieldsStep>
					<AddressForm
						id="billing"
						type="billing"
						onChange={ ( values ) => {
							setBillingFields( values );
							dispatchCheckoutEvent( 'set-billing-address' );
						} }
						values={ billingFields }
						fields={ Object.keys( defaultAddressFields ) }
						fieldConfig={ addressFieldsConfig }
					/>
					{ showPhoneField && ! needsShipping && (
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
				</BillingFieldsStep>
			) }
			<ShippingOptionsStep />
			<PaymentMethodStep />
			{ showOrderNotes && <OrderNotesStep /> }
		</Form>
	);
};

CheckoutForm.propTypes = {
	requireCompanyField: PropTypes.bool.isRequired,
	requirePhoneField: PropTypes.bool.isRequired,
	showApartmentField: PropTypes.bool.isRequired,
	showCompanyField: PropTypes.bool.isRequired,
	showOrderNotes: PropTypes.bool.isRequired,
	showPhoneField: PropTypes.bool.isRequired,
	allowCreateAccount: PropTypes.bool.isRequired,
};

export default CheckoutForm;
