/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect, useRef } from 'react';

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

	const elementRef = useRef< HTMLDivElement >( null );

	useEffect( () => {
		if ( cartItems.length === 0 && ! cartIsLoading ) {
			elementRef.current?.focus();
		}
	}, [ cartItems, cartIsLoading ] );

	if ( cartIsLoading || cartItems.length > 0 ) {
		return null;
	}

	return (
		<div
			tabIndex={ -1 }
			ref={ elementRef }
			className="wp-block-woocommerce-empty-mini-cart-contents-block"
		>
			{ children }
		</div>
	);
};

export default EmptyMiniCartContentsBlock;
