/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { isWpVersion } from '@woocommerce/settings';
import type { BlockEditProps, BlockInstance } from '@wordpress/blocks';
import type { Block } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductCollectionAttributes, ProductCollectionQuery } from './types';
import { coreQueryPaginationBlockName } from './constants';
import blockJson from './block.json';

/**
 * Sets the new query arguments of a Product Query block
 *
 * Shorthand for setting new nested query parameters.
 */
export function setQueryAttribute(
	block: BlockEditProps< ProductCollectionAttributes >,
	queryParams: Partial< ProductCollectionQuery >
) {
	const { query } = block.attributes;

	block.setAttributes( {
		query: {
			...query,
			...queryParams,
		},
	} );
}

const isInProductArchive = () => {
	const ARCHIVE_PRODUCT_TEMPLATES = [
		'woocommerce/woocommerce//archive-product',
		'woocommerce/woocommerce//taxonomy-product_cat',
		'woocommerce/woocommerce//taxonomy-product_tag',
		'woocommerce/woocommerce//taxonomy-product_attribute',
		'woocommerce/woocommerce//product-search-results',
	];

	const currentTemplateId = select(
		'core/edit-site'
	).getEditedPostId() as string;

	/**
	 * Set inherit value when Product Collection block is first added to the page.
	 * We want inherit value to be true when block is added to ARCHIVE_PRODUCT_TEMPLATES
	 * and false when added to somewhere else.
	 */
	return currentTemplateId
		? ARCHIVE_PRODUCT_TEMPLATES.includes( currentTemplateId )
		: false;
};

const isFirstBlockThatSyncsWithQuery = () => {
	const blocks = select( blockEditorStore ).getBlocks() as BlockInstance[];
	const blockAlreadySyncedWithQuery = blocks.find(
		( block ) =>
			block.name === 'woocommerce/product-collection' &&
			block.attributes?.query?.inherit
	);

	return ! blockAlreadySyncedWithQuery;
};

export function getDefaultValueOfInheritQueryFromTemplate() {
	return isInProductArchive() ? isFirstBlockThatSyncsWithQuery() : false;
}

/**
 * Add Product Collection block to the parent array of the Core Pagination block.
 * This enhancement allows the Core Pagination block to be available for the Product Collection block.
 */
export const addProductCollectionBlockToParentOfPaginationBlock = () => {
	if ( isWpVersion( '6.1', '>=' ) ) {
		addFilter(
			'blocks.registerBlockType',
			'woocommerce/add-product-collection-block-to-parent-array-of-pagination-block',
			( blockSettings: Block, blockName: string ) => {
				if (
					blockName !== coreQueryPaginationBlockName ||
					! blockSettings?.parent
				) {
					return blockSettings;
				}

				return {
					...blockSettings,
					parent: [ ...blockSettings.parent, blockJson.name ],
				};
			}
		);
	}
};
