/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { BlockInstance, createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import nameBlock from '../components/details-name-block/block.json';

export function parseProductToBlocks( product: Partial< Product > ) {
	const blocks: BlockInstance[] = [];

	blocks.push(
		createBlock( nameBlock.name, {
			name: product.name,
		} )
	);

	return blocks;
}
