/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { objectOmit } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import blockJson from '../block.json';
import {
	ProductCollectionAttributes,
	ProductCollectionQuery,
	TProductCollectionOrder,
	TProductCollectionOrderBy,
} from '../types';

const defaultQuery = blockJson.attributes.query.default;

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

export const DEFAULT_FILTERS: Partial< ProductCollectionQuery > = {
	woocommerceOnSale: defaultQuery.woocommerceOnSale,
	woocommerceStockStatus: getDefaultStockStatuses(),
	search: '',
};

export const getDefaultSettings = (
	currentAttributes: ProductCollectionAttributes
): Partial< ProductCollectionAttributes > => ( {
	displayLayout: blockJson.attributes.displayLayout.default,
	query: {
		...currentAttributes.query,
		orderBy: blockJson.attributes.query.default
			.orderBy as TProductCollectionOrderBy,
		order: blockJson.attributes.query.default
			.order as TProductCollectionOrder,
	},
} );
