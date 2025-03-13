/**
 * External dependencies
 */
import { store, getElement, getContext } from '@wordpress/interactivity';

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

				yield actions.navigate( ref.href );

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
				yield actions.prefetch( ref.href );
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
				yield actions.prefetch( ref.href );
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
