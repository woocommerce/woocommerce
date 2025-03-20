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
import type { ProductGalleryContext } from '../../types';
import type { Store as ProductGallery } from '../../frontend';

type Context = {
	styles: {
		// eslint-disable-next-line @typescript-eslint/naming-convention
		'transform-origin': string;
		transform: string;
	};
} & ProductGalleryContext;

const getContext = ( ns?: string ) => getContextFn< Context >( ns );

type Store = typeof productGalleryLargeImage & ProductGallery;

const productGalleryLargeImage = {
	state: {
		get styles() {
			const { styles } = getContext();
			const { isSelected } = state;
			return isSelected
				? Object.entries( styles ?? [] ).reduce(
						( acc, [ key, value ] ) => {
							const style = `${ key }:${ value };`;
							return acc.length > 0
								? `${ acc } ${ style }`
								: style;
						},
						''
				  )
				: '';
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

			const { ref } = getElement();
			if ( ref ) {
				// Scroll to the selected image with a smooth animation.
				ref.scrollIntoView( {
					behavior: 'smooth',
					block: 'nearest',
					inline: 'center',
				} );
			}
		},
	},
};

const { state, actions } = store< Store >(
	'woocommerce/product-gallery',
	productGalleryLargeImage,
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);
