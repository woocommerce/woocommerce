/**
 * External dependencies
 */
import { Icon, column } from '@wordpress/icons';
import {
	registerBlockType,
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Gutenberg
	createBlocksFromInnerBlocksTemplate,
	BlockInstance,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';
import crossSells from '../../../product-collection/collections/cross-sells';

// @ts-expect-error - blockName can be either string or object
registerBlockType( 'woocommerce/cart-cross-sells-block', {
	apiVersion: metadata.apiVersion,
	title: metadata.title,
	icon: {
		src: (
			<Icon
				icon={ column }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
	save: Save,
	transforms: {
		to: [
			{
				type: 'block',
				blocks: [ 'woocommerce/product-collection' ],
				transform: (
					attributes: Record< string, unknown >,
					innerBlocks: BlockInstance[]
				) => {
					const columns =
						innerBlocks.find(
							( block ) =>
								block.name ===
								'woocommerce/cart-cross-sells-products-block'
						)?.attributes?.columns || 3;

					return createBlock(
						'woocommerce/product-collection',
						{
							collection:
								'woocommerce/product-collection/cross-sells',
						},
						createBlocksFromInnerBlocksTemplate(
							crossSells.innerBlocks
						)
					);
				},
			},
		],
	},
} );
