/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type UseVariationsProps = {
	productId: Product[ 'id' ];
};

export type GetVariationsRequest = {
	product_id: number;
	page?: number;
	per_page?: number;
	order?: 'asc' | 'desc';
	orderby?: string;
	attributes?: AttributeFilters[];
};

export type AttributeFilters = { attribute: string; terms: string[] };
