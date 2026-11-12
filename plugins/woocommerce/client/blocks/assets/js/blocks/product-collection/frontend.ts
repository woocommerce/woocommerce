/**
 * External dependencies
 */
import { store, getElement, getContext } from '@wordpress/interactivity';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import {
	triggerProductListRenderedEvent,
	triggerViewedProductEvent,
} from './legacy-events';
import { CoreCollectionNames } from './types';
import './style.scss';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

export type ProductCollectionStoreContext = {
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
 * Column classes exist for 1 through 6. Keep this in sync with the carousel
 * widths in product-template/style.scss.
 */
const MAX_FIXED_CAROUSEL_COLUMNS = 6;

/**
 * Chosen carousel column count, when the block saved one.
 * Undefined keeps the responsive carousel scroll distance.
 */
function getFixedCarouselColumns(
	productTemplate: HTMLElement
): number | undefined {
	if (
		! productTemplate.classList.contains(
			'is-product-collection-layout-carousel'
		)
	) {
		return undefined;
	}

	for ( let columns = 1; columns <= MAX_FIXED_CAROUSEL_COLUMNS; columns++ ) {
		if ( productTemplate.classList.contains( `columns-${ columns }` ) ) {
			return columns;
		}
	}

	return undefined;
}

/**
 * Distance from the start of one product to the start of the next.
 */
function getProductStride( productTemplate: HTMLElement ): number | undefined {
	const product = productTemplate.querySelector( '.wc-block-product' );
	if ( ! product ) {
		return undefined;
	}

	const width = product.getBoundingClientRect().width;
	if ( ! Number.isFinite( width ) || width <= 0 ) {
		return undefined;
	}

	const gap = Number.parseFloat(
		window.getComputedStyle( productTemplate ).columnGap
	);

	return width + ( Number.isFinite( gap ) ? gap : 0 );
}

/**
 * Products fully inside the carousel. A responsive carousel uses this as the
 * page size so a narrower screen does not skip products.
 */
function getFullyVisibleProductCount( productTemplate: HTMLElement ): number {
	const templateRect = productTemplate.getBoundingClientRect();
	const products = productTemplate.querySelectorAll( '.wc-block-product' );
	let count = 0;

	products.forEach( ( product ) => {
		const rect = product.getBoundingClientRect();
		if (
			rect.width > 0 &&
			rect.left >= templateRect.left - 1 &&
			rect.right <= templateRect.right + 1
		) {
			count++;
		}
	} );

	return count;
}

/**
 * How far one press of a carousel button should scroll.
 * A chosen column count moves by that many products, or by however many are
 * currently visible when the responsive toggle is on. Otherwise it scrolls
 * 90% of the container, matching the responsive carousel.
 */
function getCarouselScrollDistance(
	productTemplate: HTMLElement,
	containerWidth: number | undefined
): number {
	const columns = getFixedCarouselColumns( productTemplate );
	const stride = columns ? getProductStride( productTemplate ) : undefined;

	if ( columns && stride ) {
		const visibleCount = productTemplate.classList.contains(
			'is-shrink-columns'
		)
			? getFullyVisibleProductCount( productTemplate )
			: columns;
		const pageSize = Math.min( columns, Math.max( 1, visibleCount ) );

		return pageSize * stride;
	}

	return containerWidth ? 0.9 * containerWidth : 400;
}

/**
 * Scrolls the carousel and updates the isDisabledPrevious and
 * isDisabledNext context values.
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

	const scrollBy = getCarouselScrollDistance(
		productTemplate,
		productCollection?.clientWidth
	);

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

				const { actions } =
					yield import( '@wordpress/interactivity-router' );

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
				const { actions } =
					yield import( '@wordpress/interactivity-router' );

				yield actions.prefetch( ref.href );
			}
		},
		*viewProduct() {
			const { collection } =
				getContext< ProductCollectionStoreContext >();

			const productId = productsState.productInContext?.id;

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
				const { actions } =
					yield import( '@wordpress/interactivity-router' );

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
	lock: universalLock,
} );
