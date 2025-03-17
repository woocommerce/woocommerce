/**
 * External dependencies
 */
import { store, getElement, getContext } from '@wordpress/interactivity';

// Todo: Remove after support for WP .6.6 is dropped.
const htmlMap = new Map< string, string >();
const data = document.getElementById( 'wp-interactivity-data' );
if ( data ) {
	const interactivityData = JSON.parse( data.textContent );
	if ( interactivityData.state?.[ 'woocommerce/product-button' ] ) {
		interactivityData.state[ 'woocommerce/product-button' ].addToCartText =
			undefined;
		data.textContent = JSON.stringify( interactivityData );
	}
}

/**
 * Internal dependencies
 */
import {
	triggerProductListRenderedEvent,
	triggerViewedProductEvent,
} from './legacy-events';
import { CoreCollectionNames } from './types';
import './style.scss';

export type ProductCollectionStoreContext = {
	// Available on the <li/> product element and deeper
	productId?: number;
	isPrefetchNextOrPreviousLink: string;
	collection: CoreCollectionNames;
};

function isValidLink( ref: HTMLElement | null ): ref is HTMLAnchorElement {
	return (
		ref !== null &&
		ref instanceof window.HTMLAnchorElement &&
		!! ref.href &&
		( ! ref.target || ref.target === '_self' ) &&
		ref.origin === window.location.origin
	);
}

function isValidEvent( event: MouseEvent ): boolean {
	return (
		event.button === 0 && // Left clicks only.
		! event.metaKey && // Open in new tab (Mac).
		! event.ctrlKey && // Open in new tab (Windows).
		! event.altKey && // Download.
		! event.shiftKey &&
		! event.defaultPrevented
	);
}

// Todo: Remove after support for WP .6.6 is dropped.
async function fetchUrlAndReplaceState( url: string ): Promise< string > {
	if ( ! htmlMap.has( url ) ) {
		const response = await window.fetch( url );
		const html = await response.text();
		const dom = new window.DOMParser().parseFromString( html, 'text/html' );
		const dataElement = dom.getElementById( 'wp-interactivity-data' );
		const interactivityData = JSON.parse( data.textContent );

		if ( interactivityData.state?.[ 'woocommerce/product-button' ] ) {
			interactivityData.state[
				'woocommerce/product-button'
			].addToCartText = undefined;
			dataElement.textContent = JSON.stringify( interactivityData );
		}

		htmlMap.set( url, dom.documentElement.outerHTML );
	}
	return htmlMap.get( url ) || '';
}

const productCollectionStore = {
	actions: {
		*navigate( event: MouseEvent ) {
			const { ref } = getElement();

			if ( isValidLink( ref ) && isValidEvent( event ) ) {
				event.preventDefault();

				const ctx = getContext< ProductCollectionStoreContext >();

				const routerRegionId = ref
					.closest( '[data-wp-router-region]' )
					?.getAttribute( 'data-wp-router-region' );

				const { actions } = yield import(
					'@wordpress/interactivity-router'
				);

				// Todo: Remove after support for WP .6.6 is dropped.
				if ( document.getElementById( 'wp-interactivity-data' ) ) {
					const html = yield fetchUrlAndReplaceState( ref.href );
					yield actions.navigate( ref.href, { html } );
				} else {
					yield actions.navigate( ref.href );
				}

				ctx.isPrefetchNextOrPreviousLink = ref.href;

				// Moves focus to the product link.
				const product: HTMLAnchorElement | null =
					document.querySelector(
						`[data-wp-router-region=${ routerRegionId }] .wc-block-product-template .wc-block-product a`
					);
				product?.focus();

				triggerProductListRenderedEvent( {
					collection: ctx.collection,
				} );
			}
		},
		/**
		 * We prefetch the next or previous button page on hover.
		 * Optimizes user experience by preloading content for faster access.
		 */
		*prefetchOnHover() {
			const { ref } = getElement();

			if ( isValidLink( ref ) ) {
				const { actions } = yield import(
					'@wordpress/interactivity-router'
				);

				// Todo: Remove after support for WP .6.6 is dropped.
				if ( document.getElementById( 'wp-interactivity-data' ) ) {
					const html = yield fetchUrlAndReplaceState( ref.href );
					yield actions.prefetch( ref.href, { html } );
				} else {
					yield actions.prefetch( ref.href );
				}
			}
		},
		*viewProduct() {
			const { collection, productId } =
				getContext< ProductCollectionStoreContext >();

			if ( productId ) {
				triggerViewedProductEvent( { collection, productId } );
			}
		},
	},
	callbacks: {
		/**
		 * Prefetches content for next or previous links after initial user interaction.
		 * Reduces perceived load times for subsequent page navigations.
		 */
		*prefetch() {
			const { ref } = getElement();
			const context = getContext< ProductCollectionStoreContext >();

			if ( isValidLink( ref ) && context.isPrefetchNextOrPreviousLink ) {
				const { actions } = yield import(
					'@wordpress/interactivity-router'
				);

				// Todo: Remove after support for WP .6.6 is dropped.
				if ( document.getElementById( 'wp-interactivity-data' ) ) {
					const html = yield fetchUrlAndReplaceState( ref.href );
					yield actions.prefetch( ref.href, { html } );
				} else {
					yield actions.prefetch( ref.href );
				}
			}
		},
		*onRender() {
			const { collection } =
				getContext< ProductCollectionStoreContext >();

			triggerProductListRenderedEvent( { collection } );
		},
	},
};

store( 'woocommerce/product-collection', productCollectionStore );
