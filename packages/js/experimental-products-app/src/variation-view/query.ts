/**
 * External dependencies
 */
import type { View } from '@wordpress/dataviews';

export type VariationViewQuery = {
	product_id: number;
	page: number;
	per_page: number;
	search?: string;
	order?: 'asc' | 'desc';
	orderby?: 'date' | 'id' | 'include' | 'title' | 'slug' | 'menu_order';
};

export function buildVariationViewQuery(
	view: View,
	productId: number
): VariationViewQuery {
	const query: VariationViewQuery = {
		product_id: productId,
		page: view.page ?? 1,
		per_page: view.perPage ?? 20,
	};

	if ( view.search ) {
		query.search = view.search;
	}

	if ( view.sort?.direction ) {
		query.order = view.sort.direction;
	}

	if ( view.sort?.field ) {
		query.orderby = view.sort.field === 'name' ? 'title' : 'menu_order';
	}

	return query;
}
