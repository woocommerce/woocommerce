/**
 * External dependencies
 */
import { getContext as getContextFn, store } from '@woocommerce/interactivity';

export interface ProductFiltersContext {
	isDialogOpen: boolean;
}

const getContext = ( ns?: string ) =>
	getContextFn< ProductFiltersContext >( ns );

const productFiltersOverlayNavigation = {
	state: {},
	actions: {},
	callbacks: {},
};

store( 'woocommerce/product-filters', productFiltersOverlayNavigation );

export type ProductFiltersOverlayNavigation =
	typeof productFiltersOverlayNavigation;
