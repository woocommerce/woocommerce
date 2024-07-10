/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { CheckoutExpressPayment } from '../../../cart-checkout-shared/payment-methods';

const Block = ( {
	className,
	buttonHeight,
	buttonBorderRadius,
}: {
	className?: string;
	buttonHeight: string;
	buttonBorderRadius: string;
} ): JSX.Element | null => {
	const { cartNeedsPayment } = useStoreCart();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<div className={ className }>
			buttonBorderRadius,
			<CheckoutExpressPayment
				buttonHeight={ buttonHeight }
				buttonBorderRadius={ buttonBorderRadius }
			/>
		</div>
	);
};

export default Block;
