/**
 * Internal dependencies
 */
import blockJson from '../block.json';
import {
	ProductCollectionAttributes,
	TProductCollectionOrder,
	TProductCollectionOrderBy,
} from '../types';

const defaultQuery = blockJson.attributes.query.default;

export const DEFAULT_FILTERS = {
	woocommerceOnSale: defaultQuery.woocommerceOnSale,
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
