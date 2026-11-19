/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect, useLayoutEffect, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import {
	dispatchEvent,
	isKeyboardInput,
	whenAnimationsEnd,
} from '@woocommerce/base-utils';

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
	const removalCount = useSelect(
		( select ) => select( cartStore ).getItemsPendingDelete().length,
		[]
	);
	const isEmpty = ! cartIsLoading && cartItems.length === 0;
	const hasItems = cartItems.length > 0;
	const ref = useRef< HTMLDivElement >( null );
	const hasHadItems = useRef( false );
	const wasKeyPress = useRef( false );
	const previousRemovalCount = useRef( 0 );

	// Read the input when a removal starts, such as Enter on Remove, so a click elsewhere while it is in flight
	// does not change how the empty cart shows.
	useEffect( () => {
		if ( removalCount > previousRemovalCount.current ) {
			wasKeyPress.current = isKeyboardInput();
		}
		previousRemovalCount.current = removalCount;
	}, [ removalCount ] );

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
