/**
 * External dependencies
 */
import {
	store,
	getContext as getContextFn,
	getElement,
	withScope,
} from '@wordpress/interactivity';
import type { StorePart } from '@woocommerce/utils';

export interface ProductGalleryContext {
	selectedImageId: string;
	imageIds: string[];
	isDialogOpen: boolean;
	productId: string;
	disableLeft: boolean;
	disableRight: boolean;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
	userHasInteracted: boolean;
	imageData: {
		id: string;
		src: string;
		srcSet: string;
		sizes: string;
	}[];
}

const getContext = ( ns?: string ) =>
	getContextFn< ProductGalleryContext >( ns );

type Store = typeof productGallery & StorePart< ProductGallery >;
const { actions } = store< Store >( 'woocommerce/product-gallery' );

const getArrowsState = ( imageNumber: number, totalImages: number ) => ( {
	// One-based index so it ranges from 1 to imagesIds.length.
	disableLeft: imageNumber === 1,
	disableRight: imageNumber === totalImages,
} );

/**
 * Scrolls an image into view.
 *
 * @param {string} imageId - The ID of the image to scroll into view.
 */
const scrollImageIntoView = ( imageId: string ) => {
	if ( ! imageId ) {
		return;
	}
	const imageElement = document.querySelector(
		`.wp-block-woocommerce-product-gallery-large-image img[data-image-id="${ imageId }"]`
	);
	if ( imageElement ) {
		imageElement.scrollIntoView( {
			behavior: 'smooth',
			block: 'nearest',
			inline: 'center',
		} );
	}
};

/**
 * Gets the number of the active image.
 *
 * @param {string[]} imageIds        - The IDs of the images.
 * @param {string}   selectedImageId - The ID of the selected image.
 * @return {number} The number of the active image.
 */
const getSelectedImageNumber = (
	imageIds: string[],
	selectedImageId: string
) => imageIds.indexOf( selectedImageId ) + 1;

const productGallery = {
	state: {
		/**
		 * The number of the active image. Not to be confused with the index of the active image in the imageIds array.
		 *
		 * @return {number} The number of the active image.
		 */
		get selectedImageNumber(): number {
			const { imageIds, selectedImageId } = getContext();
			return getSelectedImageNumber( imageIds, selectedImageId );
		},
		/**
		 * The index of the active image in the imageIds array.
		 *
		 * @return {number} The index of the active image.
		 */
		get imageIndex(): number {
			const { imageIds, selectedImageId } = getContext();
			return imageIds.indexOf( selectedImageId );
		},
		/**
		 * The processed image data.
		 *
		 * @return {Object} The processed image data.
		 */
		get processedImageData() {
			// The thumbnail block preloads all required images into cache. Without thumbnails, only the first two images load initially,
			// as users navigate one at a time, with more loading on interaction. If thumbnails later use smaller, separate images, this
			// logic will need adjustment, as users could jump to an unloaded image by clicking a thumbnail.
			const { imageData, userHasInteracted, imageIds, selectedImageId } =
				getContext();

			const selectedImageNumber = getSelectedImageNumber(
				imageIds,
				selectedImageId
			);

			return imageData.map( ( image, index ) => {
				const isActive = selectedImageNumber === index + 1;
				const tabIndex = isActive ? '0' : '-1';

				if ( ! userHasInteracted && index >= 2 ) {
					// Return a copy with empty src and srcSet for images beyond the first two
					return {
						...image,
						isActive,
						tabIndex,
						src: '',
						srcSet: '',
					};
				}
				return {
					...image,
					isActive,
					tabIndex,
				};
			} );
		},
	},
	actions: {
		userHasInteracted: () => {
			const context = getContext();
			context.userHasInteracted = true;
		},
		selectImage: ( newImageNumber: number ) => {
			const context = getContext();

			const { disableLeft, disableRight } = getArrowsState(
				newImageNumber,
				context.imageIds.length
			);

			actions.userHasInteracted();
			context.disableLeft = disableLeft;
			context.disableRight = disableRight;

			const { imageData } = context;
			const imageIndex = newImageNumber - 1;
			const imageId = imageData[ imageIndex ].id;
			context.selectedImageId = imageId;
			if ( imageIndex !== -1 ) {
				scrollImageIntoView( imageId );
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
			const imageId = element.getAttribute( 'data-image-id' );
			if ( ! imageId ) {
				return;
			}
			const context = getContext();
			const newImageNumber = context.imageIds.indexOf( imageId ) + 1;
			actions.selectImage( newImageNumber );
		},
		selectNextImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}
			const { imageIds, selectedImageId } = getContext();
			const selectedImageNumber = getSelectedImageNumber(
				imageIds,
				selectedImageId
			);

			const newImageNumber = Math.min(
				imageIds.length,
				selectedImageNumber + 1
			);
			actions.selectImage( newImageNumber );
		},
		selectPreviousImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}

			const { imageIds, selectedImageId } = getContext();
			const selectedImageNumber = getSelectedImageNumber(
				imageIds,
				selectedImageId
			);

			const newImageNumber = Math.max( 1, selectedImageNumber - 1 );
			actions.selectImage( newImageNumber );
		},
		onSelectedLargeImageKeyDown: ( event: KeyboardEvent ) => {
			if (
				event.code === 'Enter' ||
				event.code === 'Space' ||
				event.code === 'NumpadEnter'
			) {
				if ( event.code === 'Space' ) {
					event.preventDefault();
				}
				actions.openDialog();
			}

			if ( event.code === 'ArrowRight' ) {
				actions.selectNextImage();
			}

			if ( event.code === 'ArrowLeft' ) {
				actions.selectPreviousImage();
			}
		},
		onThumbnailKeyDown: ( event: KeyboardEvent ) => {
			if (
				event.code === 'Enter' ||
				event.code === 'Space' ||
				event.code === 'NumpadEnter'
			) {
				if ( event.code === 'Space' ) {
					event.preventDefault();
				}
				actions.selectCurrentImage();
			}
		},
		onDialogKeyDown: ( event: KeyboardEvent ) => {
			if ( event.code === 'Escape' ) {
				actions.closeDialog();
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
			event.preventDefault();
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
				if ( delta > 0 && ! context.disableLeft ) {
					actions.selectPreviousImage();
				} else if ( delta < 0 && ! context.disableRight ) {
					actions.selectNextImage();
				}
			}

			// Reset touch state
			context.isDragging = false;
			context.touchStartX = 0;
			context.touchCurrentX = 0;
		},
	},
	callbacks: {
		watchForChangesOnAddToCartForm: () => {
			const context = getContext();
			const variableProductCartForm = document.querySelector(
				`form[data-product_id="${ context.productId }"]`
			);

			if ( ! variableProductCartForm ) {
				return;
			}

			// TODO: Replace with an interactive block that calls `actions.selectImage`.
			// This have a diffent context in current setup.
			const selectImage = ( newImageNumber: number ) => {
				const { disableLeft, disableRight } = getArrowsState(
					newImageNumber,
					context.imageIds.length
				);
				context.selectedImageId =
					context.imageIds[ newImageNumber - 1 ];
				context.disableLeft = disableLeft;
				context.disableRight = disableRight;
				scrollImageIntoView( context.imageIds[ newImageNumber - 1 ] );
			};

			const selectFirstImage = () => selectImage( 1 );

			// Initial mutation is triggered when the page is loaded.
			// We don't want to set `userHasInteracted` to true on initial mutation
			let isInitialMutation = true;

			const observer = new MutationObserver(
				withScope( function ( mutations ) {
					for ( const mutation of mutations ) {
						if ( ! isInitialMutation ) {
							actions.userHasInteracted();
						}

						if ( isInitialMutation ) {
							isInitialMutation = false;
						}

						const mutationTarget = mutation.target as HTMLElement;
						const currentImageAttribute =
							mutationTarget.getAttribute( 'current-image' );
						if (
							mutation.type === 'attributes' &&
							currentImageAttribute &&
							context.imageIds.includes( currentImageAttribute )
						) {
							const nextImageNumber =
								context.imageIds.indexOf(
									currentImageAttribute
								) + 1;

							actions.selectImage( nextImageNumber );
						} else {
							actions.selectImage( 1 );
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
			const { imageIds, selectedImageId, isDialogOpen } = getContext();
			const { ref: dialogRef } = getElement() || {};
			const selectedImageNumber = getSelectedImageNumber(
				imageIds,
				selectedImageId
			);

			if ( isDialogOpen && dialogRef instanceof HTMLElement ) {
				dialogRef.focus();
				const selectedImage = dialogRef.querySelector(
					`[data-image-index="${ selectedImageNumber }"]`
				);

				if ( selectedImage instanceof HTMLElement ) {
					selectedImage.scrollIntoView( {
						behavior: 'auto',
						block: 'center',
					} );
					selectedImage.focus();
				}
			}
		},
	},
};

store( 'woocommerce/product-gallery', productGallery );

export type ProductGallery = typeof productGallery;
