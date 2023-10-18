/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import {
	getCurrencyFromPriceResponse,
	formatPrice,
} from '@woocommerce/price-format';
import {
	CartResponse,
	CartResponseTotals,
	isBoolean,
} from '@woocommerce/types';
import { getSettingWithCoercion } from '@woocommerce/settings';
import type { ColorPaletteOption } from '@woocommerce/editor-components/color-panel/types';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { Attributes } from '../edit';

const getPrice = ( totals: CartResponseTotals, showIncludingTax: boolean ) => {
	const currency = getCurrencyFromPriceResponse( totals );

	const subTotal = showIncludingTax
		? parseInt( totals.total_items, 10 ) +
		  parseInt( totals.total_items_tax, 10 )
		: parseInt( totals.total_items, 10 );

	return formatPrice( subTotal, currency );
};

export const updateTotals = (
	cartData: [ CartResponseTotals, number ] | undefined
) => {
	if ( ! cartData ) {
		return;
	}
	const [ totals, quantity ] = cartData;
	const showIncludingTax = getSettingWithCoercion(
		'displayCartPricesIncludingTax',
		false,
		isBoolean
	);
	const amount = getPrice( totals, showIncludingTax );
	const miniCartBlocks = document.querySelectorAll( '.wc-block-mini-cart' );
	const miniCartQuantities = document.querySelectorAll(
		'.wc-block-mini-cart__badge'
	);
	const miniCartAmounts = document.querySelectorAll(
		'.wc-block-mini-cart__amount'
	);

	miniCartBlocks.forEach( ( miniCartBlock ) => {
		if ( ! ( miniCartBlock instanceof HTMLElement ) ) {
			return;
		}

		const miniCartButton = miniCartBlock.querySelector(
			'.wc-block-mini-cart__button'
		);

		miniCartButton?.setAttribute(
			'aria-label',
			miniCartBlock.dataset.hasHiddenPrice
				? sprintf(
						/* translators: %s number of products in cart. */
						_n(
							'%1$d item in cart',
							'%1$d items in cart',
							quantity,
							'woo-gutenberg-products-block'
						),
						quantity
				  )
				: sprintf(
						/* translators: %1$d is the number of products in the cart. %2$s is the cart total */
						_n(
							'%1$d item in cart, total price of %2$s',
							'%1$d items in cart, total price of %2$s',
							quantity,
							'woo-gutenberg-products-block'
						),
						quantity,
						amount
				  )
		);

		miniCartBlock.dataset.cartTotals = JSON.stringify( totals );
		miniCartBlock.dataset.cartItemsCount = quantity.toString();
	} );
	miniCartQuantities.forEach( ( miniCartQuantity ) => {
		if ( quantity > 0 || miniCartQuantity.textContent !== '' ) {
			miniCartQuantity.textContent = quantity.toString();
		}
	} );
	miniCartAmounts.forEach( ( miniCartAmount ) => {
		miniCartAmount.textContent = amount;
	} );

	// Show the tax label only if there are products in the cart.
	if ( quantity > 0 ) {
		const miniCartTaxLabels = document.querySelectorAll(
			'.wc-block-mini-cart__tax-label'
		);
		miniCartTaxLabels.forEach( ( miniCartTaxLabel ) => {
			miniCartTaxLabel.removeAttribute( 'hidden' );
		} );
	}
};

export const getMiniCartTotalsFromLocalStorage = ():
	| [ CartResponseTotals, number ]
	| undefined => {
	const rawMiniCartTotals = localStorage.getItem(
		'wc-blocks_mini_cart_totals'
	);
	if ( ! rawMiniCartTotals ) {
		return undefined;
	}
	const cartData = JSON.parse( rawMiniCartTotals );
	return [ cartData.totals, cartData.itemsCount ] as [
		CartResponseTotals,
		number
	];
};

export const getMiniCartTotalsFromServer = async (): Promise<
	[ CartResponseTotals, number ] | undefined
> => {
	return apiFetch< CartResponse >( {
		path: '/wc/store/v1/cart',
	} )
		.then( ( data: CartResponse ) => {
			// Save server data to local storage, so we can re-fetch it faster
			// on the next page load.
			localStorage.setItem(
				'wc-blocks_mini_cart_totals',
				JSON.stringify( {
					totals: data.totals,
					itemsCount: data.items_count,
				} )
			);
			return [ data.totals, data.items_count ] as [
				CartResponseTotals,
				number
			];
		} )
		.catch( ( error ) => {
			// eslint-disable-next-line no-console
			console.error( error );
			return undefined;
		} );
};

interface MaybeInCompatibleAttributes
	extends Omit<
		Attributes,
		'priceColor' | 'iconColor' | 'productCountColor'
	> {
	priceColorValue?: string;
	iconColorValue?: string;
	productCountColorValue?: string;
	priceColor: Partial< ColorPaletteOption > | string;
	iconColor: Partial< ColorPaletteOption > | string;
	productCountColor: Partial< ColorPaletteOption > | string;
}

export function migrateAttributesToColorPanel(
	attributes: MaybeInCompatibleAttributes
): Attributes {
	const attrs = { ...attributes };

	if ( attrs.priceColorValue && ! attrs.priceColor ) {
		attrs.priceColor = {
			color: attributes.priceColorValue as string,
		};
		delete attrs.priceColorValue;
	}

	if ( attrs.iconColorValue && ! attrs.iconColor ) {
		attrs.iconColor = {
			color: attributes.iconColorValue as string,
		};
		delete attrs.iconColorValue;
	}

	if ( attrs.productCountColorValue && ! attrs.productCountColor ) {
		attrs.productCountColor = {
			color: attributes.productCountColorValue as string,
		};
		delete attrs.productCountColorValue;
	}

	return <Attributes>attrs;
}
