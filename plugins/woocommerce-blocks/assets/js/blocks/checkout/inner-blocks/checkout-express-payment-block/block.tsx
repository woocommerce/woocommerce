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
	showButtonStyles,
}: {
	className?: string;
	buttonHeight: string;
	buttonBorderRadius: string;
	showButtonStyles: boolean;
} ): JSX.Element | null => {
	const { cartNeedsPayment } = useStoreCart();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<div className={ className }>
			<CheckoutExpressPayment
				buttonHeight={ buttonHeight }
				buttonBorderRadius={ buttonBorderRadius }
				showButtonStyles={ showButtonStyles }
			/>
		</div>
	);
};

export default Block;
