/**
 * External dependencies
 */
import {
	store,
	getContext as getContextFn,
	getElement,
	withScope,
	withSyncEvent,
	getConfig,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type {
	ProductGalleryContext,
	ProductGalleryConfig,
	ProductImageSet,
} from './types';
import { checkOverflow } from './utils';
import { subscribeLegacyJQueryFormVariations } from './legacy-jquery-form';
import { SELECTORS, CLASSES } from './constants';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const getContext = ( ns?: string ) =>
	getContextFn< ProductGalleryContext >( ns );

const getArrowsState = ( imageIndex: number, totalImages: number ) => ( {
	isDisabledPrevious: imageIndex === 0,
	isDisabledNext: imageIndex === totalImages - 1,
} );

const DIALOG_VIDEO_INTERSECTION_HEIGHT_RATIO = 0.25;
const pendingViewerImages = new WeakMap< HTMLElement, number >();

const getVerticalIntersectionRatio = ( rect: DOMRect, rootRect: DOMRect ) => {
	const height =
		Math.min( rect.bottom, rootRect.bottom ) -
		Math.max( rect.top, rootRect.top );
	const referenceHeight = Math.min( rect.height, rootRect.height );

	return referenceHeight ? Math.max( 0, height ) / referenceHeight : 0;
};

const isDialogVideoInView = ( video: HTMLVideoElement ) => {
	const scrollableContainer = video.closest( SELECTORS.dialogContent );

	if ( ! scrollableContainer ) {
		return false;
	}

	const videoRect = video.getBoundingClientRect();
	const containerRect = scrollableContainer.getBoundingClientRect();

	return (
		getVerticalIntersectionRatio( videoRect, containerRect ) >=
		DIALOG_VIDEO_INTERSECTION_HEIGHT_RATIO
	);
};

const isDialogVideoIntersectionInView = (
	entry: IntersectionObserverEntry
) => {
	if ( ! entry.rootBounds || ! entry.boundingClientRect.height ) {
		return false;
	}

	return (
		getVerticalIntersectionRatio(
			entry.boundingClientRect,
			entry.rootBounds
		) >= DIALOG_VIDEO_INTERSECTION_HEIGHT_RATIO
	);
};

const syncVideoPlaybackState = (
	video: HTMLVideoElement,
	shouldPlay: boolean
) => {
	if ( shouldPlay && video.paused ) {
		void video.play().catch( () => undefined );
	} else if ( ! shouldPlay && ! video.paused ) {
		video.pause();
	}
};

const syncVideoElementPlayback = (
	video: HTMLVideoElement,
	selectedImageId: number,
	isDialogOpen: boolean,
	videoLocation?: ProductGalleryContext[ 'videoLocation' ]
) => {
	const imageId = Number( video.getAttribute( 'data-image-id' ) ?? 0 );
	const shouldPlayInDialog =
		videoLocation === 'dialog' &&
		isDialogOpen &&
		isDialogVideoInView( video );
	const shouldPlayInGallery =
		videoLocation === 'gallery' &&
		selectedImageId === imageId &&
		! isDialogOpen;
	const shouldPlay = shouldPlayInDialog || shouldPlayInGallery;

	syncVideoPlaybackState( video, shouldPlay );
};

const syncScopedVideoElementPlayback = ( video: HTMLVideoElement ) => {
	const { selectedImageId, isDialogOpen, videoLocation } = getContext();
	syncVideoElementPlayback(
		video,
		selectedImageId,
		isDialogOpen,
		videoLocation
	);
};

/** Read the `products` map from the WooCommerce iAPI config (or `{}`). */
const getConfiguredProducts = () =>
	( getConfig( 'woocommerce' ) as ProductGalleryConfig )?.products || {};

const getProductImageSet = ( productId: string | number ) =>
	getConfiguredProducts()?.[ String( productId ) ];

/**
 * Find a variation by matching its featured `image_id` against the given
 * `currentImageId`. Used as a fallback when the form doesn't expose a
 * `variation_id` directly (early stages of legacy form lifecycle).
 */
const getVariationImageSetByCurrentImage = (
	productImageSet: ProductImageSet,
	currentImageId: number
) =>
	Object.values( productImageSet.variations || {} ).find(
		( variation ) => variation.image_id === currentImageId
	);

/**
 * Pick the image to surface as selected after a visible-set change.
 * Prefers the caller's request when it's still in the new set; otherwise
 * falls back to the first image, or `-1` when the set is empty.
 */
const pickSelectedImageId = (
	imageData: number[],
	requestedId: number | undefined
): number => {
	if ( requestedId !== undefined && imageData.includes( requestedId ) ) {
		return requestedId;
	}
	return imageData[ 0 ] ?? -1;
};

/** Recompute arrow disabled flags for an image set + selected slot. */
const computeArrowsState = ( imageData: number[], selectedImageId: number ) => {
	const index = imageData.indexOf( selectedImageId );
	if ( index < 0 ) {
		return { isDisabledPrevious: true, isDisabledNext: true };
	}
	return getArrowsState( index, imageData.length );
};

/** Update the selected image and arrow states without scrolling. */
const updateSelectedImage = ( imageId: number ) => {
	const context = getContext();
	Object.assign( context, {
		selectedImageId: imageId,
		...computeArrowsState( context.imageData, imageId ),
	} );
};

/** Scroll both the large viewer and the thumbnail strip to the given image. */
const scrollImageEverywhereIntoView = (
	imageId: number,
	behavior: ScrollBehavior = 'smooth'
) => {
	scrollImageIntoView( imageId, behavior );
	scrollThumbnailIntoView( imageId );
};

/**
 * Mutate the gallery's reactive context to reflect a new visible image
 * set. Empty input restores the parent product's gallery from the iAPI
 * config. Also recomputes arrow states and scrolls the active slot into
 * view.
 */
const updateVisibleImageSet = (
	imageIds: number[],
	selectedImageId?: number
) => {
	const context = getContext();
	const nextImageData = imageIds.length
		? imageIds
		: getProductImageSet( context.productId )?.image_ids || [];
	const nextSelectedImageId = pickSelectedImageId(
		nextImageData,
		selectedImageId
	);
	context.imageData = nextImageData;
	context.hideNextPreviousButtons = nextImageData.length <= 1;
	updateSelectedImage( nextSelectedImageId );

	if ( nextSelectedImageId === -1 ) {
		return;
	}

	scrollImageEverywhereIntoView( nextSelectedImageId, 'instant' );
};

/**
 * Toggle the `hidden` attribute on the closest gallery wrapper based on
 * whether the element's `data-image-id` is in the current `imageData`.
 * Bound via `data-wp-watch` so it re-runs reactively on context change.
 */
const toggleImageVisibility = ( element: HTMLElement ) => {
	const imageIdValue = element.getAttribute( 'data-image-id' );
	if ( ! imageIdValue ) {
		return;
	}

	const imageId = Number.parseInt( imageIdValue, 10 );
	const { imageData } = getContext();
	const visibleIndex = imageData.indexOf( imageId );
	const isVisible = visibleIndex >= 0;
	const closestWrapper = element.closest(
		`${ SELECTORS.largeImageWrapper }, ${ SELECTORS.thumbnail }`
	) as HTMLElement | null;
	const visibilityTarget = closestWrapper || element;

	visibilityTarget.hidden = ! isVisible;
	visibilityTarget.style.order = isVisible ? String( visibleIndex ) : '';
	element.setAttribute( 'aria-hidden', isVisible ? 'false' : 'true' );
};

/**
 * Apply the active-thumbnail class and tabIndex when the element's
 * image is both visible and the currently selected image; otherwise
 * remove them.
 */
const toggleActiveThumbnailAttributes = ( element: HTMLElement ) => {
	const imageIdValue = element.getAttribute( 'data-image-id' );
	if ( ! imageIdValue ) {
		return;
	}

	const { imageData, selectedImageId } = getContext();
	const imageId = Number.parseInt( imageIdValue, 10 );
	const isVisible = imageData.includes( imageId );

	if ( isVisible && selectedImageId === imageId ) {
		element.classList.add( CLASSES.activeThumbnail );
		element.setAttribute( 'tabIndex', '0' );
		return;
	}

	element.classList.remove( CLASSES.activeThumbnail );
	element.setAttribute( 'tabIndex', '-1' );
};

const scrollImageIntoView = (
	imageId: number,
	behavior: ScrollBehavior = 'smooth'
) => {
	if ( ! imageId ) {
		return;
	}

	const element = getElement()?.ref as HTMLElement;
	if ( ! element ) {
		return;
	}

	const galleryContainer = element.closest( SELECTORS.galleryContainer );
	if ( ! galleryContainer ) {
		return;
	}

	const scrollableContainer = galleryContainer.querySelector(
		SELECTORS.largeImageContainer
	) as HTMLElement | null;
	if ( ! scrollableContainer ) {
		return;
	}

	const { imageData } = getContext();
	const imageIndex = imageData.indexOf( imageId );
	if ( imageIndex < 0 ) {
		return;
	}

	const direction =
		getComputedStyle( scrollableContainer ).direction === 'rtl' ? -1 : 1;
	const left = imageIndex * scrollableContainer.clientWidth * direction;
	if ( Math.abs( scrollableContainer.scrollLeft - left ) > 1 ) {
		pendingViewerImages.set( scrollableContainer, imageId );
	} else {
		pendingViewerImages.delete( scrollableContainer );
	}

	scrollableContainer.scrollTo( {
		left,
		behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' )
			.matches
			? 'instant'
			: behavior,
	} );
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
	const galleryContainer = element.closest( SELECTORS.galleryContainer );

	if ( ! galleryContainer ) {
		return;
	}

	const thumbnailElement = galleryContainer.querySelector(
		`${ SELECTORS.thumbnail } ${ SELECTORS.elementByImageId( imageId ) }`
	);

	if ( ! thumbnailElement ) {
		return;
	}

	// Find the thumbnail scrollable container
	const scrollContainer = thumbnailElement.closest(
		SELECTORS.thumbnailsScrollable
	);

	if ( ! scrollContainer ) {
		return;
	}

	const thumbnail = thumbnailElement.closest( SELECTORS.thumbnail );

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

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

const lastSeenVariationId = new Map< string, number | null | undefined >();

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
			if ( newImageIndex < 0 || newImageIndex >= imageData.length ) {
				return;
			}

			const imageId = imageData[ newImageIndex ];
			updateSelectedImage( imageId );

			if ( imageId !== -1 ) {
				scrollImageIntoView( imageId );
				scrollThumbnailIntoView( imageId );
			}
		},
		setImageData: ( imageIds: number[], selectedImageId?: number ) => {
			updateVisibleImageSet( imageIds, selectedImageId );
		},
		resetImageData: () => {
			updateVisibleImageSet( [] );
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
			if ( Number.isNaN( imageId ) ) {
				return;
			}
			updateSelectedImage( imageId );

			scrollImageIntoView( imageId );
			scrollThumbnailIntoView( imageId );
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
		onViewerImageKeyDown: withSyncEvent( ( event: KeyboardEvent ) => {
			if ( event.key === 'Enter' || event.key === ' ' ) {
				if ( event.key === ' ' ) {
					event.preventDefault();
				}
				actions.openDialog();
			}

			if ( event.key === 'ArrowRight' ) {
				event.preventDefault();
				actions.selectNextImage();
			}

			if ( event.key === 'ArrowLeft' ) {
				event.preventDefault();
				actions.selectPreviousImage();
			}
		} ),
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
		openDialog: withSyncEvent( ( event?: Event ) => {
			event?.preventDefault();
			const context = getContext();
			context.isDialogOpen = true;
			document.body.classList.add( CLASSES.dialogOpenBody );
		} ),
		closeDialog: () => {
			const context = getContext();
			context.isDialogOpen = false;
			document.body.classList.remove( CLASSES.dialogOpenBody );
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
					SELECTORS.galleryContainer
				);
				if ( galleryContainer ) {
					const selectedImage = galleryContainer.querySelector(
						SELECTORS.elementByImageId( selectedImageId )
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
		/**
		 * Keep thumbnails and arrows in sync with the visible slide:
		 * - Update thumbnail selection during native swipes and trackpad scrolling.
		 * - Keep the thumbnail clicked by the user selected unless interrupted by another user input.
		 * - Reset the selected image's alignment after variation or gallery width changes.
		 */
		watchViewerScroll: () => {
			const container = getElement()?.ref;
			if ( ! ( container instanceof HTMLElement ) ) {
				return;
			}

			// Reset alignment after variation changes, once slide visibility and order have updated.
			const { imageData } = getContext();
			let frame = 0;
			const alignSelectedImage = withScope( () => {
				scrollImageIntoView( getContext().selectedImageId, 'instant' );
				frame = 0;
			} );
			const resetAlignment = () => {
				cancelAnimationFrame( frame );
				frame = requestAnimationFrame( alignSelectedImage );
			};
			// Avoid two slides qualifying when both are exactly half visible.
			const visibilityThreshold = 0.51;
			const observer = new IntersectionObserver(
				withScope( ( entries: IntersectionObserverEntry[] ) => {
					const context = getContext();
					if ( frame || context.imageData !== imageData ) {
						return;
					}

					const visibleSlide = entries.find(
						( entry ) =>
							entry.intersectionRatio >= visibilityThreshold
					);
					const image =
						visibleSlide?.target.querySelector< HTMLElement >(
							'[data-image-id]'
						);
					const imageId = Number( image?.dataset.imageId );
					const index = imageData.indexOf( imageId );
					if ( index < 0 ) {
						return;
					}

					const pendingImage = pendingViewerImages.get( container );
					// Keep the clicked thumbnail selected while scrolling past intermediate images.
					if (
						pendingImage !== undefined &&
						imageId !== pendingImage
					) {
						return;
					}
					pendingViewerImages.delete( container );
					if ( imageId === context.selectedImageId ) {
						return;
					}

					updateSelectedImage( imageId );
					scrollThumbnailIntoView( imageId );
				} ),
				{ root: container, threshold: visibilityThreshold }
			);
			const slides = container.querySelectorAll(
				SELECTORS.largeImageWrapper
			);
			const observeSlides = () =>
				slides.forEach( ( slide ) => observer.observe( slide ) );
			observeSlides();
			const onUserInput = () => {
				if ( pendingViewerImages.delete( container ) ) {
					// The visible slide may have crossed the threshold before the interruption.
					observer.disconnect();
					observeSlides();
				}
			};
			for ( const event of [ 'pointerdown', 'wheel', 'keydown' ] ) {
				container.addEventListener( event, onUserInput, {
					capture: true,
					passive: true,
				} );
			}

			let width = container.clientWidth;
			const resizeObserver = new ResizeObserver( () => {
				if ( width !== container.clientWidth ) {
					width = container.clientWidth;
					resetAlignment();
				}
			} );
			resizeObserver.observe( container );
			resetAlignment();

			return () => {
				observer.disconnect();
				for ( const event of [ 'pointerdown', 'wheel', 'keydown' ] ) {
					container.removeEventListener( event, onUserInput, true );
				}
				pendingViewerImages.delete( container );
				resizeObserver.disconnect();
				cancelAnimationFrame( frame );
			};
		},
		/**
		 * Sync the gallery to the blockified Add to Cart + Options block's
		 * variation state. Bound via `data-wp-watch`, so it re-runs whenever
		 * `productsState.variationId` changes.
		 */
		listenToProductDataChanges: () => {
			const context = getContext();
			const variationId = productsState.variationId;
			const prevVariationId = lastSeenVariationId.get(
				context.productId
			);

			if ( prevVariationId === variationId ) {
				return;
			}

			if ( prevVariationId === undefined && ! variationId ) {
				lastSeenVariationId.set( context.productId, variationId );
				return;
			}

			lastSeenVariationId.set( context.productId, variationId );

			const product = productsState.mainProductInContext;
			if ( ! product ) {
				return;
			}

			const productImageSet = getProductImageSet( product.id );
			if ( ! productImageSet ) {
				return;
			}

			if ( ! variationId ) {
				actions.resetImageData();
				return;
			}

			const variationImageSet =
				productImageSet.variations?.[ variationId ];

			if ( variationImageSet?.image_ids?.length ) {
				actions.setImageData(
					variationImageSet.image_ids,
					variationImageSet.image_id
				);
				return;
			}

			actions.resetImageData();
		},
		/**
		 * Subscribe the gallery to the legacy classic Add to Cart form's
		 * variation events. Prefers jQuery `found_variation` / `reset_data`
		 * when jQuery is present; falls back to a MutationObserver on the
		 * form's `current-image` attribute. Returns a teardown function.
		 */
		watchForChangesOnAddToCartForm: () => {
			const context = getContext();
			const $form = document.querySelector(
				SELECTORS.cartFormForProduct( context.productId )
			) as HTMLElement | null;

			if ( ! $form ) {
				return;
			}

			const productImageSet = getProductImageSet( context.productId );
			const syncFormVariationGallery = withScope(
				( variationId?: number, featuredImageId?: number ) => {
					if ( ! productImageSet ) {
						actions.resetImageData();
						return;
					}

					const $variationIdInput = $form.querySelector(
						SELECTORS.legacyVariationIdInput
					) as HTMLInputElement | null;
					const hasVariationIdInput = !! $variationIdInput;
					const currentVariationId =
						variationId ??
						Number.parseInt( $variationIdInput?.value || '0', 10 );

					// When the form exposes a variation_id input but it's empty,
					// the merchant cleared the variation — restore the parent
					// gallery instead of guessing from `current-image`.
					if ( hasVariationIdInput && ! currentVariationId ) {
						actions.resetImageData();
						return;
					}

					const currentImageId =
						featuredImageId ??
						Number.parseInt(
							$form.getAttribute( 'current-image' ) || '0',
							10
						);
					const variationImageSet = currentVariationId
						? productImageSet.variations?.[ currentVariationId ]
						: getVariationImageSetByCurrentImage(
								productImageSet,
								currentImageId
						  );

					if ( variationImageSet?.image_ids?.length ) {
						actions.setImageData(
							variationImageSet.image_ids,
							currentImageId || variationImageSet.image_id
						);
						return;
					}

					actions.resetImageData();
				}
			);

			const teardownJQuery = subscribeLegacyJQueryFormVariations( $form, {
				// `found_variation` fires before the classic form updates its DOM.
				// Pass its fresh IDs into the config-based gallery sync.
				onVariationFound: ( variationId, featuredImageId ) =>
					syncFormVariationGallery( variationId, featuredImageId ),
				onVariationReset: () => actions.resetImageData(),
			} );

			if ( teardownJQuery ) {
				syncFormVariationGallery();
				return teardownJQuery;
			}

			// MutationObserver fallback for environments without jQuery.
			const observer = new MutationObserver(
				withScope( () => syncFormVariationGallery() )
			);
			const $clearVariationsLink = $form.querySelector(
				SELECTORS.legacyResetVariations
			);
			const syncOnChange = withScope( () => syncFormVariationGallery() );
			const resetGallery = withScope( () => actions.resetImageData() );

			observer.observe( $form, {
				attributes: true,
				attributeFilter: [ 'current-image' ],
			} );
			$form.addEventListener( 'change', syncOnChange );
			$clearVariationsLink?.addEventListener( 'click', resetGallery );

			syncFormVariationGallery();

			return () => {
				observer.disconnect();
				$form.removeEventListener( 'change', syncOnChange );
				$clearVariationsLink?.removeEventListener(
					'click',
					resetGallery
				);
			};
		},
		/** When the dialog opens, focus it and center the active image vertically. */
		dialogStateChange: () => {
			const { selectedImageId, isDialogOpen } = getContext();
			const { ref: dialogRef } = getElement() || {};

			if ( ! ( dialogRef instanceof HTMLElement ) ) {
				return;
			}

			if ( isDialogOpen ) {
				dialogRef.focus();
				const selectedImage = dialogRef.querySelector(
					SELECTORS.elementByImageId( selectedImageId )
				);

				if (
					selectedImage instanceof HTMLElement &&
					selectedImage.parentNode instanceof HTMLElement
				) {
					// We're doing this manually because scrollIntoView caused layout shifts resulting in buggy
					// dialog layout.
					selectedImage.parentNode.scrollTop =
						selectedImage.offsetTop +
						selectedImage.offsetHeight / 2 -
						dialogRef.offsetHeight / 2 -
						32; // Arbitrary value for the header height.
				}
			}
		},
		initDialogVideoPlayback: () => {
			const element = getElement()?.ref;

			if ( ! ( element instanceof HTMLVideoElement ) ) {
				return;
			}

			const scrollableContainer = element.closest(
				SELECTORS.dialogContent
			);

			if ( ! scrollableContainer || ! window.IntersectionObserver ) {
				return () => element.pause();
			}

			const observer = new IntersectionObserver(
				( entries: IntersectionObserverEntry[] ) => {
					entries.forEach( ( entry ) => {
						if ( entry.target !== element ) {
							return;
						}

						const isDialogOpen = document.body.classList.contains(
							CLASSES.dialogOpenBody
						);
						const shouldPlay =
							isDialogOpen &&
							isDialogVideoIntersectionInView( entry );

						syncVideoPlaybackState( element, shouldPlay );
					} );
				},
				{
					root: scrollableContainer,
					threshold: [ 0, DIALOG_VIDEO_INTERSECTION_HEIGHT_RATIO, 1 ],
				}
			);

			observer.observe( element );

			return () => {
				observer.disconnect();
				element.pause();
			};
		},
		syncVideoPlayback: () => {
			const element = getElement()?.ref;

			if ( ! ( element instanceof HTMLVideoElement ) ) {
				return;
			}

			toggleImageVisibility( element );
			syncScopedVideoElementPlayback( element );
		},
		/** Per-image `data-wp-watch` callback that toggles visibility from `imageData`. */
		toggleImageVisibility: () => {
			const element = getElement()?.ref as HTMLElement;
			if ( ! element ) {
				return false;
			}

			toggleImageVisibility( element );
		},
		/** Per-thumbnail callback that updates both visibility and the active-state class. */
		syncThumbnailState: () => {
			const element = getElement()?.ref as HTMLElement;
			if ( ! element ) {
				return false;
			}

			toggleImageVisibility( element );
			toggleActiveThumbnailAttributes( element );
		},
		/** Set up a ResizeObserver on the thumbnails strip so overflow flags stay in sync. */
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
