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
	hideNextPreviousButtons: boolean;
	isDisabledPrevious: boolean;
	isDisabledNext: boolean;
	ariaLabelPrevious: string;
	ariaLabelNext: string;
};

// @wordpress/i18n is not available on the frontend.
function isRTL(): boolean {
	return document.documentElement?.dir === 'rtl';
}

function isValidLink( ref: HTMLElement | null ): ref is HTMLAnchorElement {
	return (
		ref !== null &&
		ref instanceof window.HTMLAnchorElement &&
		!! ref.href &&
		( ! ref.target || ref.target === '_self' ) &&
		ref.origin === window.location.origin
	);
}

const checkIfButtonsDisabled = (
	productTemplate: HTMLElement | null,
	currentScroll: number
): {
	isDisabledPrevious: boolean;
	isDisabledNext: boolean;
} => {
	if ( ! productTemplate ) {
		return {
			isDisabledPrevious: true,
			isDisabledNext: true,
		};
	}

	const SCROLL_OFFSET = 5;
	const { scrollWidth, clientWidth } = productTemplate;

	if ( isRTL() ) {
		return {
			isDisabledPrevious: currentScroll > -SCROLL_OFFSET,
			isDisabledNext:
				currentScroll <= clientWidth - scrollWidth + SCROLL_OFFSET,
		};
	}

	return {
		isDisabledPrevious: currentScroll < SCROLL_OFFSET,
		isDisabledNext:
			currentScroll >= scrollWidth - clientWidth - SCROLL_OFFSET,
	};
};

/**
 * Scrolls the carousel by 90% of the container width and updates
 * the isDisabledPrevious and isDisabledNext context values.
 *
 * @param direction - The direction to scroll.
 */
const scrollCarousel = ( direction: 'left' | 'right' ) => {
	const { ref } = getElement();

	const productCollection = ref?.closest(
		'.wp-block-woocommerce-product-collection'
	);
	const productTemplate = productCollection?.querySelector(
		'.wc-block-product-template'
	) as HTMLElement;

	if ( ! productTemplate ) {
		return;
	}

	const productCollectionWidth = productCollection?.clientWidth;
	// Arbitrary value to scroll the carousel by 90% of the container width.
	const scrollBy = productCollectionWidth
		? 0.9 * productCollectionWidth
		: 400;

	const multiplier = isRTL() ? -1 : 1;

	productTemplate?.scrollBy( {
		left: multiplier * ( direction === 'left' ? -scrollBy : scrollBy ),
		behavior: 'smooth',
	} );

	const context = getContext< ProductCollectionStoreContext >();
	const { scrollLeft } = productTemplate;
	// scrollBy doesn't return the final position, so we need to calculate it.
	const finalPosition =
		direction === 'left'
			? scrollLeft - multiplier * scrollBy
			: scrollLeft + multiplier * scrollBy;

	const { isDisabledPrevious, isDisabledNext } = checkIfButtonsDisabled(
		productTemplate,
		finalPosition
	);

	context.isDisabledPrevious = isDisabledPrevious;
	context.isDisabledNext = isDisabledNext;
};

const onKeyDown = ( event: KeyboardEvent ) => {
	if ( event.code === 'ArrowRight' ) {
		event.preventDefault();
		scrollCarousel( 'right' );
	}

	if ( event.code === 'ArrowLeft' ) {
		event.preventDefault();
		scrollCarousel( 'left' );
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
			scrollCarousel( 'left' );
		},
		onClickNext: () => {
			scrollCarousel( 'right' );
		},
		onKeyDownPrevious: ( event: KeyboardEvent ) => {
			onKeyDown( event );
		},
		onKeyDownNext: ( event: KeyboardEvent ) => {
			onKeyDown( event );
		},
		watchScroll: () => {
			const context = getContext< ProductCollectionStoreContext >();
			const { ref } = getElement();
			if ( ref ) {
				const { isDisabledPrevious, isDisabledNext } =
					checkIfButtonsDisabled( ref, ref.scrollLeft );

				context.isDisabledPrevious = isDisabledPrevious;
				context.isDisabledNext = isDisabledNext;
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
		initResizeObserver: () => {
			const scrollableElement = getElement()?.ref;
			if ( ! scrollableElement ) {
				return;
			}

			const context = getContext< ProductCollectionStoreContext >();
			const observer = new ResizeObserver( () => {
				const hasOverflowX =
					scrollableElement.scrollWidth >
					scrollableElement.clientWidth;
				context.hideNextPreviousButtons = ! hasOverflowX;
			} );

			observer.observe( scrollableElement );
		},
	},
};

store( 'woocommerce/product-collection', productCollectionStore, {
	lock: true,
} );
