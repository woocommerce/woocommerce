/**
 * External dependencies
 */
import { gallery as icon } from '@wordpress/icons';
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import './style.scss';

const galleryBlock = 'woocommerce/product-gallery';

registerBlockSingleProductTemplate( {
	blockName: metadata.name,
	// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core
	blockMetadata: metadata,
	blockSettings: {
		icon,
		// @ts-expect-error `edit` can be extended to include other attributes
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
	},
	isAvailableOnPostEditor: false,
} );
