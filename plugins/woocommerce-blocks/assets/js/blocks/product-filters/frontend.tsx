/**
 * External dependencies
 */
import { getContext as getContextFn, store } from '@woocommerce/interactivity';

export interface ProductFiltersContext {
	isDialogOpen: boolean;
}

const getContext = ( ns?: string ) =>
	getContextFn< ProductFiltersContext >( ns );

const productFilters = {
	state: {
		isDialogOpen: () => {
			const context = getContext();
			return context.isDialogOpen;
		},
	},
	actions: {
		closeDialog: () => {
			// const context = getContext();
			// context.isDialogOpen = false;
		},
		openDialog: () => {
			const context = getContext();
			context.isDialogOpen = true;
			console.log( 'openDialog' );
		},
	},
	callbacks: {},
};

store( 'woocommerce/product-filters', productFilters );

export type ProductFilters = typeof productFilters;
