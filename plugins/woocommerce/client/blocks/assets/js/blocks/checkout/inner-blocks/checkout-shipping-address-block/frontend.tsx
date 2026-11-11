/**
 * External dependencies
 */
import clsx from 'clsx';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/blocks-components';
import { useSectionCollapse } from '@woocommerce/base-hooks';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
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
		select( checkoutStore ).isProcessing()
	);
	const { showShippingFields } = useCheckoutAddress();
	const { showFormStepNumbers } = useCheckoutBlockContext();

	useSectionCollapse(
		showShippingFields,
		() => document.getElementById( 'shipping-fields' ),
		{ easeHeight: true }
	);

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
			<Block />
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
