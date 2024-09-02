/**
 * External dependencies
 */
import {
	BlockInstance,
	createBlock,
	registerBlockType,
} from '@wordpress/blocks';
import { Icon, more } from '@wordpress/icons';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import { BLOCK_NAME_MAP } from './constants';
import { BlockAttributes } from './types';
import { blockVariations } from './block-variations';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: {
			src: (
				<Icon
					icon={ more }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit,
		save,
		variations: blockVariations,
		transforms: {
			from: [
				{
					type: 'block',
					blocks: [ 'woocommerce/filter-wrapper' ],
					transform: (
						attributes: BlockAttributes,
						innerBlocks: BlockInstance[]
					) => {
						const newInnerBlocks: BlockInstance[] = [];
						// Loop through inner blocks to preserve the block order.
						innerBlocks.forEach( ( block ) => {
							if (
								block.name ===
								`woocommerce/${ attributes.filterType }`
							) {
								newInnerBlocks.push(
									createBlock(
										BLOCK_NAME_MAP[ attributes.filterType ],
										block.attributes
									)
								);
							}

							if ( block.name === 'core/heading' ) {
								newInnerBlocks.push( block );
							}
						} );

						return createBlock(
							'woocommerce/product-filter',
							attributes,
							newInnerBlocks
						);
					},
				},
			],
		},
	} );
}
