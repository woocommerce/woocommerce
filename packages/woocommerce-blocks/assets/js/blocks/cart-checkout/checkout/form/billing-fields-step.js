/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutContext } from '@woocommerce/base-context';
import PropTypes from 'prop-types';

const BillingFieldsStep = ( { children } ) => {
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
			{ children }
		</FormStep>
	);
};

BillingFieldsStep.propTypes = {
	children: PropTypes.node.isRequired,
};

export default BillingFieldsStep;
