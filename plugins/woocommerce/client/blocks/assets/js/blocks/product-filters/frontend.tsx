/**
 * External dependencies
 */
import { store } from '@woocommerce/interactivity';

export interface ProductFiltersContext {
	productId: string;
}

const productFilters = {
	state: {},
	actions: {},
	callbacks: {},
};

store( 'woocommerce/product-filters', productFilters );

export type ProductFilters = typeof productFilters;
