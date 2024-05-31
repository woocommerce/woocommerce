/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { CartExpressPayment } from '../../../cart-checkout-shared/payment-methods';

const Block = ( { className }: { className: string } ): JSX.Element | null => {
	const { cartNeedsPayment } = useStoreCart();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<div className={ clsx( 'wc-block-cart__payment-options', className ) }>
			<CartExpressPayment />
		</div>
	);
};

export default Block;
