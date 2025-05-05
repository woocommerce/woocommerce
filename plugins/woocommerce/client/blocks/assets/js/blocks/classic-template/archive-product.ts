/**
 * External dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import {
	INNER_BLOCKS_TEMPLATE as productCollectionInnerBlocksTemplate,
	DEFAULT_ATTRIBUTES as productCollectionDefaultAttributes,
	DEFAULT_QUERY as productCollectionDefaultQuery,
} from '@woocommerce/blocks/product-collection/constants';
import {
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Gutenberg
	createBlocksFromInnerBlocksTemplate,
	type BlockInstance,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { createArchiveTitleBlock, createRowBlock } from './utils';
import { OnClickCallbackParameter, type InheritedAttributes } from './types';

const createProductCollectionBlock = (
	inheritedAttributes: InheritedAttributes
) =>
	createBlock(
		'woocommerce/product-collection',
		{
			...productCollectionDefaultAttributes,
			...inheritedAttributes,
			query: {
				...productCollectionDefaultQuery,
				inherit: true,
			},
		},
		createBlocksFromInnerBlocksTemplate(
			productCollectionInnerBlocksTemplate
		)
	);

const getBlockifiedTemplate = (
	inheritedAttributes: InheritedAttributes,
	withTermDescription = false
) =>
	[
		createBlock( 'woocommerce/breadcrumbs', inheritedAttributes ),
		createArchiveTitleBlock( 'archive-title', inheritedAttributes ),
		withTermDescription
			? createBlock( 'core/term-description', inheritedAttributes )
			: null,
		createBlock( 'woocommerce/store-notices', inheritedAttributes ),
		createRowBlock(
			[
				createBlock( 'woocommerce/product-results-count' ),
				createBlock( 'woocommerce/catalog-sorting' ),
			],
			inheritedAttributes
		),
		createProductCollectionBlock( inheritedAttributes ),
	].filter( Boolean ) as BlockInstance[];

const getBlockifiedTemplateWithTermDescription = (
	inheritedAttributes: InheritedAttributes
) => getBlockifiedTemplate( inheritedAttributes, true );

const isConversionPossible = () => {
	return true;
};

const getDescriptionAllowingConversion = ( templateTitle: string ) =>
	sprintf(
		/* translators: %s is the template title */
		__(
			'Transform this template into multiple blocks so you can add, remove, reorder, and customize your %s template.',
			'woocommerce'
		),
		templateTitle
	);

const getDescriptionDisallowingConversion = ( templateTitle: string ) =>
	sprintf(
		/* translators: %s is the template title */
		__(
			'This block serves as a placeholder for your %s. It will display the actual product image, title, price in your store. You can move this placeholder around and add more blocks around to customize the template.',
			'woocommerce'
		),
		templateTitle
	);

const getDescription = ( templateTitle: string, canConvert: boolean ) => {
	if ( canConvert ) {
		return getDescriptionAllowingConversion( templateTitle );
	}

	return getDescriptionDisallowingConversion( templateTitle );
};

const getButtonLabel = () => __( 'Transform into blocks', 'woocommerce' );

const onClickCallback = ( {
	clientId,
	attributes,
	getBlocks,
	replaceBlock,
	selectBlock,
}: OnClickCallbackParameter ) => {
	replaceBlock( clientId, getBlockifiedTemplate( attributes ) );

	const blocks = getBlocks();

	const groupBlock = blocks.find(
		( block ) =>
			block.name === 'core/group' &&
			block.innerBlocks.some(
				( innerBlock ) =>
					innerBlock.name === 'woocommerce/store-notices'
			)
	);

	if ( groupBlock ) {
		selectBlock( groupBlock.clientId );
	}
};

const onClickCallbackWithTermDescription = ( {
	clientId,
	attributes,
	getBlocks,
	replaceBlock,
	selectBlock,
}: OnClickCallbackParameter ) => {
	replaceBlock( clientId, getBlockifiedTemplate( attributes, true ) );

	const blocks = getBlocks();

	const groupBlock = blocks.find(
		( block ) =>
			block.name === 'core/group' &&
			block.innerBlocks.some(
				( innerBlock ) =>
					innerBlock.name === 'woocommerce/store-notices'
			)
	);

	if ( groupBlock ) {
		selectBlock( groupBlock.clientId );
	}
};

const productCatalogBlockifyConfig = {
	getButtonLabel,
	onClickCallback,
	getBlockifiedTemplate,
};

const productTaxonomyBlockifyConfig = {
	getButtonLabel,
	onClickCallback: onClickCallbackWithTermDescription,
	getBlockifiedTemplate: getBlockifiedTemplateWithTermDescription,
};

export const blockifiedProductCatalogConfig = {
	isConversionPossible,
	getDescription,
	blockifyConfig: productCatalogBlockifyConfig,
};

export const blockifiedProductTaxonomyConfig = {
	isConversionPossible,
	getDescription,
	blockifyConfig: productTaxonomyBlockifyConfig,
};
