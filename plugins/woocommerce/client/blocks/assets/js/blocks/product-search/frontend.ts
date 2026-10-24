/**
 * Live results for the Product Search block (opt-in via the "Show products while
 * typing" setting).
 *
 * Enqueued only when a Product Search variation with `liveResults` enabled renders
 * (see `ProductSearch::enqueue_live_results()`). Debounces keystrokes, queries the
 * public Store API products search, and renders a listbox of matching products —
 * image, name and price — under the input. Enter still submits the normal search;
 * Escape or clicking away dismisses.
 */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import {
	formatPrice,
	getCurrencyFromPriceResponse,
} from '@woocommerce/price-format';
import type { CurrencyResponse } from '@woocommerce/types';

interface StoreProductImage {
	src: string;
	thumbnail: string;
}

interface StoreProductPrices extends CurrencyResponse {
	price: string;
	price_range: { min_amount: string; max_amount: string } | null;
}

interface StoreProduct {
	name: string;
	permalink: string;
	images: StoreProductImage[];
	prices: StoreProductPrices;
}

const MIN_CHARS = 2;
const DEBOUNCE_MS = 250;
const MAX_RESULTS = 8;
const MARKER_CLASS = 'wc-block-product-search--live';
const PANEL_CLASS = 'wc-block-product-search__live-results';

let panelId = 0;

const priceText = ( product: StoreProduct ): string => {
	const prices = product.prices;
	const currency = getCurrencyFromPriceResponse( prices );
	const format = ( raw: string ): string => formatPrice( raw, currency );
	if (
		prices.price_range &&
		prices.price_range.min_amount !== prices.price_range.max_amount
	) {
		return `${ format( prices.price_range.min_amount ) }+`;
	}
	return format( prices.price );
};

export const attach = ( block: HTMLElement ): void => {
	const input = block.querySelector< HTMLInputElement >(
		'input[type="search"], input[name="s"]'
	);
	if ( ! input || input.dataset.wcLiveSearch ) {
		return;
	}
	input.dataset.wcLiveSearch = '1';
	input.setAttribute( 'autocomplete', 'off' );

	const panel = document.createElement( 'div' );
	panel.className = PANEL_CLASS;
	panel.id = `${ PANEL_CLASS }-${ ++panelId }`;
	panel.hidden = true;
	input.setAttribute( 'role', 'combobox' );
	input.setAttribute( 'aria-autocomplete', 'list' );
	input.setAttribute( 'aria-controls', panel.id );
	input.setAttribute( 'aria-expanded', 'false' );
	if ( window.getComputedStyle( block ).position === 'static' ) {
		block.style.position = 'relative';
	}
	block.appendChild( panel );

	let timer: ReturnType< typeof setTimeout > | undefined;
	let controller: AbortController | undefined;
	let requestId = 0;
	let active = -1;

	const close = (): void => {
		// Dismissal also invalidates any in-flight request, so a late
		// response can never reopen the panel.
		controller?.abort();
		requestId++;
		panel.hidden = true;
		panel.innerHTML = '';
		input.setAttribute( 'aria-expanded', 'false' );
		input.removeAttribute( 'aria-activedescendant' );
		active = -1;
	};

	const render = ( products: StoreProduct[] ): void => {
		if ( ! products.length ) {
			close();
			return;
		}
		panel.innerHTML = '';
		const list = document.createElement( 'ul' );
		list.setAttribute( 'role', 'listbox' );
		products.forEach( ( product, index ) => {
			const item = document.createElement( 'li' );
			const link = document.createElement( 'a' );
			link.id = `${ panel.id }-option-${ index }`;
			link.setAttribute( 'role', 'option' );
			link.setAttribute( 'aria-selected', 'false' );
			link.href = product.permalink;
			const image = product.images?.[ 0 ];
			if ( image ) {
				const img = document.createElement( 'img' );
				img.src = image.thumbnail || image.src;
				img.alt = '';
				img.loading = 'lazy';
				link.appendChild( img );
			}
			const name = document.createElement( 'span' );
			name.className = `${ PANEL_CLASS }-name`;
			name.textContent = product.name;
			const price = document.createElement( 'span' );
			price.className = `${ PANEL_CLASS }-price`;
			price.textContent = priceText( product );
			link.appendChild( name );
			link.appendChild( price );
			item.appendChild( link );
			list.appendChild( item );
		} );
		panel.appendChild( list );
		panel.hidden = false;
		input.setAttribute( 'aria-expanded', 'true' );
		active = -1;
	};

	const search = (): void => {
		const query = input.value.trim();
		if ( query.length < MIN_CHARS ) {
			close();
			return;
		}
		const currentRequestId = ++requestId;
		controller = new AbortController();
		apiFetch< StoreProduct[] >( {
			path: `/wc/store/v1/products?search=${ encodeURIComponent(
				query
			) }&per_page=${ MAX_RESULTS }`,
			signal: controller.signal,
		} )
			.then( ( products ) => {
				if (
					currentRequestId !== requestId ||
					input.value.trim() !== query
				) {
					return;
				}
				render( Array.isArray( products ) ? products : [] );
			} )
			.catch( () => {
				// Aborted or failed suggestions are silence, never a shopper-facing error.
			} );
	};

	input.addEventListener( 'input', () => {
		window.clearTimeout( timer );
		close();
		timer = window.setTimeout( search, DEBOUNCE_MS );
	} );
	input.addEventListener( 'keydown', ( event: KeyboardEvent ) => {
		if ( event.key === 'Escape' ) {
			window.clearTimeout( timer );
			close();
			return;
		}
		if ( panel.hidden ) {
			return;
		}
		const links = panel.querySelectorAll< HTMLAnchorElement >( 'li a' );
		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			event.preventDefault();
			active += event.key === 'ArrowDown' ? 1 : -1;
			active = Math.max( 0, Math.min( links.length - 1, active ) );
			links.forEach( ( link, index ) => {
				link.classList.toggle( 'is-active', index === active );
				link.setAttribute(
					'aria-selected',
					index === active ? 'true' : 'false'
				);
			} );
			input.setAttribute( 'aria-activedescendant', links[ active ].id );
		} else if ( event.key === 'Enter' && active >= 0 && links[ active ] ) {
			event.preventDefault();
			window.location.href = links[ active ].href;
		}
	} );
	document.addEventListener( 'click', ( event ) => {
		if ( ! block.contains( event.target as Node ) ) {
			close();
		}
	} );
	input.addEventListener( 'blur', () => {
		window.setTimeout( () => {
			if ( ! block.contains( block.ownerDocument.activeElement ) ) {
				close();
			}
		} );
	} );
};

const boot = (): void => {
	document
		.querySelectorAll< HTMLElement >( `.${ MARKER_CLASS }` )
		.forEach( attach );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', boot );
} else {
	boot();
}
