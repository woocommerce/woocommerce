/**
 * External dependencies
 */
import classnames from 'classnames';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	RawHTML,
	useState,
	useEffect,
	useCallback,
	unmountComponentAtNode,
} from '@wordpress/element';
import {
	renderBlock,
	translateJQueryEventToNative,
} from '@woocommerce/base-utils';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import Drawer from '@woocommerce/base-components/drawer';
import {
	formatPrice,
	getCurrencyFromPriceResponse,
} from '@woocommerce/price-format';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isString, isBoolean } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import QuantityBadge from './quantity-badge';
import MiniCartContentsBlock from '../mini-cart-contents/block';
import './style.scss';

interface Props {
	isInitiallyOpen?: boolean;
	transparentButton: boolean;
	colorClassNames?: string;
	style?: Record< string, Record< string, string > >;
	contents: string;
}

const MiniCartBlock = ( {
	isInitiallyOpen = false,
	colorClassNames,
	style,
	contents = '',
}: Props ): JSX.Element => {
	const { cartItemsCount, cartIsLoading, cartTotals } = useStoreCart();
	const [ isOpen, setIsOpen ] = useState< boolean >( isInitiallyOpen );
	// We already rendered the HTML drawer placeholder, so we want to skip the
	// slide in animation.
	const [ skipSlideIn, setSkipSlideIn ] = useState< boolean >(
		isInitiallyOpen
	);
	const [ contentsNode, setContentsNode ] = useState< HTMLDivElement | null >(
		null
	);

	const contentsRef = useCallback( ( node ) => {
		setContentsNode( node );
	}, [] );

	useEffect( () => {
		if ( contentsNode instanceof Element ) {
			const container = contentsNode.querySelector(
				'.wc-block-mini-cart-contents'
			);
			if ( ! container ) {
				return;
			}
			if ( isOpen ) {
				renderBlock( {
					Block: MiniCartContentsBlock,
					container,
				} );
			}
		}

		return () => {
			if ( contentsNode instanceof Element && isOpen ) {
				const container = contentsNode.querySelector(
					'.wc-block-mini-cart-contents'
				);
				if ( container ) {
					unmountComponentAtNode( container );
				}
			}
		};
	}, [ isOpen, contentsNode ] );

	useEffect( () => {
		const openMiniCart = () => {
			setSkipSlideIn( false );
			setIsOpen( true );
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
	}, [] );

	const showIncludingTax = getSettingWithCoercion(
		'displayCartPricesIncludingTax',
		false,
		isBoolean
	);

	const taxLabel = getSettingWithCoercion( 'taxLabel', '', isString );

	const subTotal = showIncludingTax
		? parseInt( cartTotals.total_items, 10 ) +
		  parseInt( cartTotals.total_items_tax, 10 )
		: parseInt( cartTotals.total_items, 10 );

	const ariaLabel = sprintf(
		/* translators: %1$d is the number of products in the cart. %2$s is the cart total */
		_n(
			'%1$d item in cart, total price of %2$s',
			'%1$d items in cart, total price of %2$s',
			cartItemsCount,
			'woo-gutenberg-products-block'
		),
		cartItemsCount,
		formatPrice( subTotal, getCurrencyFromPriceResponse( cartTotals ) )
	);

	const colorStyle = {
		backgroundColor: style?.color?.background,
		color: style?.color?.text,
	};

	return (
		<>
			<button
				className={ `wc-block-mini-cart__button ${ colorClassNames }` }
				style={ colorStyle }
				onClick={ () => {
					if ( ! isOpen ) {
						setIsOpen( true );
						setSkipSlideIn( false );
					}
				} }
				aria-label={ ariaLabel }
			>
				<span className="wc-block-mini-cart__amount">
					{ formatPrice(
						subTotal,
						getCurrencyFromPriceResponse( cartTotals )
					) }
				</span>
				{ taxLabel !== '' && subTotal !== 0 && (
					<small className="wc-block-mini-cart__tax-label">
						{ taxLabel }
					</small>
				) }
				<QuantityBadge
					count={ cartItemsCount }
					colorClassNames={ colorClassNames }
					style={ colorStyle }
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
				title={
					cartIsLoading
						? __( 'Your cart', 'woo-gutenberg-products-block' )
						: sprintf(
								/* translators: %d is the count of items in the cart. */
								_n(
									'Your cart (%d item)',
									'Your cart (%d items)',
									cartItemsCount,
									'woo-gutenberg-products-block'
								),
								cartItemsCount
						  )
				}
				isOpen={ isOpen }
				onClose={ () => {
					setIsOpen( false );
				} }
				slideIn={ ! skipSlideIn }
			>
				<div ref={ contentsRef }>
					<RawHTML>{ contents }</RawHTML>
				</div>
			</Drawer>
		</>
	);
};

export default MiniCartBlock;
