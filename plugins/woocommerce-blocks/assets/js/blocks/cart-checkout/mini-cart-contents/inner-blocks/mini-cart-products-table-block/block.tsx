/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import CartLineItemsTable from '../../../cart/cart-line-items-table';

const Block = (): JSX.Element => {
	const { cartItems, cartIsLoading } = useStoreCart();
	return (
		<div className="wc-block-mini-cart__products-table">
			<CartLineItemsTable
				lineItems={ cartItems }
				isLoading={ cartIsLoading }
			/>
		</div>
	);
};

export default Block;
