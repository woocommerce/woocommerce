/**
 * External dependencies
 */
import { renderParentBlock } from '@woocommerce/atomic-utils';
import Drawer from '@woocommerce/base-components/drawer';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import {
	getValidBlockAttributes,
	translateJQueryEventToNative,
} from '@woocommerce/base-utils';
import { getRegisteredBlockComponents } from '@woocommerce/blocks-registry';
import { isCartResponseTotals, isNumber } from '@woocommerce/types';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import clsx from 'clsx';
import type { ReactRootWithContainer } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import type { BlockAttributes } from './types';
import { MiniCartContentsBlock } from '../mini-cart/mini-cart-contents/block';
import './style.scss';
import {
	blockName,
	attributes as miniCartContentsAttributes,
} from '../mini-cart/mini-cart-contents/attributes';

type Props = BlockAttributes;

function getScrollbarWidth() {
	return window.innerWidth - document.documentElement.clientWidth;
}

export const MiniCartDrawer = ( attributes: Props ): JSX.Element => {
	const {
		isInitiallyOpen = false,
		contents = '',
		addToCartBehaviour = 'none',
	} = attributes;

	const {
		cartItemsCount: cartItemsCountFromApi,
		cartIsLoading,
		cartTotals: cartTotalsFromApi,
	} = useStoreCart();

	const cartIsLoadingForTheFirstTime = useRef( cartIsLoading );

	useEffect( () => {
		if ( cartIsLoadingForTheFirstTime.current && ! cartIsLoading ) {
			cartIsLoadingForTheFirstTime.current = false;
		}
	}, [ cartIsLoading, cartIsLoadingForTheFirstTime ] );

	useEffect( () => {
		if (
			! cartIsLoading &&
			isCartResponseTotals( cartTotalsFromApi ) &&
			isNumber( cartItemsCountFromApi )
		) {
			// Save server data to local storage, so we can re-fetch it faster
			// on the next page load.
			localStorage.setItem(
				'wc-blocks_mini_cart_totals',
				JSON.stringify( {
					totals: cartTotalsFromApi,
					itemsCount: cartItemsCountFromApi,
				} )
			);
		}
	} );

	const [ isOpen, setIsOpen ] = useState< boolean >( isInitiallyOpen );
	// We already rendered the HTML drawer placeholder, so we want to skip the
	// slide in animation.
	const [ skipSlideIn, setSkipSlideIn ] =
		useState< boolean >( isInitiallyOpen );
	const [ contentsNode, setContentsNode ] = useState< HTMLDivElement | null >(
		null
	);

	// Hack to force re-render when elements are not rendered in expected order.
	const [ observerState, setObserverState ] = useState< boolean >( false );

	const contentsRef = useCallback( ( node ) => {
		setContentsNode( node );

		const mutationObserver = new MutationObserver( ( mutationsList ) => {
			for ( const mutation of mutationsList ) {
				if ( mutation.type === 'childList' ) {
					// Check if a child matching the selector exists
					const childElement = node.querySelector(
						'.wc-block-components-drawer__close-wrapper'
					);
					if ( childElement ) {
						// Hack to force re-render when elements are not rendered in expected order.
						setObserverState( ! observerState );
					}
				}
			}
		} );

		mutationObserver.observe( node, {
			childList: true,
			subtree: true,
		} );
	}, [] );

	const rootRef = useRef< ReactRootWithContainer[] | null >( null );

	useEffect( () => {
		const body = document.querySelector( 'body' );
		if ( body ) {
			const scrollBarWidth = getScrollbarWidth();
			if ( isOpen ) {
				Object.assign( body.style, {
					overflow: 'hidden',
					paddingRight: scrollBarWidth + 'px',
				} );
			} else {
				Object.assign( body.style, { overflow: '', paddingRight: 0 } );
			}
		}
	}, [ isOpen ] );

	useEffect( () => {
		if ( contentsNode instanceof Element ) {
			const container = contentsNode.querySelector(
				'.wp-block-woocommerce-mini-cart-contents'
			);
			if ( ! container ) {
				return;
			}
			if ( isOpen ) {
				const renderedBlock = renderParentBlock( {
					Block: MiniCartContentsBlock,
					blockName,
					getProps: ( el: Element ) => {
						return {
							attributes: getValidBlockAttributes(
								miniCartContentsAttributes,
								/* eslint-disable @typescript-eslint/no-explicit-any */
								( el instanceof HTMLElement
									? el.dataset
									: {} ) as any
							),
						};
					},
					selector: '.wp-block-woocommerce-mini-cart-contents',
					blockMap: getRegisteredBlockComponents( blockName ),
				} );
				rootRef.current = renderedBlock;
			}
		}

		return () => {
			if ( contentsNode instanceof Element && isOpen ) {
				const unmountingContainer = contentsNode.querySelector(
					'.wp-block-woocommerce-mini-cart-contents'
				);

				if ( unmountingContainer ) {
					const foundRoot = rootRef?.current?.find(
						( { container } ) => unmountingContainer === container
					);
					if ( typeof foundRoot?.root?.unmount === 'function' ) {
						setTimeout( () => {
							foundRoot.root.unmount();
						} );
					}
				}
			}
		};
	}, [ isOpen, contentsNode ] );

	useEffect( () => {
		const openMiniCart = () => {
			if ( addToCartBehaviour === 'open_drawer' ) {
				setSkipSlideIn( false );
				setIsOpen( true );
			}
		};

		// Make it so we can read jQuery events triggered by WC Core elements.
		const removeJQueryAddedToCartEvent = translateJQueryEventToNative(
			'added_to_cart',
			'wc-blocks_added_to_cart'
		);

		document.body.addEventListener(
			'wc-blocks_added_to_cart',
			openMiniCart
		);

		return () => {
			removeJQueryAddedToCartEvent();

			document.body.removeEventListener(
				'wc-blocks_added_to_cart',
				openMiniCart
			);
		};
	}, [ addToCartBehaviour ] );

	return (
		// We only render the drawer in this component now. The button is controlled by Interactivity API.
		<>
			<Drawer
				className={ clsx( 'wc-block-mini-cart__drawer', 'is-mobile', {
					'is-loading': cartIsLoading,
				} ) }
				isOpen={ isOpen }
				onClose={ () => {
					setIsOpen( false );
				} }
				slideIn={ ! skipSlideIn }
			>
				<div
					className="wc-block-mini-cart-interactivity__template-part"
					ref={ contentsRef }
					dangerouslySetInnerHTML={ { __html: contents } }
				></div>
			</Drawer>
		</>
	);
};
