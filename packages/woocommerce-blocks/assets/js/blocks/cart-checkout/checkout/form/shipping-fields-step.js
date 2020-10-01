/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	AddressForm,
	FormStep,
} from '@woocommerce/base-components/cart-checkout';
import { DebouncedValidatedTextInput } from '@woocommerce/base-components/text-input';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import { useCheckoutContext } from '@woocommerce/base-context';
import PropTypes from 'prop-types';

const ShippingFieldsStep = ( {
	addressFieldsConfig,
	defaultAddressFields,
	billingFields,
	setPhone,
	shippingAsBilling,
	shippingFields,
	showPhoneField,
	setShippingFields,
	setShippingAsBilling,
	requirePhoneField,
} ) => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();

	return (
		<FormStep
			id="shipping-fields"
			disabled={ checkoutIsProcessing }
			className="wc-block-checkout__shipping-fields"
			title={ __( 'Shipping address', 'woocommerce' ) }
			description={ __(
				'Enter the physical address where you want us to deliver your order.',
				'woocommerce'
			) }
		>
			<AddressForm
				id="shipping"
				onChange={ setShippingFields }
				values={ shippingFields }
				fields={ Object.keys( defaultAddressFields ) }
				fieldConfig={ addressFieldsConfig }
			/>
			{ showPhoneField && (
				<DebouncedValidatedTextInput
					id="phone"
					type="tel"
					label={
						requirePhoneField
							? __( 'Phone', 'woocommerce' )
							: __(
									'Phone (optional)',
									'woocommerce'
							  )
					}
					value={ billingFields.phone }
					autoComplete="tel"
					onChange={ setPhone }
					required={ requirePhoneField }
				/>
			) }
			<CheckboxControl
				className="wc-block-checkout__use-address-for-billing"
				label={ __(
					'Use same address for billing',
					'woocommerce'
				) }
				checked={ shippingAsBilling }
				onChange={ ( isChecked ) => setShippingAsBilling( isChecked ) }
			/>
		</FormStep>
	);
};

ShippingFieldsStep.propTypes = {
	addressFieldsConfig: PropTypes.object.isRequired,
	billingFields: PropTypes.object.isRequired,
	defaultAddressFields: PropTypes.object.isRequired,
	requirePhoneField: PropTypes.bool.isRequired,
	setPhone: PropTypes.func.isRequired,
	shippingAsBilling: PropTypes.bool.isRequired,
	setShippingAsBilling: PropTypes.func.isRequired,
	setShippingFields: PropTypes.func.isRequired,
	shippingFields: PropTypes.object.isRequired,
	showPhoneField: PropTypes.bool.isRequired,
};

export default ShippingFieldsStep;
