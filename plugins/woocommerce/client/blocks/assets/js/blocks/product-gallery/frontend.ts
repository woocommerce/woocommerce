/**
 * External dependencies
 */
import {
	store,
	getContext as getContextFn,
	getElement,
	withScope,
	getConfig,
} from '@wordpress/interactivity';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';
import type { WooCommerceConfig } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type { ProductGalleryContext } from './types';
import { checkOverflow } from './utils';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const getContext = ( ns?: string ) =>
	getContextFn< ProductGalleryContext >( ns );

const getArrowsState = ( imageIndex: number, totalImages: number ) => ( {
	isDisabledPrevious: imageIndex === 0,
	isDisabledNext: imageIndex === totalImages - 1,
} );

/**
 * Scrolls the image into view for the main image.
 *
 * We use getElement to get the current element that triggered the action
 * to find the closest gallery container and scroll the image into view.
 * This is necessary because if you have two galleries on the same page with the same image IDs,
 * then we need to query the image in the correct gallery to avoid scrolling the wrong image into view.
 *
 * @param {string} imageId - The ID of the image to scroll into view.
 */
const scrollImageIntoView = ( imageId: number ) => {
	if ( ! imageId ) {
		return;
	}

	// Get the current element that triggered the action
	const element = getElement()?.ref as HTMLElement;

	if ( ! element ) {
		return;
	}

	const galleryContainer = element.closest(
		'.wp-block-woocommerce-product-gallery'
	);

	if ( ! galleryContainer ) {
		return;
	}

	// Find the scrollable container for the large image gallery
	const scrollableContainer = galleryContainer.querySelector(
		'.wc-block-product-gallery-large-image__container'
	);

	if ( ! scrollableContainer ) {
		return;
	}

	const imageElement = scrollableContainer.querySelector(
		`img[data-image-id="${ imageId }"]`
	);

	if ( imageElement ) {
		// Calculate the scroll position to center the image horizontally
		const containerRect = scrollableContainer.getBoundingClientRect();
		const imageRect = imageElement.getBoundingClientRect();

		const scrollLeft =
			scrollableContainer.scrollLeft +
			( imageRect.left - containerRect.left ) -
			( containerRect.width - imageRect.width ) / 2;

		// Use scrollTo as scrollIntoView with inline: 'center'
		// is not supported in iOS (Safari and Chrome).
		scrollableContainer.scrollTo( {
			left: scrollLeft,
			behavior: 'smooth',
		} );
	}
};

/**
 * Scrolls the thumbnail into view.
 *
 * @param {number} imageId - The ID of the thumbnail to scroll into view.
 */
const scrollThumbnailIntoView = ( imageId: number ) => {
	if ( ! imageId ) {
		return;
	}

	// Get the current element that triggered the action
	const element = getElement()?.ref as HTMLElement;

	if ( ! element ) {
		return;
	}

	// Find the closest gallery container
	const galleryContainer = element.closest(
		'.wp-block-woocommerce-product-gallery'
	);

	if ( ! galleryContainer ) {
		return;
	}

	const thumbnailElement = galleryContainer.querySelector(
		`.wc-block-product-gallery-thumbnails__thumbnail img[data-image-id="${ imageId }"]`
	);

	if ( ! thumbnailElement ) {
		return;
	}

	// Find the thumbnail scrollable container
	const scrollContainer = thumbnailElement.closest(
		'.wc-block-product-gallery-thumbnails__scrollable'
	);

	if ( ! scrollContainer ) {
		return;
	}

	const thumbnail = thumbnailElement.closest(
		'.wc-block-product-gallery-thumbnails__thumbnail'
	);

	if ( ! thumbnail ) {
		return;
	}

	// Calculate the scroll position to center the thumbnail
	const containerRect = scrollContainer.getBoundingClientRect();
	const thumbnailRect = thumbnail.getBoundingClientRect();

	const scrollTop =
		scrollContainer.scrollTop +
		( thumbnailRect.top - containerRect.top ) -
		( containerRect.height - thumbnailRect.height ) / 2;
	const scrollLeft =
		scrollContainer.scrollLeft +
		( thumbnailRect.left - containerRect.left ) -
		( containerRect.width - thumbnailRect.width ) / 2;

	// Use scrollTo to avoid scrolling the entire page which
	// happens with scrollIntoView.
	scrollContainer.scrollTo( {
		top: scrollTop,
		left: scrollLeft,
		behavior: 'smooth',
	} );
};

const { state: productDataState } = store< ProductDataStore >(
	'woocommerce/product-data',
	{},
	{ lock: universalLock }
);

const productGallery = {
	state: {
		/**
		 * The index of the active image in the imageIds array.
		 *
		 * @return {number} The index of the active image.
		 */
		get imageIndex(): number {
			const { imageData, selectedImageId } = getContext();
			return imageData.indexOf( selectedImageId );
		},
	},
	actions: {
		selectImage: ( newImageIndex: number ) => {
			const context = getContext();
			const { imageData } = context;

			const imageId = imageData[ newImageIndex ];
			const { isDisabledPrevious, isDisabledNext } = getArrowsState(
				newImageIndex,
				imageData.length
			);

			context.isDisabledPrevious = isDisabledPrevious;
			context.isDisabledNext = isDisabledNext;
			context.selectedImageId = imageId;

			if ( imageId !== -1 ) {
				scrollImageIntoView( imageId );
				scrollThumbnailIntoView( imageId );
			}
		},
		selectCurrentImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}
			const element = getElement()?.ref as HTMLElement;
			if ( ! element ) {
				return;
			}
			const imageIdValue = element.getAttribute( 'data-image-id' );
			if ( ! imageIdValue ) {
				return;
			}
			const imageId = parseInt( imageIdValue, 10 );
			const { imageData } = getContext();
			const newImageIndex = imageData.indexOf( imageId );
			actions.selectImage( newImageIndex );
		},
		selectNextImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}

			const { imageData, selectedImageId } = getContext();
			const selectedImageIndex = imageData.indexOf( selectedImageId );
			const newImageIndex = Math.min(
				imageData.length - 1,
				selectedImageIndex + 1
			);

			actions.selectImage( newImageIndex );
		},
		selectPreviousImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}

			const { imageData, selectedImageId } = getContext();
			const selectedImageIndex = imageData.indexOf( selectedImageId );
			const newImageIndex = Math.max( 0, selectedImageIndex - 1 );

			actions.selectImage( newImageIndex );
		},
		onSelectedLargeImageKeyDown: ( event: KeyboardEvent ) => {
			if ( event.key === 'Enter' || event.key === ' ' ) {
				if ( event.key === ' ' ) {
					event.preventDefault();
				}
				actions.openDialog();
			}

			if ( event.key === 'ArrowRight' ) {
				actions.selectNextImage();
			}

			if ( event.key === 'ArrowLeft' ) {
				actions.selectPreviousImage();
			}
		},
		onDialogKeyDown: ( event: KeyboardEvent ) => {
			if ( event.key === 'Escape' ) {
				actions.closeDialog();
			}

			if ( event.key === 'Tab' ) {
				const focusableElementsSelectors =
					'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])';

				const dialogPopUp = getElement()?.ref as HTMLElement;
				const focusableElements = dialogPopUp.querySelectorAll(
					focusableElementsSelectors
				);

				if ( ! focusableElements.length ) {
					return;
				}

				const firstFocusableElement =
					focusableElements[ 0 ] as HTMLElement;
				const lastFocusableElement = focusableElements[
					focusableElements.length - 1
				] as HTMLElement;

				if (
					! event.shiftKey &&
					event.target === lastFocusableElement
				) {
					event.preventDefault();
					firstFocusableElement.focus();
					return;
				}

				if (
					event.shiftKey &&
					event.target === firstFocusableElement
				) {
					event.preventDefault();
					lastFocusableElement.focus();
					return;
				}

				if ( event.target === dialogPopUp ) {
					event.preventDefault();
					firstFocusableElement.focus();
				}
			}
		},
		openDialog: () => {
			const context = getContext();
			context.isDialogOpen = true;
			document.body.classList.add(
				'wc-block-product-gallery-dialog-open'
			);
		},
		closeDialog: () => {
			const context = getContext();
			context.isDialogOpen = false;
			document.body.classList.remove(
				'wc-block-product-gallery-dialog-open'
			);
		},
		onTouchStart: ( event: TouchEvent ) => {
			const context = getContext();
			const { clientX } = event.touches[ 0 ];
			context.touchStartX = clientX;
			context.touchCurrentX = clientX;
			context.isDragging = true;
		},
		onTouchMove: ( event: TouchEvent ) => {
			const context = getContext();
			if ( ! context.isDragging ) {
				return;
			}
			const { clientX } = event.touches[ 0 ];
			context.touchCurrentX = clientX;

			// Only prevent default if there's significant horizontal movement
			const delta = clientX - context.touchStartX;
			if ( Math.abs( delta ) > 10 ) {
				event.preventDefault();
			}
		},
		onTouchEnd: () => {
			const context = getContext();
			if ( ! context.isDragging ) {
				return;
			}

			const SNAP_THRESHOLD = 0.2;
			const delta = context.touchCurrentX - context.touchStartX;
			const element = getElement()?.ref as HTMLElement;
			const imageWidth = element?.offsetWidth || 0;

			// Only trigger swipe actions if there was significant movement
			if ( Math.abs( delta ) > imageWidth * SNAP_THRESHOLD ) {
				if ( delta > 0 && ! context.isDisabledPrevious ) {
					actions.selectPreviousImage();
				} else if ( delta < 0 && ! context.isDisabledNext ) {
					actions.selectNextImage();
				}
			}

			// Reset touch state
			context.isDragging = false;
			context.touchStartX = 0;
			context.touchCurrentX = 0;
		},
		onScroll: () => {
			const scrollableElement = getElement()?.ref;
			if ( ! scrollableElement ) {
				return;
			}
			const context = getContext();
			const overflowState = checkOverflow( scrollableElement );

			context.thumbnailsOverflow = overflowState;
		},
		onArrowsKeyDown: ( event: KeyboardEvent ) => {
			if ( event.key === 'ArrowRight' ) {
				event.preventDefault();
				actions.selectNextImage();
			}

			if ( event.key === 'ArrowLeft' ) {
				event.preventDefault();
				actions.selectPreviousImage();
			}
		},
		onThumbnailsArrowsKeyDown: ( event: KeyboardEvent ) => {
			actions.onArrowsKeyDown( event );

			// Find and focus the newly selected image
			const element = getElement()?.ref as HTMLElement;
			const { selectedImageId } = getContext();

			if ( element ) {
				const galleryContainer = element.closest(
					'.wp-block-woocommerce-product-gallery'
				);
				if ( galleryContainer ) {
					const selectedImage = galleryContainer.querySelector(
						`img[data-image-id="${ selectedImageId }"]`
					) as HTMLElement;
					if ( selectedImage ) {
						selectedImage.focus( { preventScroll: true } );
					}
				}
			}
		},
		// Next/Previous Buttons block actions
		onClickPrevious: ( event?: MouseEvent ) => {
			actions.selectPreviousImage( event );
		},
		onClickNext: ( event?: MouseEvent ) => {
			actions.selectNextImage( event );
		},
		onKeyDownPrevious: ( event: KeyboardEvent ) => {
			actions.onArrowsKeyDown( event );
		},
		onKeyDownNext: ( event: KeyboardEvent ) => {
			actions.onArrowsKeyDown( event );
		},
	},
	callbacks: {
		listenToProductDataChanges: () => {
			const productId = productDataState?.productId;
			if ( ! productId ) {
				return;
			}

			const { products } = getConfig(
				'woocommerce'
			) as WooCommerceConfig;

			const productData =
				products?.[ productId ]?.variations?.[
					productDataState?.variationId || 0
				] || products?.[ productId ];

			const imageId = productData?.image_id;
			if ( ! imageId ) {
				return;
			}

			const { imageData } = getContext();
			const imageIndex = imageData.indexOf( imageId );

			if ( imageIndex >= 0 ) {
				actions.selectImage( imageIndex );
			}
		},
		watchForChangesOnAddToCartForm: () => {
			const context = getContext();
			const variableProductCartForm = document.querySelector(
				`form[data-product_id="${ context.productId }"]`
			);

			if ( ! variableProductCartForm ) {
				return;
			}

			const selectFirstImage = () =>
				withScope( () => actions.selectImage( 0 ) );

			const observer = new MutationObserver(
				withScope( function ( mutations ) {
					for ( const mutation of mutations ) {
						const { imageData } = getContext();

						const mutationTarget = mutation.target as HTMLElement;
						const currentImageAttribute =
							mutationTarget.getAttribute( 'current-image' );
						const currentImageId = currentImageAttribute
							? parseInt( currentImageAttribute, 10 )
							: null;
						if (
							mutation.type === 'attributes' &&
							currentImageId &&
							imageData.includes( currentImageId )
						) {
							const nextImageIndex =
								imageData.indexOf( currentImageId );

							actions.selectImage( nextImageIndex );
						} else {
							actions.selectImage( 0 );
						}
					}
				} )
			);

			observer.observe( variableProductCartForm, {
				attributes: true,
			} );

			const clearVariationsLink = document.querySelector(
				'.wp-block-add-to-cart-form .reset_variations'
			);

			if ( clearVariationsLink ) {
				clearVariationsLink.addEventListener(
					'click',
					selectFirstImage
				);
			}

			return () => {
				observer.disconnect();
				document.removeEventListener( 'click', selectFirstImage );
			};
		},
		dialogStateChange: () => {
			const { selectedImageId, isDialogOpen } = getContext();
			const { ref: dialogRef } = getElement() || {};

			if ( isDialogOpen && dialogRef instanceof HTMLElement ) {
				dialogRef.focus();
				const selectedImage = dialogRef.querySelector(
					`[data-image-id="${ selectedImageId }"]`
				);

				if ( selectedImage instanceof HTMLElement ) {
					selectedImage.scrollIntoView( {
						behavior: 'auto',
						block: 'center',
					} );
				}
			}
		},
		toggleActiveThumbnailAttributes: () => {
			const element = getElement()?.ref as HTMLElement;
			if ( ! element ) return false;

			const imageIdValue = element.getAttribute( 'data-image-id' );
			if ( ! imageIdValue ) return false;

			const { selectedImageId } = getContext();
			const imageId = Number( imageIdValue );

			if ( selectedImageId === imageId ) {
				element.classList.add(
					'wc-block-product-gallery-thumbnails__thumbnail__image--is-active'
				);
				element.setAttribute( 'tabIndex', '0' );
			} else {
				element.classList.remove(
					'wc-block-product-gallery-thumbnails__thumbnail__image--is-active'
				);
				element.setAttribute( 'tabIndex', '-1' );
			}
		},
		initResizeObserver: () => {
			const scrollableElement = getElement()?.ref;
			if ( ! scrollableElement ) {
				return;
			}

			const context = getContext();
			const resizeObserver = new ResizeObserver( () => {
				const overflowState = checkOverflow( scrollableElement );
				context.thumbnailsOverflow = overflowState;
			} );

			// Observe both the scrollable element and its parent for size changes
			resizeObserver.observe( scrollableElement );
			if ( scrollableElement.parentElement ) {
				resizeObserver.observe( scrollableElement.parentElement );
			}

			return () => {
				resizeObserver.disconnect();
			};
		},
		// There's this issue with the scrollbar on the thumbnails block,
		// that in certain cases thumbnails overflow slightly the container.
		// This triggers the overflow and scrollbar makes thumbnails smaller
		// so they no longer overflow resulting in a ghost scrollbar (no scroll).
		// scrollbar-gutter doesn't work well in flexbox and doesn't solve it,
		// hence programmatic solution.
		// See https://github.com/woocommerce/woocommerce/issues/59810.
		hideGhostOverflow: () => {
			const element = getElement()?.ref as HTMLElement;
			if ( ! element ) return;

			const { clientWidth, scrollWidth } = element;

			if ( clientWidth >= scrollWidth ) {
				element.style.scrollbarWidth = 'none';
			}
		},
	},
};

const { actions } = store( 'woocommerce/product-gallery', productGallery, {
	lock: true,
} );

export type Store = typeof productGallery;
