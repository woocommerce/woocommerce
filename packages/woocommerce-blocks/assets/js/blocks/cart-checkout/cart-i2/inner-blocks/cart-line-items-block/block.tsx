/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
/**
 * Internal dependencies
 */
import CartLineItemsTable from '../../full-cart/cart-line-items-table';

const Block = (): JSX.Element => {
	const { cartItems, cartIsLoading } = useStoreCart();
	return (
		<CartLineItemsTable
			lineItems={ cartItems }
			isLoading={ cartIsLoading }
		/>
	);
};

export default Block;
