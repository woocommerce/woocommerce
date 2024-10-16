/**
 * External dependencies
 */
import { isObject } from '@woocommerce/types';
/**
 * Internal dependencies
 */
import { StockStatus } from '../../types';

export const getStockFilterData = ( results: unknown ) => {
	if ( ! isObject( results ) || ! ( 'stock_status_counts' in results ) ) {
		return [];
	}

	const { stock_status_counts: stockStatusCounts } = results;

	return ( stockStatusCounts ?? [] ) as Array< {
		status: StockStatus;
		count: number;
	} >;
};
