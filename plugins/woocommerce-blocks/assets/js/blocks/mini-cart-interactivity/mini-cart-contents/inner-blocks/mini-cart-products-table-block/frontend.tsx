/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { CartLineItemsTable } from '@woocommerce/base-components/cart-checkout';
import clsx from 'clsx';
import { CartItem } from '../../../../../types';

type MiniCartProductsTableBlockProps = {
	className: string;
};

const Block = ( {
	className,
}: MiniCartProductsTableBlockProps ): JSX.Element => {
	// const { cartItems, cartIsLoading } = useStoreCart();

	const cartIsLoading = false;
	const cartItems: CartItem[] = [];
	return (
		<div
			className={ clsx(
				className,
				'wc-block-mini-cart__products-table'
			) }
		>
			<CartLineItemsTable
				lineItems={ cartItems }
				isLoading={ cartIsLoading }
				className="wc-block-mini-cart-items"
			/>
		</div>
	);
};

export default Block;
