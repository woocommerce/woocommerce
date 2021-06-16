/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import PropTypes from 'prop-types';

const ShippingFieldsStep = ( {
	shippingAsBilling,
	setShippingAsBilling,
	children,
} ) => {
	const { isDisabled } = useCheckoutSubmit();

	return (
		<FormStep
			id="shipping-fields"
			disabled={ isDisabled }
			className="wc-block-checkout__shipping-fields"
			title={ __( 'Shipping address', 'woo-gutenberg-products-block' ) }
			description={ __(
				'Enter the physical address where you want us to deliver your order.',
				'woo-gutenberg-products-block'
			) }
		>
			{ children }
			<CheckboxControl
				className="wc-block-checkout__use-address-for-billing"
				label={ __(
					'Use same address for billing',
					'woo-gutenberg-products-block'
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
