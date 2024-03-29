/**
 * External dependencies
 */
import {
	createBlock,
	createBlocksFromInnerBlocksTemplate,
	type BlockInstance,
	type InnerBlockTemplate,
} from '@wordpress/blocks';
import { isWpVersion } from '@woocommerce/settings';
import { __, sprintf } from '@wordpress/i18n';
import {
	INNER_BLOCKS_TEMPLATE as productsInnerBlocksTemplate,
	QUERY_DEFAULT_ATTRIBUTES as productsQueryDefaultAttributes,
	PRODUCT_QUERY_VARIATION_NAME as productsVariationName,
} from '@woocommerce/blocks/product-query/constants';

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
	const noResultsContent = [
		createNoResultsParagraph(),
		createProductSearch(),
	];

	const noResultsBlockName = 'core/query-no-results';
	const noResultsBlockIndex = innerBlocks.findIndex(
		( block ) => block[ 0 ] === noResultsBlockName
	);
	const noResultsBlock = innerBlocks[ noResultsBlockIndex ];
	const attributes = {
		...( noResultsBlock[ 1 ] || {} ),
		...inheritedAttributes,
	};

	const extendedNoResults = [
		noResultsBlockName,
		attributes,
		noResultsContent,
	];

	return [
		...productsInnerBlocksTemplate.slice( 0, noResultsBlockIndex ),
		extendedNoResults,
		...productsInnerBlocksTemplate.slice( noResultsBlockIndex + 1 ),
	];
};

const createProductsBlock = ( inheritedAttributes: InheritedAttributes ) => {
	const productsInnerBlocksWithNoResults =
		extendInnerBlocksWithNoResultsContent(
			productsInnerBlocksTemplate,
			inheritedAttributes
		);

	return createBlock(
		'core/query',
		{
			...productsQueryDefaultAttributes,
			...inheritedAttributes,
			namespace: productsVariationName,
			query: {
				...productsQueryDefaultAttributes.query,
				inherit: true,
			},
		},
		createBlocksFromInnerBlocksTemplate( productsInnerBlocksWithNoResults )
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
		createProductsBlock( inheritedAttributes ),
	].filter( Boolean ) as BlockInstance[];

const isConversionPossible = () => {
	// Blockification is possible for the WP version 6.1 and above,
	// which are the versions the Products block supports.
	return isWpVersion( '6.1', '>=' );
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

export { isConversionPossible, getDescription, blockifyConfig };
