/**
 * External dependencies
 */
import { Product, ProductVariation } from '@woocommerce/data';

export type UseVariationsProps = {
	productId: Product[ 'id' ];
};

export type GetVariationsRequest = {
	productId: number;
	page?: number;
	perPage?: number;
	order?: 'asc' | 'desc';
	orderby?: string;
};

export type GetVariationsResponse = {
	data: ProductVariation[];
	totalCount: number;
};
