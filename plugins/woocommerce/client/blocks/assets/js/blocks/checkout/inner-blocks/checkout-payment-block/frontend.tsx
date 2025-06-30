/**
 * External dependencies
 */
import clsx from 'clsx';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import {
	FormStep,
	StoreNoticesContainer,
} from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { noticeContexts } from '@woocommerce/base-context';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';

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
	const { showFormStepNumbers } = useCheckoutBlockContext();
	const checkoutIsProcessing = useSelect( ( select ) =>
		select( checkoutStore ).isProcessing()
	);
	const { cartNeedsPayment } = useStoreCart();

	if ( ! cartNeedsPayment ) {
		return null;
	}
	return (
		<FormStep
			id="payment-method"
			disabled={ checkoutIsProcessing }
			className={ clsx( 'wc-block-checkout__payment-method', className ) }
			title={ title }
			description={ description }
			showStepNumber={ showFormStepNumbers }
		>
			<StoreNoticesContainer context={ noticeContexts.PAYMENTS } />
			<Block />
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
