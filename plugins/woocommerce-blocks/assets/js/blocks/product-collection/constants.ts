/**
 * External dependencies
 */
import {
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	ProductCollectionAttributes,
	TProductCollectionOrder,
	TProductCollectionOrderBy,
	ProductCollectionQuery,
	ProductCollectionDisplayLayout,
} from './types';
import { getDefaultValueOfInheritQueryFromTemplate } from './utils';
import blockJson from './block.json';
import {
	DEFAULT_QUERY,
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_TEMPLATE,
} from './constants-register-product-collection';

export * from './constants-register-product-collection';

export const getDefaultQuery = (
	currentQuery: ProductCollectionQuery
): ProductCollectionQuery => ( {
	...currentQuery,
	orderBy: DEFAULT_QUERY.orderBy as TProductCollectionOrderBy,
	order: DEFAULT_QUERY.order as TProductCollectionOrder,
	inherit: getDefaultValueOfInheritQueryFromTemplate(),
} );

export const getDefaultDisplayLayout = () =>
	DEFAULT_ATTRIBUTES.displayLayout as ProductCollectionDisplayLayout;

export const getDefaultSettings = (
	currentAttributes: ProductCollectionAttributes
): Partial< ProductCollectionAttributes > => ( {
	displayLayout: getDefaultDisplayLayout(),
	query: getDefaultQuery( currentAttributes.query ),
} );

export const DEFAULT_FILTERS: Pick<
	ProductCollectionQuery,
	| 'woocommerceOnSale'
	| 'woocommerceStockStatus'
	| 'woocommerceAttributes'
	| 'woocommerceHandPickedProducts'
	| 'taxQuery'
	| 'featured'
	| 'timeFrame'
	| 'priceRange'
> = {
	woocommerceOnSale: DEFAULT_QUERY.woocommerceOnSale,
	woocommerceStockStatus: DEFAULT_QUERY.woocommerceStockStatus,
	woocommerceAttributes: DEFAULT_QUERY.woocommerceAttributes,
	woocommerceHandPickedProducts: DEFAULT_QUERY.woocommerceHandPickedProducts,
	taxQuery: DEFAULT_QUERY.taxQuery,
	featured: DEFAULT_QUERY.featured,
	timeFrame: DEFAULT_QUERY.timeFrame,
	priceRange: DEFAULT_QUERY.priceRange,
};

export const getDefaultProductCollection = () =>
	createBlock(
		blockJson.name,
		{
			...DEFAULT_ATTRIBUTES,
			query: {
				...DEFAULT_ATTRIBUTES.query,
				inherit: getDefaultValueOfInheritQueryFromTemplate(),
			},
		},
		createBlocksFromInnerBlocksTemplate( INNER_BLOCKS_TEMPLATE )
	);
