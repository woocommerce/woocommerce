/**
 * External dependencies
 */
import {
	store,
	getContext as getContextFn,
	getElement,
} from '@woocommerce/interactivity';
import { StorePart } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import type { ProductGalleryContext, ProductGallery } from '../../frontend';

type Context = {
	styles: {
		// eslint-disable-next-line @typescript-eslint/naming-convention
		'transform-origin': string;
		transform: string;
		transition: string;
	};
} & ProductGalleryContext;

const getContext = ( ns?: string ) => getContextFn< Context >( ns );

type Store = typeof productGalleryLargeImage & StorePart< ProductGallery >;
const { state, actions } = store< Store >( 'woocommerce/product-gallery' );

let isDialogStatusChanged = false;

const productGalleryLargeImage = {
	state: {
		get styles() {
			const { styles } = getContext();
			return Object.entries( styles ?? [] ).reduce(
				( acc, [ key, value ] ) => {
					const style = `${ key }:${ value };`;
					return acc.length > 0 ? `${ acc } ${ style }` : style;
				},
				''
			);
		},
	},
	actions: {
		startZoom: ( event: MouseEvent ) => {
			const target = event.target as HTMLElement;
			const isMouseEventFromLargeImage = target.classList.contains(
				'wc-block-woocommerce-product-gallery-large-image__image'
			);
			if ( ! isMouseEventFromLargeImage ) {
				return actions.resetZoom();
			}

			const element = event.target as HTMLElement;
			const percentageX = ( event.offsetX / element.clientWidth ) * 100;
			const percentageY = ( event.offsetY / element.clientHeight ) * 100;

			const { styles } = getContext();

			if ( styles ) {
				styles.transform = `scale(1.3)`;
				styles[
					'transform-origin'
				] = `${ percentageX }% ${ percentageY }%`;
			}
		},
		resetZoom: () => {
			const context = getContext();
			if ( context.styles ) {
				context.styles.transform = `scale(1.0)`;
				context.styles[ 'transform-origin' ] = '';
			}
		},
	},
	callbacks: {
		scrollInto: () => {
			if ( ! state.isSelected ) {
				return;
			}

			const { isDialogOpen } = getContext();
			const { ref } = getElement();
			// Scroll to the selected image with a smooth animation.
			if ( isDialogOpen === isDialogStatusChanged ) {
				ref.scrollIntoView( {
					behavior: 'smooth',
					block: 'nearest',
					inline: 'center',
				} );
			}

			// Scroll to the selected image when the dialog is being opened without an animation.
			if (
				isDialogOpen &&
				isDialogOpen !== isDialogStatusChanged &&
				ref.closest( 'dialog' )
			) {
				ref.scrollIntoView( {
					behavior: 'instant',
					block: 'nearest',
					inline: 'center',
				} );

				isDialogStatusChanged = isDialogOpen;
			}

			// Scroll to the selected image when the dialog is being closed without an animation.
			if ( ! isDialogOpen && isDialogOpen !== isDialogStatusChanged ) {
				ref.scrollIntoView( {
					behavior: 'instant',
					block: 'nearest',
					inline: 'center',
				} );
				isDialogStatusChanged = isDialogOpen;
			}
		},
	},
};

store< Store >( 'woocommerce/product-gallery', productGalleryLargeImage );
