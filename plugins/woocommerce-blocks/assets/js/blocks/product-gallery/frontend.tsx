/**
 * External dependencies
 */
import { store as interactivityApiStore } from '@woocommerce/interactivity';

interface State {
	[ key: string ]: unknown;
}

export interface ProductGalleryInteractivityApiContext {
	woocommerce: {
		selectedImage: string;
		firstMainImageId: string;
		imageId: string;
		visibleImagesIds: string[];
		dialogVisibleImagesIds: string[];
		isDialogOpen: boolean;
		productId: string;
	};
}

export interface ProductGallerySelectors {
	woocommerce: {
		isSelected: ( store: unknown ) => boolean;
		pagerDotFillOpacity: ( store: SelectorsStore ) => number;
		selectedImageIndex: ( store: SelectorsStore ) => number;
		isDialogOpen: ( store: unknown ) => boolean;
	};
}

interface Actions {
	woocommerce: {
		thumbnails: {
			handleClick: (
				context: ProductGalleryInteractivityApiContext
			) => void;
		};
		handlePreviousImageButtonClick: {
			( store: Store ): void;
		};
		handleNextImageButtonClick: {
			( store: Store ): void;
		};
		dialog: {
			handleCloseButtonClick: {
				( store: Store ): void;
			};
		};
	};
}

interface Store {
	state: State;
	context: ProductGalleryInteractivityApiContext;
	selectors: ProductGallerySelectors;
	actions: Actions;
	ref?: HTMLElement;
}

interface Event {
	keyCode: number;
}

type SelectorsStore = Pick< Store, 'context' | 'selectors' | 'ref' >;

enum Keys {
	ESC = 27,
	LEFT_ARROW = 37,
	RIGHT_ARROW = 39,
}

interactivityApiStore( {
	state: {},
	effects: {
		woocommerce: {
			watchForChangesOnAddToCartForm: ( store: Store ) => {
				const variableProductCartForm = document.querySelector(
					`form[data-product_id="${ store.context.woocommerce.productId }"]`
				);

				if ( ! variableProductCartForm ) {
					return;
				}

				const observer = new MutationObserver( function ( mutations ) {
					for ( const mutation of mutations ) {
						const mutationTarget = mutation.target as HTMLElement;
						const currentImageAttribute =
							mutationTarget.getAttribute( 'current-image' );
						if (
							mutation.type === 'attributes' &&
							currentImageAttribute &&
							store.context.woocommerce.visibleImagesIds.includes(
								currentImageAttribute
							)
						) {
							store.context.woocommerce.selectedImage =
								currentImageAttribute;
						}
					}
				} );

				observer.observe( variableProductCartForm, {
					attributes: true,
				} );

				return () => {
					observer.disconnect();
				};
			},
			keyboardAccess: ( store: Store ) => {
				const { context, actions } = store;
				let allowNavigation = true;

				const handleKeyEvents = ( event: Event ) => {
					if (
						! allowNavigation ||
						! context.woocommerce?.isDialogOpen
					) {
						return;
					}

					// Disable navigation for a brief period to prevent spamming.
					allowNavigation = false;

					requestAnimationFrame( () => {
						allowNavigation = true;
					} );

					// Check if the esc key is pressed.
					if ( event.keyCode === Keys.ESC ) {
						actions.woocommerce.dialog.handleCloseButtonClick(
							store
						);
					}

					// Check if left arrow key is pressed.
					if ( event.keyCode === Keys.LEFT_ARROW ) {
						actions.woocommerce.handlePreviousImageButtonClick(
							store
						);
					}

					// Check if right arrow key is pressed.
					if ( event.keyCode === Keys.RIGHT_ARROW ) {
						actions.woocommerce.handleNextImageButtonClick( store );
					}
				};

				document.addEventListener( 'keydown', handleKeyEvents );
			},
		},
	},
	selectors: {
		woocommerce: {
			isSelected: ( { context }: Store ) => {
				return (
					context?.woocommerce.selectedImage ===
					context?.woocommerce.imageId
				);
			},
			pagerDotFillOpacity( store: SelectorsStore ) {
				const { context } = store;

				return context?.woocommerce.selectedImage ===
					context?.woocommerce.imageId
					? 1
					: 0.2;
			},
			isDialogOpen: ( { context }: Store ) => {
				return context.woocommerce.isDialogOpen;
			},
		},
	},
	actions: {
		woocommerce: {
			thumbnails: {
				handleClick: ( { context }: Store ) => {
					context.woocommerce.selectedImage =
						context.woocommerce.imageId;
				},
			},
			dialog: {
				handleCloseButtonClick: ( { context }: Store ) => {
					context.woocommerce.isDialogOpen = false;

					// Reset the main image.
					context.woocommerce.selectedImage =
						context.woocommerce.firstMainImageId;
				},
			},
			handleSelectImage: ( { context }: Store ) => {
				context.woocommerce.selectedImage = context.woocommerce.imageId;
			},
			handleNextImageButtonClick: ( store: Store ) => {
				const { context } = store;
				const imagesIds =
					context.woocommerce[
						context.woocommerce.isDialogOpen
							? 'dialogVisibleImagesIds'
							: 'visibleImagesIds'
					];
				const selectedImageIdIndex = imagesIds.indexOf(
					context.woocommerce.selectedImage
				);
				const nextImageIndex = Math.min(
					selectedImageIdIndex + 1,
					imagesIds.length - 1
				);

				context.woocommerce.selectedImage = imagesIds[ nextImageIndex ];
			},
			handlePreviousImageButtonClick: ( store: Store ) => {
				const { context } = store;
				const imagesIds =
					context.woocommerce[
						context.woocommerce.isDialogOpen
							? 'dialogVisibleImagesIds'
							: 'visibleImagesIds'
					];
				const selectedImageIdIndex = imagesIds.indexOf(
					context.woocommerce.selectedImage
				);
				const previousImageIndex = Math.max(
					selectedImageIdIndex - 1,
					0
				);
				context.woocommerce.selectedImage =
					imagesIds[ previousImageIndex ];
			},
		},
	},
} );
