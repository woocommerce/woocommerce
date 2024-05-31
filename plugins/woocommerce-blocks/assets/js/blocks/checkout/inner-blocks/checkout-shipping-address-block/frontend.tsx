/**
 * External dependencies
 */
import clsx from 'clsx';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/blocks-components';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
import { useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';
import { useCheckoutBlockContext } from '../../context';

const FrontendBlock = ( {
	title,
	description,
	children,
	className,
}: {
	title: string;
	description: string;
	children: JSX.Element;
	className?: string;
} ) => {
	const checkoutIsProcessing = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).isProcessing()
	);
	const { showShippingFields } = useCheckoutAddress();
	const {
		showCompanyField,
		requireCompanyField,
		showApartmentField,
		requireApartmentField,
		showPhoneField,
		requirePhoneField,
		showFormStepNumbers,
	} = useCheckoutBlockContext();

	if ( ! showShippingFields ) {
		return null;
	}

	return (
		<FormStep
			id="shipping-fields"
			disabled={ checkoutIsProcessing }
			className={ clsx(
				'wc-block-checkout__shipping-fields',
				className
			) }
			title={ title }
			description={ description }
			showStepNumber={ showFormStepNumbers }
		>
			<Block
				showCompanyField={ showCompanyField }
				requireCompanyField={ requireCompanyField }
				showApartmentField={ showApartmentField }
				requireApartmentField={ requireApartmentField }
				showPhoneField={ showPhoneField }
				requirePhoneField={ requirePhoneField }
			/>
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
