/**
 * Internal dependencies
 */
import { WC_PRODUCT_NAMESPACE } from './constants';
import { Product, ProductQuery } from './types';
import {
	getProductsError,
	getProductsSuccess,
	getProductsTotalCountError,
	getProductsTotalCountSuccess,
} from './actions';
import { request } from '../utils';

export function* getProducts( query: Partial< ProductQuery > ) {
	// id is always required.
	const productsQuery = {
		...query,
	};
	if (
		productsQuery &&
		productsQuery._fields &&
		! productsQuery._fields.includes( 'id' )
	) {
		productsQuery._fields = [ 'id', ...productsQuery._fields ];
	}
	try {
		const {
			items,
			totalCount,
		}: { items: Product[]; totalCount: number } = yield request<
			ProductQuery,
			Product
		>( WC_PRODUCT_NAMESPACE, productsQuery );
		yield getProductsTotalCountSuccess( query, totalCount );
		yield getProductsSuccess( query, items, totalCount );
		return items;
	} catch ( error ) {
		yield getProductsError( query, error );
		throw error;
	}
}

export function* getProductsTotalCount( query: Partial< ProductQuery > ) {
	try {
		const totalsQuery = {
			...query,
			page: 1,
			per_page: 1,
		};
		const { totalCount } = yield request< ProductQuery, Product >(
			WC_PRODUCT_NAMESPACE,
			totalsQuery
		);
		yield getProductsTotalCountSuccess( query, totalCount );
		return totalCount;
	} catch ( error ) {
		yield getProductsTotalCountError( query, error );
		throw error;
	}
}
