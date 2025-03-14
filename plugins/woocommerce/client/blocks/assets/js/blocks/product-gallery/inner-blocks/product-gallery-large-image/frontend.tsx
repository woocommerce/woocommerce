/**
 * External dependencies
 */
import { store, getContext as getContextFn } from '@wordpress/interactivity';
import type { StorePart } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import type { ProductGalleryContext, ProductGallery } from '../../frontend';

type Context = {
	styles: {
		// eslint-disable-next-line @typescript-eslint/naming-convention
		'transform-origin': string;
		transform: string;
	};
} & ProductGalleryContext;

const getContext = ( ns?: string ) => getContextFn< Context >( ns );

type Store = typeof productGalleryLargeImage & StorePart< ProductGallery >;
const { actions } = store< Store >( 'woocommerce/product-gallery' );

const productGalleryLargeImage = {
	actions: {
		startZoom: ( event: MouseEvent ) => {
			const target = event.target as HTMLElement;
			const isMouseEventFromLargeImage = target.classList.contains(
				'wc-block-woocommerce-product-gallery-large-image__image'
			);

			if ( ! isMouseEventFromLargeImage ) {
				return actions.resetZoom( event );
			}

			const element = event.target as HTMLElement;
			const percentageX = ( event.offsetX / element.clientWidth ) * 100;
			const percentageY = ( event.offsetY / element.clientHeight ) * 100;

			const { selectedImageId } = getContext();

			const imageId = parseInt(
				target.getAttribute( 'data-image-id' ) ?? '0',
				10
			);
			if ( selectedImageId === imageId ) {
				target.style.transform = `scale(1.3)`;
				target.style.transformOrigin = `${ percentageX }% ${ percentageY }%`;
			}
		},
		resetZoom: ( event: MouseEvent ) => {
			const target = event.target as HTMLElement;

			if ( ! target ) {
				return;
			}

			const image = target.querySelector(
				'.wc-block-woocommerce-product-gallery-large-image__image--active-image-slide'
			) as HTMLElement;

			if ( ! image ) {
				return;
			}

			image.style.transform = `scale(1.0)`;
			image.style.transformOrigin = '';
		},
	},
};

store< Store >( 'woocommerce/product-gallery', productGalleryLargeImage );
