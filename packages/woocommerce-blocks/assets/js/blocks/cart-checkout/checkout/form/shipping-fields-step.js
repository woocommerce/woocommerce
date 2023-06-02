/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import { useCheckoutContext } from '@woocommerce/base-context';
import PropTypes from 'prop-types';

const ShippingFieldsStep = ( {
	shippingAsBilling,
	setShippingAsBilling,
	children,
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
			{ children }
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
	shippingAsBilling: PropTypes.bool.isRequired,
	setShippingAsBilling: PropTypes.func.isRequired,
	children: PropTypes.node.isRequired,
};

export default ShippingFieldsStep;
