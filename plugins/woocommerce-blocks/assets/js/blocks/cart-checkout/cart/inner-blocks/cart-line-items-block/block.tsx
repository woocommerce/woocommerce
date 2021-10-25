/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
/**
 * Internal dependencies
 */
import CartLineItemsTable from '../../cart-line-items-table';

const Block = ( { className }: { className: string } ): JSX.Element => {
	const { cartItems, cartIsLoading } = useStoreCart();
	return (
		<CartLineItemsTable
			className={ className }
			lineItems={ cartItems }
			isLoading={ cartIsLoading }
		/>
	);
};

export default Block;
