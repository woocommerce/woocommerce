/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { createBlock, BlockAttributes, BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import icon from './icon';
import './inner-blocks/product-gallery-next-previous-buttons';
import './inner-blocks/product-gallery-thumbnails';

const updateInnerBlocks = ( blocks: BlockInstance[] ): BlockInstance[] => {
	return blocks.map( ( block: BlockInstance ) => {
		if (
			block.name ===
			'woocommerce/product-gallery-large-image-next-previous'
		) {
			const newBlock = createBlock(
				'woocommerce/navigation-arrows',
				block.attributes
			);
			return newBlock;
		}

		if ( block.innerBlocks?.length ) {
			return {
				...block,
				innerBlocks: updateInnerBlocks( block.innerBlocks ),
			};
		}

		return block;
	} );
};

const blockConfig = {
	...metadata,
	icon,
	edit: Edit,
	save: Save,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
	deprecated: [
		{
			isEligible: () => true,
			attributes: metadata.attributes,
			supports: metadata.supports,
			save: Save,
			migrate: (
				attributes: BlockAttributes,
				innerBlocks: BlockInstance[]
			) => [ attributes, updateInnerBlocks( innerBlocks ) ],
		},
	],
} );
