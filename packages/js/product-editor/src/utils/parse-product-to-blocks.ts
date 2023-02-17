/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';
import { BlockInstance, createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import imagesBlock from '../components/images/block';
import nameBlock from '../components/name/block';
import sectionBlock from '../components/section/block';
import summaryBlock from '../components/summary/block';

export function parseProductToBlocks( product: Partial< Product > ) {
	const blocks: BlockInstance[] = [];

	blocks.push(
		createBlock(
			sectionBlock.name,
			{
				title: __( 'Product details', 'woocommerce' ),
				description: __(
					'This info will be displayed on the product page, category pages, social media, and search results.',
					'woocommerce'
				),
			},
			[
				createBlock( nameBlock.name, {
					name: product.name,
				} ),
				createBlock( summaryBlock.name, {
					content: product.short_description,
				} ),
				createBlock( imagesBlock.name, {} ),
			]
		)
	);

	return blocks;
}
