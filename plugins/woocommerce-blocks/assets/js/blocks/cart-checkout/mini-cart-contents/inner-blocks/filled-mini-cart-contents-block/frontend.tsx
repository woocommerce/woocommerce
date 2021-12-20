/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';

const FilledMiniCartContentsBlock = ( {
	children,
}: {
	children: JSX.Element | JSX.Element[];
} ): JSX.Element | null => {
	const { cartItems } = useStoreCart();

	if ( cartItems.length === 0 ) {
		return null;
	}

	return <>{ children }</>;
};

export default FilledMiniCartContentsBlock;
