/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { BlockInstance, createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import listPriceBlock from '../components/list-price/block';

export function parseProductToBlocks( product: Partial< Product > ) {
	const blocks: BlockInstance[] = [];

	blocks.push( createBlock( listPriceBlock.name ) );

	return blocks;
}
