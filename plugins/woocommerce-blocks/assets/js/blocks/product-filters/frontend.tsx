/**
 * External dependencies
 */
import { getContext as getContextFn, store } from '@woocommerce/interactivity';

export interface ProductFiltersContext {
	isDialogOpen: boolean;
	hasPageWithWordPressAdminBar: boolean;
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
		openDialog: () => {
			const context = getContext();
			document.body.classList.add( 'wc-modal--open' );
			context.hasPageWithWordPressAdminBar = Boolean(
				document.getElementById( 'wpadminbar' )
			);

			context.isDialogOpen = true;
		},
		closeDialog: () => {
			const context = getContext();
			document.body.classList.remove( 'wc-modal--open' );

			context.isDialogOpen = false;
		},
	},
	callbacks: {},
};

store( 'woocommerce/product-filters', productFilters );

export type ProductFilters = typeof productFilters;
