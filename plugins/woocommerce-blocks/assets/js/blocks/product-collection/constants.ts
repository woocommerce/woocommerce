/**
 * Purpose of this file:
 * This file defines constants for use in `plugins/woocommerce-blocks/assets/js/blocks-registry/product-collection/register-product-collection.tsx`.
 * By isolating constants here, we avoid loading unnecessary JS file on the frontend (e.g., the /shop page), enhancing site performance.
 *
 * Context: https://github.com/woocommerce/woocommerce/pull/48141#issuecomment-2208770592.
 */

/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { objectOmit } from '@woocommerce/utils';
import type { InnerBlockTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import blockJson from './block.json';
import {
	ProductCollectionAttributes,
	ProductCollectionQuery,
	LayoutOptions,
} from './types';
import { ImageSizing } from '../../atomic/blocks/product-elements/image/types';

export const PRODUCT_COLLECTION_BLOCK_NAME = blockJson.name;
const PRODUCT_TITLE_NAME = `${ PRODUCT_COLLECTION_BLOCK_NAME }/product-title`;

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
	inherit: false,
	taxQuery: {},
	isProductCollectionBlock: true,
	featured: false,
	woocommerceOnSale: false,
	woocommerceStockStatus: getDefaultStockStatuses(),
	woocommerceAttributes: [],
	woocommerceHandPickedProducts: [],
	timeFrame: undefined,
	priceRange: undefined,
	filterable: false,
};

export const DEFAULT_ATTRIBUTES: Pick<
	ProductCollectionAttributes,
	| 'query'
	| 'tagName'
	| 'displayLayout'
	| 'queryContextIncludes'
	| 'forcePageReload'
> = {
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
				__woocommerceNamespace: PRODUCT_TITLE_NAME,
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
