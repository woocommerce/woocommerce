/**
 * External dependencies
 */
import { gallery as icon } from '@wordpress/icons';
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import './style.scss';

const galleryBlock = 'woocommerce/product-gallery';

const blockConfig = {
	...metadata,
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

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: false,
} );
