/**
 * External dependencies
 */
import { Icon, column } from '@wordpress/icons';
import {
	registerBlockType,
	createBlock,
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';
import crossSells from '../../../product-collection/collections/cross-sells';

export const createCrossSellsProductCollection = () => {
	return createBlock(
		'woocommerce/product-collection',
		{
			...crossSells.attributes,
			displayLayout: {
				...crossSells.attributes.displayLayout,
				columns: 3,
			},
			query: {
				...crossSells.attributes.query,
				perPage: 3,
			},
			collection: 'woocommerce/product-collection/cross-sells',
		},
		createBlocksFromInnerBlocksTemplate( crossSells.innerBlocks )
	);
};

// @ts-expect-error - registerBlockType isn't well typed for metadata as the first argument
registerBlockType( metadata, {
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
				transform: createCrossSellsProductCollection,
			},
		],
	},
} );
