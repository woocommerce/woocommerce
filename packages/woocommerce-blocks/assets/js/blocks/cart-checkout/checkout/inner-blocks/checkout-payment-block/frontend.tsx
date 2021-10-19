/**
 * External dependencies
 */
import { useStoreCart, useEmitResponse } from '@woocommerce/base-context/hooks';
import withFilteredAttributes from '@woocommerce/base-hocs/with-filtered-attributes';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutContext,
	StoreNoticesProvider,
} from '@woocommerce/base-context';

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
}: {
	title: string;
	description: string;
	showStepNumber: boolean;
	children: JSX.Element;
} ) => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();
	const { cartNeedsPayment } = useStoreCart();
	const { noticeContexts } = useEmitResponse();

	if ( ! cartNeedsPayment ) {
		return null;
	}
	return (
		<FormStep
			id="payment-method"
			disabled={ checkoutIsProcessing }
			className="wc-block-checkout__payment-method"
			title={ title }
			description={ description }
			showStepNumber={ showStepNumber }
		>
			<StoreNoticesProvider context={ noticeContexts.PAYMENTS }>
				<Block />
			</StoreNoticesProvider>
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
