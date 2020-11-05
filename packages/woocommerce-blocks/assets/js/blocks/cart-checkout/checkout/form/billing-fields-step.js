/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	AddressForm,
	FormStep,
} from '@woocommerce/base-components/cart-checkout';
import { useCheckoutContext } from '@woocommerce/base-context';
import PropTypes from 'prop-types';

const BillingFieldsStep = ( {
	addressFieldsConfig,
	billingFields,
	defaultAddressFields,
	setBillingFields,
} ) => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();

	return (
		<FormStep
			id="billing-fields"
			disabled={ checkoutIsProcessing }
			className="wc-block-checkout__billing-fields"
			title={ __( 'Billing address', 'woocommerce' ) }
			description={ __(
				'Enter the address that matches your card or payment method.',
				'woocommerce'
			) }
		>
			<AddressForm
				id="billing"
				onChange={ setBillingFields }
				type="billing"
				values={ billingFields }
				fields={ Object.keys( defaultAddressFields ) }
				fieldConfig={ addressFieldsConfig }
			/>
		</FormStep>
	);
};

BillingFieldsStep.propTypes = {
	addressFieldsConfig: PropTypes.object.isRequired,
	billingFields: PropTypes.object.isRequired,
	defaultAddressFields: PropTypes.object.isRequired,
	setBillingFields: PropTypes.func.isRequired,
};

export default BillingFieldsStep;
