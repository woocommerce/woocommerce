/**
 * External dependencies
 */
import classnames from 'classnames';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutContext } from '@woocommerce/base-context';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';

const FrontendBlock = ( {
	title,
	description,
	showStepNumber,
	children,
	className,
}: {
	title: string;
	description: string;
	requireCompanyField: boolean;
	requirePhoneField: boolean;
	showApartmentField: boolean;
	showCompanyField: boolean;
	showPhoneField: boolean;
	showStepNumber: boolean;
	children: JSX.Element;
	className?: string;
} ) => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();
	const { showShippingFields } = useCheckoutAddress();

	if ( ! showShippingFields ) {
		return null;
	}

	return (
		<FormStep
			id="shipping-option"
			disabled={ checkoutIsProcessing }
			className={ classnames(
				'wc-block-checkout__shipping-option',
				className
			) }
			title={ title }
			description={ description }
			showStepNumber={ showStepNumber }
		>
			<Block />
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
