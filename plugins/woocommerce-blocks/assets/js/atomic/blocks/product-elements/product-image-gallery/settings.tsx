/**
 * External dependencies
 */
import { gallery as icon } from '@wordpress/icons';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';

const galleryBlock = 'woocommerce/product-gallery';

export const ProductImageGalleryBlockSettings = {
	icon,
	edit,
	transforms: {
		to: [
			{
				type: 'block',
				blocks: [ galleryBlock ],
				transform: () => {
					return createBlock( galleryBlock );
				},
			},
		],
	},
};
