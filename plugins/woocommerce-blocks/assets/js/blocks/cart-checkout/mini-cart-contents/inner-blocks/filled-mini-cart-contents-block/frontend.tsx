/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';

type FilledMiniCartContentsBlockProps = {
	children: JSX.Element;
	className: string;
};

const FilledMiniCartContentsBlock = ( {
	children,
	className,
}: FilledMiniCartContentsBlockProps ): JSX.Element | null => {
	const { cartItems } = useStoreCart();

	if ( cartItems.length === 0 ) {
		return null;
	}

	return <div className={ className }>{ children }</div>;
};

export default FilledMiniCartContentsBlock;
