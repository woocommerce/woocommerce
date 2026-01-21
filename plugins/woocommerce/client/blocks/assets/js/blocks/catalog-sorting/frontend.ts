/**
 * External dependencies
 */
import { store, getConfig } from '@wordpress/interactivity';

const BLOCK_NAME = 'woocommerce/catalog-sorting';

const catalogSortingStore = {
	actions: {
		/**
		 * Prevent default form submission.
		 */
		preventSubmit: ( event: Event ) => {
			event.preventDefault();
		},

		/**
		 * Handle sort order change.
		 */
		*handleSortChange( event: Event ): Generator {
			// Stop propagation to prevent jQuery handler from seeing the event.
			event.stopPropagation();

			const target = event.target as HTMLSelectElement;
			const newOrderBy = target.value;

			// Build URL with updated orderby parameter.
			const config = getConfig( BLOCK_NAME );
			const url = new URL( config.currentUrl, window.location.origin );

			url.searchParams.set( 'orderby', newOrderBy );
			url.searchParams.set( 'paged', '1' );

			// Determine navigation strategy.
			const sharedConfig = getConfig( 'woocommerce' );
			const isBlockTheme = sharedConfig?.isBlockTheme || false;
			const needsRefresh =
				sharedConfig?.needsRefreshForInteractivityAPI || false;

			// Classic themes or when refresh needed: full page reload.
			if ( needsRefresh || ! isBlockTheme ) {
				window.location.href = url.href;
				return;
			}

			// Block themes: client-side navigation.
			const routerModule: typeof import('@wordpress/interactivity-router') =
				yield import( '@wordpress/interactivity-router' );

			yield routerModule.actions.navigate( url.href );
		},
	},
};

store( BLOCK_NAME, catalogSortingStore );
