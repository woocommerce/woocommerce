/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect, useLayoutEffect, useRef } from '@wordpress/element';
import { dispatchEvent, whenAnimationsEnd } from '@woocommerce/base-utils';

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
	const { cartItems, cartIsLoading, hasPendingItemsOperations } =
		useStoreCart();
	const isEmpty = ! cartIsLoading && cartItems.length === 0;
	const hasItems = cartItems.length > 0;
	const ref = useRef< HTMLDivElement >( null );
	const hasHadItems = useRef( false );
	const wasKeyPress = useRef( false );

	// A click from a key press, such as Enter on Remove, has no click count. While a removal is in flight, keep the
	// click that started it, so a later click elsewhere does not change how the empty cart shows.
	useEffect( () => {
		if ( hasPendingItemsOperations ) {
			return;
		}
		const onClick = ( event: MouseEvent ) => {
			wasKeyPress.current = event.detail === 0;
		};
		document.addEventListener( 'click', onClick, true );
		return () => document.removeEventListener( 'click', onClick, true );
	}, [ hasPendingItemsOperations ] );

	// The empty cart rises in only when it replaces the cart after the shopper removes the last item. A cart that
	// loads empty, or that empties after a key press, shows immediately.
	useLayoutEffect( () => {
		const element = ref.current;
		if ( hasItems ) {
			hasHadItems.current = true;
			return;
		}
		if ( ! element || ! hasHadItems.current || wasKeyPress.current ) {
			return;
		}
		element.setAttribute( 'data-wc-entering', '' );
		return whenAnimationsEnd( element, () =>
			element.removeAttribute( 'data-wc-entering' )
		);
	}, [ isEmpty, hasItems ] );

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
	if ( isEmpty ) {
		return (
			<div className={ className } ref={ ref }>
				{ children }
			</div>
		);
	}
	return null;
};

export default FrontendBlock;
