/**
 * External dependencies
 */
import { createBlock, BlockInstance } from '@wordpress/blocks';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { disableAutoUpdate } from './migration-from-products-to-product-collection';
import {
	getProductCollectionBlockClientIds,
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
import {
	LayoutOptions,
	ProductCollectionDisplayLayout,
} from '../product-collection/types';

const VARIATION_NAME = 'woocommerce/product-query';

const mapAttributes = ( attributes ) => {
	const { query, ...restAttributes } = attributes;
	const {
		woocommerceAttributes,
		woocommerceStockStatus,
		woocommerceOnSale,
		woocommerceHandPickedProducts,
		taxQuery,
		isProductCollectionBlock,
		...restQuery
	} = query;

	// These fields have to be explicitly removed if they are empty
	// otherwise incorrect data is fetched even if they are set as undefined.
	const mappedQuery = { ...restQuery };
	if ( woocommerceHandPickedProducts ) {
		mappedQuery.include = woocommerceHandPickedProducts;
	}

	if ( woocommerceOnSale ) {
		mappedQuery.__woocommerceOnSale = woocommerceOnSale;
	}

	if ( taxQuery ) {
		mappedQuery.taxQuery = taxQuery;
	}

	return {
		...restAttributes,
		namespace: VARIATION_NAME,
		query: {
			__woocommerceAttributes: woocommerceAttributes || [],
			__woocommerceStockStatus: woocommerceStockStatus || [],
			...mappedQuery,
		},
	};
};

const isProductTemplate: IsBlockType = ( { name } ) =>
	name === 'woocommerce/product-template';

const isPostTitle: IsBlockType = ( { name, attributes } ) =>
	name === 'core/post-title' &&
	attributes.__woocommerceNamespace ===
		'woocommerce/product-collection/product-title';

const isPostSummary: IsBlockType = ( { name, attributes } ) =>
	name === 'core/post-excerpt' &&
	attributes.__woocommerceNamespace ===
		'woocommerce/product-collection/product-summary';

const mapLayoutType = ( type: LayoutOptions ): PostTemplateLayoutTypes => {
	if ( type === 'flex' ) {
		return 'grid';
	}
	if ( type === 'list' ) {
		return 'default';
	}
	return 'grid';
};

const mapLayoutPropertiesFromProductCollectionToPostTemplate = (
	layout: ProductCollectionDisplayLayout
): PostTemplateLayout => {
	const { type, columns } = layout;

	return {
		type: mapLayoutType( type ),
		columnCount: columns,
	};
};

const transformProductTemplate: TransformBlock = (
	block,
	innerBlocks,
	displayLayout?: ProductCollectionDisplayLayout
) => {
	return createBlock(
		'core/post-template',
		{
			className: 'products-block-post-template',
			layout: postTemplateHasSupportForGridView
				? mapLayoutPropertiesFromProductCollectionToPostTemplate(
						displayLayout as ProductCollectionDisplayLayout
				  )
				: undefined,
			__woocommerceNamespace:
				'woocommerce/product-query/product-template',
			...block.attributes,
		},
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

const mapInnerBlocks = (
	innerBlocks: BlockInstance[],
	displayLayout?: ProductCollectionDisplayLayout
): BlockInstance[] => {
	const mappedInnerBlocks = innerBlocks.map( ( innerBlock ) => {
		const { name, attributes } = innerBlock;

		const mappedInnerInnerBlocks = mapInnerBlocks( innerBlock.innerBlocks );

		if ( isProductTemplate( innerBlock ) ) {
			return transformProductTemplate(
				innerBlock,
				mappedInnerInnerBlocks,
				displayLayout
			);
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

const replaceProductCollectionBlock = ( clientId: string ) => {
	const productCollectionBlock =
		select( 'core/block-editor' ).getBlock( clientId );
	const canBeInserted = checkIfBlockCanBeInserted( clientId, 'core/query' );

	if ( productCollectionBlock && canBeInserted ) {
		const { attributes = {}, innerBlocks = [] } = productCollectionBlock;
		// Starting from GB 16, it's not Query Loop that keeps the layout, but the Post Template block.
		// We need to account for that and in that case, move the layout properties
		// from Product Collection either to Query Loop OR to Post Template.
		const { displayLayout, ...restAttributes } = attributes;
		const adjustedAttributes = ! postTemplateHasSupportForGridView
			? mapAttributes( attributes )
			: mapAttributes( restAttributes );
		const adjustedInnerBlocks = mapInnerBlocks(
			innerBlocks,
			displayLayout
		);

		const productsBlock = createBlock(
			'core/query',
			adjustedAttributes,
			adjustedInnerBlocks
		);
		dispatch( 'core/block-editor' ).replaceBlock( clientId, productsBlock );
		return true;
	}
	return false;
};

export const replaceProductCollectionWithProducts = () => {
	const blocks = select( 'core/block-editor' ).getBlocks();
	const productCollectionBlockClientIds =
		getProductCollectionBlockClientIds( blocks );

	productCollectionBlockClientIds.map( replaceProductCollectionBlock );
};

export const revertMigration = () => {
	disableAutoUpdate();
	setUpgradeStatus( {
		status: 'reverted',
		time: Date.now(),
	} );
	replaceProductCollectionWithProducts();
};
