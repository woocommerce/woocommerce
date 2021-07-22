/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { CheckoutExpressPayment } from '../../../payment-methods';

const Block = (): JSX.Element | null => {
	const { cartNeedsPayment } = useStoreCart();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return <CheckoutExpressPayment />;
};

export default Block;
