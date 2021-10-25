/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { CartExpressPayment } from '../../../payment-methods';

const Block = (): JSX.Element | null => {
	const { cartNeedsPayment } = useStoreCart();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<div className="wc-block-cart__payment-options">
			<CartExpressPayment />
		</div>
	);
};

export default Block;
