/**
 * External dependencies
 */
import { store as interactivityStore } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import { ProductGallerySelectors } from '../../frontend';

type Context = {
	woocommerce: {
		styles:
			| {
					// eslint-disable-next-line @typescript-eslint/naming-convention
					'transform-origin': string;
					transform: string;
					transition: string;
			  }
			| undefined;
		isDialogOpen: boolean;
	};
};

type Store = {
	context: Context;
	selectors: typeof productGalleryLargeImageSelectors &
		ProductGallerySelectors;
	ref: HTMLElement;
};

const productGalleryLargeImageSelectors = {
	woocommerce: {
		productGalleryLargeImage: {
			styles: ( { context }: Store ) => {
				const { styles } = context.woocommerce;

				return Object.entries( styles ?? [] ).reduce(
					( acc, [ key, value ] ) => {
						const style = `${ key }:${ value };`;
						return acc.length > 0 ? `${ acc } ${ style }` : style;
					},
					''
				);
			},
		},
	},
};

let isDialogStatusChanged = false;

interactivityStore(
	// @ts-expect-error: Store function isn't typed.
	{
		selectors: productGalleryLargeImageSelectors,
		actions: {
			woocommerce: {
				handleMouseMove: ( {
					event,
					context,
				}: {
					event: MouseEvent;
					context: Context;
				} ) => {
					if ( ( event.target as HTMLElement ).tagName === 'IMG' ) {
						const element = event.target as HTMLElement;
						const percentageX =
							( event.offsetX / element.clientWidth ) * 100;
						const percentageY =
							( event.offsetY / element.clientHeight ) * 100;

						context.woocommerce.styles.transform = `scale(1.3)`;

						context.woocommerce.styles[
							'transform-origin'
						] = `${ percentageX }% ${ percentageY }%`;
					}
				},
				handleMouseLeave: ( { context }: { context: Context } ) => {
					context.woocommerce.styles.transform = `scale(1.0)`;
					context.woocommerce.styles[ 'transform-origin' ] = '';
				},
				handleClick: ( {
					context,
					event,
				}: {
					context: Context;
					event: Event;
				} ) => {
					if ( ( event.target as HTMLElement ).tagName === 'IMG' ) {
						context.woocommerce.isDialogOpen = true;
					}
				},
			},
		},
		effects: {
			woocommerce: {
				scrollInto: ( store: Store ) => {
					if ( ! store.selectors.woocommerce.isSelected( store ) ) {
						return;
					}

					// Scroll to the selected image with a smooth animation.
					if (
						store.context.woocommerce.isDialogOpen ===
						isDialogStatusChanged
					) {
						store.ref.scrollIntoView( {
							behavior: 'smooth',
							block: 'nearest',
							inline: 'center',
						} );
					}

					// Scroll to the selected image when the dialog is being opened without an animation.
					if (
						store.context.woocommerce.isDialogOpen &&
						store.context.woocommerce.isDialogOpen !==
							isDialogStatusChanged &&
						store.ref.closest( 'dialog' )
					) {
						store.ref.scrollIntoView( {
							behavior: 'instant',
							block: 'nearest',
							inline: 'center',
						} );

						isDialogStatusChanged =
							store.context.woocommerce.isDialogOpen;
					}

					// Scroll to the selected image when the dialog is being closed without an animation.
					if (
						! store.context.woocommerce.isDialogOpen &&
						store.context.woocommerce.isDialogOpen !==
							isDialogStatusChanged
					) {
						store.ref.scrollIntoView( {
							behavior: 'instant',
							block: 'nearest',
							inline: 'center',
						} );
						isDialogStatusChanged =
							store.context.woocommerce.isDialogOpen;
					}
				},
			},
		},
	}
);
