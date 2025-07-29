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
	// Next/Previous Buttons block context
	isDisabledPrevious: boolean;
	isDisabledNext: boolean;
	ariaLabelPrevious: string;
	ariaLabelNext: string;
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

/**
 * Scrolls the carousel by 90% of the container width.
 *
 * @param direction - The direction to scroll.
 * @returns The new scroll position.
 */
const scrollCarousel = (
	direction: 'left' | 'right'
): {
	isDisabledPrevious: boolean;
	isDisabledNext: boolean;
} => {
	const { ref } = getElement();

	const productCollection = ref?.closest(
		'.wp-block-woocommerce-product-collection'
	);
	const productTemplate = productCollection?.querySelector(
		'.wc-block-product-template'
	);

	if ( ! productTemplate ) {
		return {
			isDisabledPrevious: true,
			isDisabledNext: true,
		};
	}

	const SCROLL_OFFSET = 5;
	const productCollectionWidth = productCollection?.clientWidth;
	// Arbitrary value to scroll the carousel by 90% of the container width.
	const scrollBy = productCollectionWidth
		? 0.9 * productCollectionWidth
		: 400;

	productTemplate?.scrollBy( {
		left: direction === 'left' ? -scrollBy : scrollBy,
		behavior: 'smooth',
	} );

	const { scrollLeft, scrollWidth, clientWidth } = productTemplate;
	// scrollBy doesn't return the final position, so we need to calculate it.
	const finalPosition =
		direction === 'left' ? scrollLeft - scrollBy : scrollLeft + scrollBy;

	return {
		isDisabledPrevious: finalPosition < SCROLL_OFFSET,
		isDisabledNext:
			finalPosition >= scrollWidth - clientWidth - SCROLL_OFFSET,
	};
};

const onKeyDown = ( event: KeyboardEvent ) => {
	if ( event.code === 'ArrowRight' ) {
		event.preventDefault();
		const context = getContext< ProductCollectionStoreContext >();
		const { isDisabledPrevious, isDisabledNext } =
			scrollCarousel( 'right' );

		context.isDisabledPrevious = isDisabledPrevious;
		context.isDisabledNext = isDisabledNext;
	}

	if ( event.code === 'ArrowLeft' ) {
		event.preventDefault();
		const context = getContext< ProductCollectionStoreContext >();
		const { isDisabledPrevious, isDisabledNext } = scrollCarousel( 'left' );

		context.isDisabledPrevious = isDisabledPrevious;
		context.isDisabledNext = isDisabledNext;
	}
};

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
		// Next/Previous Buttons block actions
		onClickPrevious: () => {
			const context = getContext< ProductCollectionStoreContext >();
			const { isDisabledPrevious, isDisabledNext } =
				scrollCarousel( 'left' );

			context.isDisabledPrevious = isDisabledPrevious;
			context.isDisabledNext = isDisabledNext;
		},
		onClickNext: () => {
			const context = getContext< ProductCollectionStoreContext >();
			const { isDisabledPrevious, isDisabledNext } =
				scrollCarousel( 'right' );

			context.isDisabledPrevious = isDisabledPrevious;
			context.isDisabledNext = isDisabledNext;
		},
		onKeyDownPrevious: ( event: KeyboardEvent ) => {
			onKeyDown( event );
		},
		onKeyDownNext: ( event: KeyboardEvent ) => {
			onKeyDown( event );
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

store( 'woocommerce/product-collection', productCollectionStore, {
	lock: true,
} );
