/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { objectOmit } from '@woocommerce/utils';

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

export const DEFAULT_ATTRIBUTES: Partial< ProductCollectionAttributes > = {
	query: {
		perPage: 9,
		pages: 0,
		offset: 0,
		postType: 'product',
		order: 'asc',
		orderBy: 'title',
		author: '',
		search: '',
		exclude: [],
		sticky: '',
		inherit: false,
		taxQuery: '',
		parents: [],
		isProductCollectionBlock: true,
		woocommerceOnSale: false,
		woocommerceStockStatus: getDefaultStockStatuses(),
		woocommerceAttributes: [],
	},
	tagName: 'div',
	displayLayout: {
		type: 'flex',
		columns: 3,
	},
};

export const getDefaultSettings = (
	currentAttributes: ProductCollectionAttributes
): Partial< ProductCollectionAttributes > => ( {
	displayLayout:
		DEFAULT_ATTRIBUTES.displayLayout as ProductCollectionDisplayLayout,
	query: {
		...currentAttributes.query,
		orderBy: ( DEFAULT_ATTRIBUTES.query as ProductCollectionQuery )
			.orderBy as TProductCollectionOrderBy,
		order: ( DEFAULT_ATTRIBUTES.query as ProductCollectionQuery )
			.order as TProductCollectionOrder,
	},
} );

export const DEFAULT_FILTERS = {
	woocommerceOnSale: ( DEFAULT_ATTRIBUTES.query as ProductCollectionQuery )
		.woocommerceOnSale,
	woocommerceStockStatus: getDefaultStockStatuses(),
	woocommerceAttributes: [],
};
