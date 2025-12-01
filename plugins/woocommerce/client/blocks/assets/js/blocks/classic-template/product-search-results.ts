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
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	createBlocksFromInnerBlocksTemplate,
	type BlockInstance,
	type InnerBlockTemplate,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { createArchiveTitleBlock, createRowBlock } from './utils';
import { OnClickCallbackParameter, type InheritedAttributes } from './types';

const createNoResultsParagraph = () =>
	createBlock( 'core/paragraph', {
		content: __(
			'No products were found matching your selection.',
			'woocommerce'
		),
	} );

const createProductSearch = () =>
	createBlock( 'core/search', {
		buttonPosition: 'button-outside',
		buttonText: __( 'Search', 'woocommerce' ),
		buttonUseIcon: false,
		showLabel: false,
		placeholder: __( 'Search products…', 'woocommerce' ),
		query: { post_type: 'product' },
	} );

const extendInnerBlocksWithNoResultsContent = (
	innerBlocks: InnerBlockTemplate[],
	inheritedAttributes: InheritedAttributes
) => {
	// InnerBlockTemplate is an array block representation so properties
	// like name or attributes need to be accessed with array indexes.
	const nameArrayIndex = 0;
	const attributesArrayIndex = 1;

	const noResultsContent = [
		createNoResultsParagraph(),
		createProductSearch(),
	];

	const noResultsBlockName = 'woocommerce/product-collection-no-results';
	const noResultsBlockIndex = innerBlocks.findIndex(
		( block ) => block[ nameArrayIndex ] === noResultsBlockName
	);
	const noResultsBlock = innerBlocks[ noResultsBlockIndex ];
	const attributes = {
		...( noResultsBlock[ attributesArrayIndex ] || {} ),
		...inheritedAttributes,
	};

	const extendedNoResults = [
		noResultsBlockName,
		attributes,
		noResultsContent,
	];

	return [
		...innerBlocks.slice( 0, noResultsBlockIndex ),
		extendedNoResults,
		...innerBlocks.slice( noResultsBlockIndex + 1 ),
	];
};

const createProductCollectionBlock = (
	inheritedAttributes: InheritedAttributes
) => {
	const productCollectionInnerBlocksWithNoResults =
		extendInnerBlocksWithNoResultsContent(
			productCollectionInnerBlocksTemplate,
			inheritedAttributes
		);

	return createBlock(
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
			productCollectionInnerBlocksWithNoResults
		)
	);
};

const getBlockifiedTemplate = ( inheritedAttributes: InheritedAttributes ) =>
	[
		createArchiveTitleBlock( 'search-title', inheritedAttributes ),
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

const getDescription = ( templateTitle: string ) =>
	sprintf(
		/* translators: %s is the template title */
		__(
			'Transform this template into multiple blocks so you can add, remove, reorder, and customize your %s template.',
			'woocommerce'
		),
		templateTitle
	);

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

const getButtonLabel = () => __( 'Transform into blocks', 'woocommerce' );

const blockifyConfig = {
	getButtonLabel,
	onClickCallback,
	getBlockifiedTemplate,
};

export { getDescription, blockifyConfig };
