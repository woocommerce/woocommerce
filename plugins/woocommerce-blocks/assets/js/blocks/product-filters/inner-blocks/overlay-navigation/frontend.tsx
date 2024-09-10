/**
 * External dependencies
 */
import { store } from '@woocommerce/interactivity';

export interface ProductFiltersContext {
	isDialogOpen: boolean;
}

const productFiltersOverlayNavigation = {
	state: {},
	actions: {},
	callbacks: {},
};

store( 'woocommerce/product-filters', productFiltersOverlayNavigation );

export type ProductFiltersOverlayNavigation =
	typeof productFiltersOverlayNavigation;
