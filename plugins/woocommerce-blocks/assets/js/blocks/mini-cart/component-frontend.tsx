/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import MiniCartBlock from './block';
import './style.scss';

const renderMiniCartFrontend = () => {
	// Check if button is focused. In that case, we want to refocus it after we
	// replace it with the React equivalent.
	let focusedMiniCartBlock: HTMLElement | null = null;
	/* eslint-disable @wordpress/no-global-active-element */
	if (
		document.activeElement &&
		document.activeElement.classList.contains(
			'wc-block-mini-cart__button'
		) &&
		document.activeElement.parentNode instanceof HTMLElement
	) {
		focusedMiniCartBlock = document.activeElement.parentNode;
	}
	/* eslint-enable @wordpress/no-global-active-element */

	renderFrontend( {
		selector: '.wc-block-mini-cart',
		Block: MiniCartBlock,
		getProps: ( el ) => {
			let colorClassNames = '';
			const button = el.querySelector( '.wc-block-mini-cart__button' );

			if ( button instanceof HTMLButtonElement ) {
				colorClassNames = button.classList
					.toString()
					.replace( 'wc-block-mini-cart__button', '' );
			}
			return {
				initialCartTotals: el.dataset.cartTotals
					? JSON.parse( el.dataset.cartTotals )
					: null,
				initialCartItemsCount: el.dataset.cartItemsCount
					? parseInt( el.dataset.cartItemsCount, 10 )
					: 0,
				isInitiallyOpen: el.dataset.isInitiallyOpen === 'true',
				colorClassNames,
				style: el.dataset.style ? JSON.parse( el.dataset.style ) : {},
				miniCartIcon: el.dataset.miniCartIcon,
				addToCartBehaviour: el.dataset.addToCartBehaviour || 'none',
				hasHiddenPrice: el.dataset.hasHiddenPrice !== 'false',
				priceColor: el.dataset.priceColor
					? JSON.parse( el.dataset.priceColor )
					: {},
				iconColor: el.dataset.iconColor
					? JSON.parse( el.dataset.iconColor )
					: {},
				productCountColor: el.dataset.productCountColor
					? JSON.parse( el.dataset.productCountColor )
					: {},
				contents:
					el.querySelector( '.wc-block-mini-cart__template-part' )
						?.innerHTML ?? '',
				productCountVisibility: el.dataset.productCountVisibility,
			};
		},
	} );

	// Refocus previously focused button if drawer is not open.
	if (
		focusedMiniCartBlock instanceof HTMLElement &&
		! focusedMiniCartBlock.dataset.isInitiallyOpen
	) {
		const innerButton = focusedMiniCartBlock.querySelector(
			'.wc-block-mini-cart__button'
		);
		if ( innerButton instanceof HTMLElement ) {
			innerButton.focus();
		}
	}
};

renderMiniCartFrontend();
