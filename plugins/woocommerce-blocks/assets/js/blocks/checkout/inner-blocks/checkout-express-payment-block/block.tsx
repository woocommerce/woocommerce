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
}: {
	className?: string;
	buttonHeight: string;
} ): JSX.Element | null => {
	const { cartNeedsPayment } = useStoreCart();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<div className={ className }>
			<CheckoutExpressPayment buttonHeight={ buttonHeight } />
		</div>
	);
};

export default Block;
