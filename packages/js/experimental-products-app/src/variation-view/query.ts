/**
 * Internal dependencies
 */
import { DEFAULT_VIEW as DEFAULT_PRODUCT_LIST_VIEW } from '../product-list/constants';
import {
	buildProductListQuery,
	type ProductListQuery,
} from '../product-list/query';

export type VariationViewQuery = ProductListQuery & {
	include: number[];
};

export function buildVariationViewQuery(
	productId: number
): VariationViewQuery {
	return {
		...buildProductListQuery( DEFAULT_PRODUCT_LIST_VIEW ),
		include: [ productId ],
		page: 1,
		per_page: 1,
	};
}
