/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

type CatalogSortingContext = {
	currentOrderBy: string;
	options: Record< string, string >;
};

store( 'woocommerce/catalog-sorting', {
	state: {
		get currentOrderBy(): string {
			const context = getContext< CatalogSortingContext >();
			return context.currentOrderBy;
		},
	},
	actions: {
		*handleSortChange(): Generator {
			const context = getContext< CatalogSortingContext >();
			const { ref } = getElement();

			if ( ! ( ref instanceof HTMLSelectElement ) ) {
				return;
			}

			const newValue = ref.value;

			// Update context
			context.currentOrderBy = newValue;

			// Build new URL with orderby parameter
			const url = new URL( window.location.href );
			url.searchParams.set( 'orderby', newValue );
			url.searchParams.set( 'paged', '1' ); // Reset to page 1

			// Navigate using Interactivity Router
			const routerModule = yield import(
				'@wordpress/interactivity-router'
			);
			yield routerModule.actions.navigate( url.href );
		},
	},
} );
