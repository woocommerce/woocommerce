/**
 * External dependencies
 */
import { getContext as getContextFn, store } from '@woocommerce/interactivity';

export interface ProductFiltersInLineContext {
	isDialogOpen: boolean;
	hasPageWithWordPressAdminBar: boolean;
}

const getContext = ( ns?: string ) =>
	getContextFn< ProductFiltersInLineContext >( ns );

const productFiltersInLine = {
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

store( 'woocommerce/product-filters-in-line', productFiltersInLine );

export type ProductFiltersInLine = typeof productFiltersInLine;
