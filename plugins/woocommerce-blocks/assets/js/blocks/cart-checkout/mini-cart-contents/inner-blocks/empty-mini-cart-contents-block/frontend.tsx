/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */

type EmptyMiniCartContentsBlockProps = {
	children: JSX.Element | JSX.Element[];
};

const EmptyMiniCartContentsBlock = ( {
	children,
}: EmptyMiniCartContentsBlockProps ): JSX.Element | null => {
	const { cartItems, cartIsLoading } = useStoreCart();

	if ( cartIsLoading || cartItems.length > 0 ) {
		return null;
	}

	return (
		<div className="wp-block-woocommerce-empty-mini-cart-contents-block">
			{ children }
		</div>
	);
};

export default EmptyMiniCartContentsBlock;
