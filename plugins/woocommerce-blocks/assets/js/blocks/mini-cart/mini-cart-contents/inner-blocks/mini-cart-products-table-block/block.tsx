/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { CartLineItemsTable } from '@woocommerce/base-components/cart-checkout';
import classNames from 'classnames';

type MiniCartProductsTableBlockProps = {
	className: string;
};

const Block = ( {
	className,
}: MiniCartProductsTableBlockProps ): JSX.Element => {
	const { cartItems, cartIsLoading } = useStoreCart();
	return (
		<div
			className={ classNames(
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
