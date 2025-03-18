/**
 * External dependencies
 */
import {
	store,
	getContext as getContextFn,
	getElement,
} from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { ProductGalleryContext } from './types';

const getContext = ( ns?: string ) =>
	getContextFn< ProductGalleryContext >( ns );

const getArrowsState = ( imageNumber: number, totalImages: number ) => ( {
	// One-based index so it ranges from 1 to imagesIds.length.
	disableLeft: imageNumber === 1,
	disableRight: imageNumber === totalImages,
} );

const productGallery = {
	state: {
		get isSelected() {
			const { selectedImageNumber, imageIds, imageId } = getContext();
			return selectedImageNumber === imageIds.indexOf( imageId ) + 1;
		},
		get imageIndex(): number {
			const { imageIds, imageId } = getContext();
			return imageIds.indexOf( imageId );
		},
		get thumbnailTabIndex(): string {
			return state.isSelected ? '0' : '-1';
		},
	},
	actions: {
		selectImage: ( newImageNumber: number ) => {
			const context = getContext();

			const { disableLeft, disableRight } = getArrowsState(
				newImageNumber,
				context.imageIds.length
			);

			context.selectedImageNumber = newImageNumber;
			context.disableLeft = disableLeft;
			context.disableRight = disableRight;
		},
		selectCurrentImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}
			const newImageNumber = state.imageIndex + 1;
			actions.selectImage( newImageNumber );
		},
		selectNextImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}
			const { selectedImageNumber, imageIds } = getContext();
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
			const { selectedImageNumber } = getContext();
			const newImageNumber = Math.max( 1, selectedImageNumber - 1 );
			actions.selectImage( newImageNumber );
		},
		onSelectedLargeImageKeyDown: ( event: KeyboardEvent ) => {
			if (
				( state.isSelected && event.code === 'Enter' ) ||
				event.code === 'Space' ||
				event.code === 'NumpadEnter'
			) {
				if ( event.code === 'Space' ) {
					event.preventDefault();
				}
				actions.openDialog();
			}
		},
		onViewAllImagesKeyDown: ( event: KeyboardEvent ) => {
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
				context.selectedImageNumber = newImageNumber;
				context.disableLeft = disableLeft;
				context.disableRight = disableRight;
			};

			const selectFirstImage = () => selectImage( 1 );

			const observer = new MutationObserver( function ( mutations ) {
				for ( const mutation of mutations ) {
					const mutationTarget = mutation.target as HTMLElement;
					const currentImageAttribute =
						mutationTarget.getAttribute( 'current-image' );
					if (
						mutation.type === 'attributes' &&
						currentImageAttribute &&
						context.imageIds.includes( currentImageAttribute )
					) {
						const nextImageNumber =
							context.imageIds.indexOf( currentImageAttribute ) +
							1;

						selectImage( nextImageNumber );
					} else {
						selectFirstImage();
					}
				}
			} );

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
			const { selectedImageNumber, isDialogOpen } = getContext();
			const { ref: dialogRef } = getElement() || {};

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

const { state, actions } = store(
	'woocommerce/product-gallery',
	productGallery,
	{ lock: true }
);

export type Store = typeof productGallery;

export type ProductGallery = typeof productGallery;
