/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';

export function setItems( itemType, query, items, totalCount ) {
	return {
		type: TYPES.SET_ITEMS,
		items,
		itemType,
		query,
		totalCount,
	};
}

export function setError( itemType, query, error ) {
	return {
		type: TYPES.SET_ERROR,
		itemType,
		query,
		error,
	};
}

export function* updateProductStock( product, quantity ) {
	const updatedProduct = { ...product, stock_quantity: quantity };
	const { id, parent_id: parentId, type } = updatedProduct;

	// Optimistically update product stock.
	yield setItems( 'products', id, [ updatedProduct ], 1 );

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
		const results = yield apiFetch( {
			path: url,
			method: 'PUT',
			data: updatedProduct,
		} );
		return { success: true, ...results };
	} catch ( error ) {
		// Update failed, return product back to original state.
		yield setItems( 'products', id, [ product ], 1 );
		yield setError( id, error );
		return { success: false, ...error };
	}
}
