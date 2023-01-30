/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE, WC_ADMIN_NAMESPACE } from '../constants';
import { ItemType, Item, ProductItem, Query, ItemID } from './types';

export function setItem( itemType: ItemType, id: ItemID, item: Item ) {
	return {
		type: TYPES.SET_ITEM,
		id,
		item,
		itemType,
	};
}

export function setItems(
	itemType: ItemType,
	query: Query,
	items: Item[],
	totalCount?: number
) {
	return {
		type: TYPES.SET_ITEMS,
		items,
		itemType,
		query,
		totalCount,
	};
}

export function setItemsTotalCount(
	itemType: ItemType,
	query: Query,
	totalCount: number
) {
	return {
		type: TYPES.SET_ITEMS_TOTAL_COUNT,
		itemType,
		query,
		totalCount,
	};
}

export function setError(
	itemType: ItemType | 'createProductFromTemplate',
	query: Record< string, unknown >,
	error: unknown
) {
	return {
		type: TYPES.SET_ERROR,
		itemType,
		query,
		error,
	};
}

export function* updateProductStock(
	product: Partial< ProductItem > & { id: ProductItem[ 'id' ] },
	quantity: number
) {
	const updatedProduct = { ...product, stock_quantity: quantity };
	const { id, parent_id: parentId, type } = updatedProduct;

	// Optimistically update product stock.
	yield setItem( 'products', id, updatedProduct );

	let url = NAMESPACE;

	switch ( type ) {
		case 'variation':
			url += `/products/${ parentId }/variations/${ id }`;
			break;
		case 'variable':
		case 'simple':
		default:
			url += `/products/${ id }`;
	}
	try {
		yield apiFetch( {
			path: url,
			method: 'PUT',
			data: updatedProduct,
		} );
		return true;
	} catch ( error ) {
		// Update failed, return product back to original state.
		yield setItem( 'products', id, product );
		yield setError( 'products', { id }, error );
		return false;
	}
}

export function* createProductFromTemplate(
	itemFields: {
		template_name: string;
		status: string;
	},
	query: Query
) {
	try {
		const url = addQueryArgs(
			`${ WC_ADMIN_NAMESPACE }/onboarding/tasks/create_product_from_template`,
			query || {}
		);
		const newItem: { id: ProductItem[ 'id' ] } = yield apiFetch( {
			path: url,
			method: 'POST',
			data: itemFields,
		} );
		yield setItem( 'products', newItem.id, newItem );
		return newItem;
	} catch ( error ) {
		yield setError( 'createProductFromTemplate', query, error );
		throw error;
	}
}

export type Action = ReturnType<
	| typeof setItem
	| typeof setItems
	| typeof setItemsTotalCount
	| typeof setError
>;
