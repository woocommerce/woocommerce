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
import {
	formatPrice,
	getCurrencyFromPriceResponse,
} from '@woocommerce/price-format';
import { getSettingWithCoercion } from '@woocommerce/settings';
import {
	isBoolean,
	isString,
	isCartResponseTotals,
	isNumber,
} from '@woocommerce/types';
import {
	unmountComponentAtNode,
	useCallback,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { sprintf, _n } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import type { BlockAttributes } from './types';
import QuantityBadge from './quantity-badge';
import { MiniCartContentsBlock } from './mini-cart-contents/block';
import './style.scss';
import {
	blockName,
	attributes as miniCartContentsAttributes,
} from './mini-cart-contents/attributes';
import { defaultColorItem } from './utils/defaults';

type Props = BlockAttributes;

function getScrollbarWidth() {
	return window.innerWidth - document.documentElement.clientWidth;
}

const MiniCartBlock = ( attributes: Props ): JSX.Element => {
	const {
		initialCartItemsCount,
		initialCartTotals,
		isInitiallyOpen = false,
		colorClassNames,
		contents = '',
		miniCartIcon,
		addToCartBehaviour = 'none',
		hasHiddenPrice = false,
		priceColor = defaultColorItem,
		iconColor = defaultColorItem,
		productCountColor = defaultColorItem,
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

	const contentsRef = useCallback( ( node ) => {
		setContentsNode( node );
	}, [] );

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
				renderParentBlock( {
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
			}
		}

		return () => {
			if ( contentsNode instanceof Element && isOpen ) {
				const container = contentsNode.querySelector(
					'.wp-block-woocommerce-mini-cart-contents'
				);
				if ( container ) {
					unmountComponentAtNode( container );
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

	const showIncludingTax = getSettingWithCoercion(
		'displayCartPricesIncludingTax',
		false,
		isBoolean
	);

	const taxLabel = getSettingWithCoercion( 'taxLabel', '', isString );

	const cartTotals =
		cartIsLoadingForTheFirstTime.current &&
		isCartResponseTotals( initialCartTotals )
			? initialCartTotals
			: cartTotalsFromApi;

	const cartItemsCount =
		cartIsLoadingForTheFirstTime.current &&
		isNumber( initialCartItemsCount )
			? initialCartItemsCount
			: cartItemsCountFromApi;

	const subTotal = showIncludingTax
		? parseInt( cartTotals.total_items, 10 ) +
		  parseInt( cartTotals.total_items_tax, 10 )
		: parseInt( cartTotals.total_items, 10 );

	const ariaLabel = hasHiddenPrice
		? sprintf(
				/* translators: %1$d is the number of products in the cart. */
				_n(
					'%1$d item in cart',
					'%1$d items in cart',
					cartItemsCount,
					'woo-gutenberg-products-block'
				),
				cartItemsCount
		  )
		: sprintf(
				/* translators: %1$d is the number of products in the cart. %2$s is the cart total */
				_n(
					'%1$d item in cart, total price of %2$s',
					'%1$d items in cart, total price of %2$s',
					cartItemsCount,
					'woo-gutenberg-products-block'
				),
				cartItemsCount,
				formatPrice(
					subTotal,
					getCurrencyFromPriceResponse( cartTotals )
				)
		  );

	return (
		<>
			<button
				className={ `wc-block-mini-cart__button ${ colorClassNames }` }
				onClick={ () => {
					if ( ! isOpen ) {
						setIsOpen( true );
						setSkipSlideIn( false );
					}
				} }
				aria-label={ ariaLabel }
			>
				{ ! hasHiddenPrice && (
					<span
						className="wc-block-mini-cart__amount"
						style={ { color: priceColor.color } }
					>
						{ formatPrice(
							subTotal,
							getCurrencyFromPriceResponse( cartTotals )
						) }
					</span>
				) }
				{ taxLabel !== '' && subTotal !== 0 && ! hasHiddenPrice && (
					<small
						className="wc-block-mini-cart__tax-label"
						style={ { color: priceColor.color } }
					>
						{ taxLabel }
					</small>
				) }
				<QuantityBadge
					count={ cartItemsCount }
					icon={ miniCartIcon }
					iconColor={ iconColor }
					productCountColor={ productCountColor }
				/>
			</button>
			<Drawer
				className={ classnames(
					'wc-block-mini-cart__drawer',
					'is-mobile',
					{
						'is-loading': cartIsLoading,
					}
				) }
				isOpen={ isOpen }
				onClose={ () => {
					setIsOpen( false );
				} }
				slideIn={ ! skipSlideIn }
			>
				<div
					className="wc-block-mini-cart__template-part"
					ref={ contentsRef }
					dangerouslySetInnerHTML={ { __html: contents } }
				></div>
			</Drawer>
		</>
	);
};

export default MiniCartBlock;
