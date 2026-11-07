/**
 * External dependencies
 */
import clsx from 'clsx';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect, useRef } from '@wordpress/element';
import { dispatchEvent } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import './style.scss';

const FrontendBlock = ( {
	children,
	className,
}: {
	children: JSX.Element;
	className: string;
} ): JSX.Element | null => {
	const { cartItems, cartIsLoading } = useStoreCart();
	// Only the empty state that replaces the shopper's last item enters; a cart that loads empty renders as is.
	const hasHadItems = useRef( false );
	if ( cartItems.length > 0 ) {
		hasHadItems.current = true;
	}
	useEffect( () => {
		if ( cartItems.length !== 0 || cartIsLoading ) {
			return;
		}
		dispatchEvent( 'wc-blocks_render_blocks_frontend', {
			element: document.body.querySelector(
				'.wp-block-woocommerce-cart'
			),
		} );
	}, [ cartIsLoading, cartItems ] );
	if ( ! cartIsLoading && cartItems.length === 0 ) {
		return (
			<div
				className={ clsx( className, {
					'is-entering': hasHadItems.current,
				} ) }
			>
				{ children }
			</div>
		);
	}
	return null;
};

export default FrontendBlock;
