/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import {
	getCurrencyFromPriceResponse,
	formatPrice,
} from '@woocommerce/price-format';
import { CartResponse } from '@woocommerce/types';

export const updateTotals = ( totals: [ string, number ] | undefined ) => {
	if ( ! totals ) {
		return;
	}
	const [ amount, quantity ] = totals;
	const miniCartButtons = document.querySelectorAll(
		'.wc-block-mini-cart__button'
	);
	const miniCartQuantities = document.querySelectorAll(
		'.wc-block-mini-cart__badge'
	);
	const miniCartAmounts = document.querySelectorAll(
		'.wc-block-mini-cart__amount'
	);

	miniCartButtons.forEach( ( miniCartButton ) => {
		miniCartButton.setAttribute(
			'aria-label',
			sprintf(
				/* translators: %s number of products in cart. */
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
	| [ string, number ]
	| undefined => {
	const rawMiniCartTotals = localStorage.getItem(
		'wc-blocks_mini_cart_totals'
	);
	if ( ! rawMiniCartTotals ) {
		return undefined;
	}
	const miniCartTotals = JSON.parse( rawMiniCartTotals );
	const currency = getCurrencyFromPriceResponse( miniCartTotals.totals );
	const formattedPrice = formatPrice(
		miniCartTotals.totals.total_price,
		currency
	);
	return [ formattedPrice, miniCartTotals.itemsCount ] as [ string, number ];
};

export const getMiniCartTotalsFromServer = async (): Promise<
	[ string, number ] | undefined
> => {
	return fetch( '/wp-json/wc/store/v1/cart/' )
		.then( ( response ) => {
			// Check if the response was successful.
			if ( ! response.ok ) {
				throw new Error();
			}

			return response.json();
		} )
		.then( ( data: CartResponse ) => {
			const currency = getCurrencyFromPriceResponse( data.totals );
			const formattedPrice = formatPrice(
				data.totals.total_price,
				currency
			);
			// Save server data to local storage, so we can re-fetch it faster
			// on the next page load.
			localStorage.setItem(
				'wc-blocks_mini_cart_totals',
				JSON.stringify( {
					totals: data.totals,
					itemsCount: data.items_count,
				} )
			);
			return [ formattedPrice, data.items_count ] as [ string, number ];
		} )
		.catch( ( error ) => {
			// eslint-disable-next-line no-console
			console.error( error );
			return undefined;
		} );
};
