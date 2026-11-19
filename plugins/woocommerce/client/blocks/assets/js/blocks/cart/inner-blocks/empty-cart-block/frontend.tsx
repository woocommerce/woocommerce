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
	const ref = useRef< HTMLDivElement >( null );
	const isPointerRemoval = useRef( false );
	const previousRemovalCount = useRef( 0 );

	// Read the input when a removal starts, such as Enter on Remove, so a click elsewhere while it is in flight
	// does not change how the empty cart shows. Clear it when no removal is pending. This effect runs after the
	// layout effect below, so the render that empties the cart still sees the removal.
	useEffect( () => {
		if ( removalCount > previousRemovalCount.current ) {
			isPointerRemoval.current = ! isKeyboardInput();
		} else if ( removalCount === 0 ) {
			isPointerRemoval.current = false;
		}
		previousRemovalCount.current = removalCount;
	}, [ removalCount ] );

	// The empty cart rises in only when it replaces the cart after the shopper removes the last item with a pointer.
	// A cart that loads empty, empties after a key press, or empties through another update shows immediately.
	useLayoutEffect( () => {
		const element = ref.current;
		if ( ! isEmpty || ! element || ! isPointerRemoval.current ) {
			return;
		}
		element.setAttribute( 'data-wc-entering', '' );
		return whenAnimationsEnd( element, () =>
			element.removeAttribute( 'data-wc-entering' )
		);
	}, [ isEmpty ] );

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
