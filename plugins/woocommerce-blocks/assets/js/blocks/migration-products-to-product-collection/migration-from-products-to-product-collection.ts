/**
 * External dependencies
 */
import { createBlock, BlockInstance } from '@wordpress/blocks';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getInitialStatusLSValue } from './constants';
import {
	getProductsBlockClientIds,
	checkIfBlockCanBeInserted,
	postTemplateHasSupportForGridView,
	setUpgradeStatus,
} from './migration-utils';
import type {
	TransformBlock,
	IsBlockType,
	PostTemplateLayout,
	PostTemplateLayoutTypes,
} from './types';
import { DEFAULT_ATTRIBUTES } from '../product-collection/constants';
import {
	LayoutOptions,
	ProductCollectionDisplayLayout,
} from '../product-collection/types';

const mapAttributes = ( attributes: Record< string, unknown > ) => {
	const { query, namespace, ...restAttributes } = attributes;
	const {
		__woocommerceAttributes,
		__woocommerceStockStatus,
		__woocommerceOnSale,
		include,
		...restQuery
	} = query;

	return {
		...restAttributes,
		query: {
			woocommerceAttributes: __woocommerceAttributes,
			woocommerceStockStatus: __woocommerceStockStatus,
			woocommerceOnSale: __woocommerceOnSale,
			woocommerceHandPickedProducts: include,
			taxQuery: {},
			isProductCollectionBlock: true,
			...restQuery,
		},
		convertedFromProducts: true,
	};
};

const isPostTemplate: IsBlockType = ( { name, attributes } ) =>
	name === 'core/post-template' &&
	attributes.__woocommerceNamespace ===
		'woocommerce/product-query/product-template';

const isPostTitle: IsBlockType = ( { name, attributes } ) =>
	name === 'core/post-title' &&
	attributes.__woocommerceNamespace ===
		'woocommerce/product-query/product-title';

const isPostSummary: IsBlockType = ( { name, attributes } ) =>
	name === 'core/post-excerpt' &&
	attributes.__woocommerceNamespace ===
		'woocommerce/product-query/product-summary';

const transformPostTemplate: TransformBlock = ( block, innerBlocks ) => {
	const { __woocommerceNamespace, className, layout, ...restAttrributes } =
		block.attributes;

	return createBlock(
		'woocommerce/product-template',
		restAttrributes,
		innerBlocks
	);
};

const transformPostTitle: TransformBlock = ( block, innerBlocks ) => {
	const { __woocommerceNamespace, ...restAttrributes } = block.attributes;
	return createBlock(
		'core/post-title',
		{
			__woocommerceNamespace:
				'woocommerce/product-collection/product-title',
			...restAttrributes,
		},
		innerBlocks
	);
};

const transformPostSummary: TransformBlock = ( block, innerBlocks ) => {
	const { __woocommerceNamespace, ...restAttrributes } = block.attributes;
	return createBlock(
		'core/post-excerpt',
		{
			__woocommerceNamespace:
				'woocommerce/product-collection/product-summary',
			...restAttrributes,
		},
		innerBlocks
	);
};

const mapLayoutType = ( type: PostTemplateLayoutTypes ): LayoutOptions => {
	if ( type === 'grid' ) {
		return LayoutOptions.GRID;
	}
	if ( type === 'default' ) {
		return LayoutOptions.STACK;
	}
	return LayoutOptions.GRID;
};

const mapLayoutPropertiesFromPostTemplateToProductCollection = (
	layout: PostTemplateLayout
): ProductCollectionDisplayLayout => {
	if ( layout === undefined ) {
		return DEFAULT_ATTRIBUTES.displayLayout as ProductCollectionDisplayLayout;
	}

	const { type, columnCount } = layout;

	return {
		type: mapLayoutType( type ),
		columns: columnCount,
	};
};

const getLayoutAttribute = (
	attributes,
	innerBlocks: BlockInstance[]
): ProductCollectionDisplayLayout => {
	// Starting from GB 16, it's not Query Loop that keeps the layout, but the Post Template block.
	// We need to account for that and in that case, move the layout properties
	// from Post Template to Product Collection.
	const postTemplate = innerBlocks.find( isPostTemplate );
	const { layout: postTemplateLayout } = postTemplate?.attributes || {};
	return postTemplateHasSupportForGridView
		? mapLayoutPropertiesFromPostTemplateToProductCollection(
				postTemplateLayout
		  )
		: attributes.displayLayout;
};

const mapInnerBlocks = ( innerBlocks: BlockInstance[] ): BlockInstance[] => {
	const mappedInnerBlocks = innerBlocks.map( ( innerBlock ) => {
		const { name, attributes } = innerBlock;

		const mappedInnerInnerBlocks = mapInnerBlocks( innerBlock.innerBlocks );

		if ( isPostTemplate( innerBlock ) ) {
			return transformPostTemplate( innerBlock, mappedInnerInnerBlocks );
		}

		if ( isPostTitle( innerBlock ) ) {
			return transformPostTitle( innerBlock, mappedInnerInnerBlocks );
		}

		if ( isPostSummary( innerBlock ) ) {
			return transformPostSummary( innerBlock, mappedInnerInnerBlocks );
		}
		return createBlock( name, attributes, mappedInnerInnerBlocks );
	} );

	return mappedInnerBlocks;
};

const replaceProductsBlock = ( clientId: string ) => {
	const productsBlock = select( 'core/block-editor' ).getBlock( clientId );
	const canBeInserted = checkIfBlockCanBeInserted(
		clientId,
		'woocommerce/product-collection'
	);

	if ( productsBlock && canBeInserted ) {
		const { attributes = {}, innerBlocks = [] } = productsBlock;
		const displayLayout = getLayoutAttribute( attributes, innerBlocks );
		const adjustedAttributes = mapAttributes( {
			...attributes,
			displayLayout,
		} );
		const adjustedInnerBlocks = mapInnerBlocks( innerBlocks );

		const productCollectionBlock = createBlock(
			'woocommerce/product-collection',
			adjustedAttributes,
			adjustedInnerBlocks
		);
		dispatch( 'core/block-editor' ).replaceBlock(
			clientId,
			productCollectionBlock
		);
		return true;
	}
	return false;
};

const replaceProductsBlocks = ( productsBlockClientIds: string[] ) => {
	const results = productsBlockClientIds.map( replaceProductsBlock );
	return !! results.length && results.every( ( result ) => !! result );
};

export const replaceProductsWithProductCollection = () => {
	const queryBlocksCount =
		select( 'core/block-editor' ).getGlobalBlockCount( 'core/query' );
	if ( queryBlocksCount === 0 ) {
		return;
	}

	const blocks = select( 'core/block-editor' ).getBlocks();
	const productsBlockClientIds = getProductsBlockClientIds( blocks );
	const productsBlocksCount = productsBlockClientIds.length;

	if ( productsBlocksCount === 0 ) {
		return;
	}

	replaceProductsBlocks( productsBlockClientIds );
};

export const manualUpdate = () => {
	setUpgradeStatus( getInitialStatusLSValue() );
	replaceProductsWithProductCollection();
};
