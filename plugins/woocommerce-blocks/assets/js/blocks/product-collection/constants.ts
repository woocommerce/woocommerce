/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { objectOmit } from '@woocommerce/utils';
import {
	type InnerBlockTemplate,
	createBlock,
	// @ts-expect-error Missing types in Gutenberg
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
	LayoutOptions,
} from './types';
import { ImageSizing } from '../../atomic/blocks/product-elements/image/types';
import { VARIATION_NAME as PRODUCT_TITLE_ID } from './variations/elements/product-title';
import { getDefaultValueOfInheritQueryFromTemplate } from './utils';
import blockJson from './block.json';

export const STOCK_STATUS_OPTIONS = getSetting< Record< string, string > >(
	'stockStatusOptions',
	[]
);

const GLOBAL_HIDE_OUT_OF_STOCK = getSetting< boolean >(
	'hideOutOfStockItems',
	false
);

export const getDefaultStockStatuses = () => {
	return GLOBAL_HIDE_OUT_OF_STOCK
		? Object.keys( objectOmit( STOCK_STATUS_OPTIONS, 'outofstock' ) )
		: Object.keys( STOCK_STATUS_OPTIONS );
};

export const DEFAULT_QUERY: ProductCollectionQuery = {
	perPage: 9,
	pages: 0,
	offset: 0,
	postType: 'product',
	order: 'asc',
	orderBy: 'title',
	search: '',
	exclude: [],
	inherit: null,
	taxQuery: {},
	isProductCollectionBlock: true,
	featured: false,
	woocommerceOnSale: false,
	woocommerceStockStatus: getDefaultStockStatuses(),
	woocommerceAttributes: [],
	woocommerceHandPickedProducts: [],
	timeFrame: undefined,
	priceRange: undefined,
};

export const DEFAULT_ATTRIBUTES: Partial< ProductCollectionAttributes > = {
	query: DEFAULT_QUERY,
	tagName: 'div',
	displayLayout: {
		type: LayoutOptions.GRID,
		columns: 3,
		shrinkColumns: true,
	},
	queryContextIncludes: [ 'collection' ],
	forcePageReload: false,
};

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

export const DEFAULT_FILTERS: Partial< ProductCollectionQuery > = {
	woocommerceOnSale: DEFAULT_QUERY.woocommerceOnSale,
	woocommerceStockStatus: getDefaultStockStatuses(),
	woocommerceAttributes: [],
	taxQuery: DEFAULT_QUERY.taxQuery,
	woocommerceHandPickedProducts: [],
	featured: DEFAULT_QUERY.featured,
	timeFrame: undefined,
	priceRange: undefined,
};

/**
 * Default inner block templates for the product collection block.
 * Exported for use in different collections, e.g., 'New Arrivals' collection.
 */
export const INNER_BLOCKS_PRODUCT_TEMPLATE: InnerBlockTemplate = [
	'woocommerce/product-template',
	{},
	[
		[
			'woocommerce/product-image',
			{
				imageSizing: ImageSizing.THUMBNAIL,
			},
		],
		[
			'core/post-title',
			{
				textAlign: 'center',
				level: 3,
				fontSize: 'medium',
				style: {
					spacing: {
						margin: {
							bottom: '0.75rem',
							top: '0',
						},
					},
				},
				isLink: true,
				__woocommerceNamespace: PRODUCT_TITLE_ID,
			},
		],
		[
			'woocommerce/product-price',
			{
				textAlign: 'center',
				fontSize: 'small',
			},
		],
		[
			'woocommerce/product-button',
			{
				textAlign: 'center',
				fontSize: 'small',
			},
		],
	],
];

export const coreQueryPaginationBlockName = 'core/query-pagination';
export const INNER_BLOCKS_PAGINATION_TEMPLATE: InnerBlockTemplate = [
	coreQueryPaginationBlockName,
	{
		layout: {
			type: 'flex',
			justifyContent: 'center',
		},
	},
];

export const INNER_BLOCKS_NO_RESULTS_TEMPLATE: InnerBlockTemplate = [
	'woocommerce/product-collection-no-results',
];

export const INNER_BLOCKS_TEMPLATE: InnerBlockTemplate[] = [
	INNER_BLOCKS_PRODUCT_TEMPLATE,
	INNER_BLOCKS_PAGINATION_TEMPLATE,
	INNER_BLOCKS_NO_RESULTS_TEMPLATE,
];

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
