/**
 * External dependencies
 */
import { store, getConfig } from '@wordpress/interactivity';

const BLOCK_NAME = 'woocommerce/catalog-sorting';

/**
 * Check if we should use client-side navigation with Interactivity API.
 *
 * @return {boolean} True if client-side navigation should be used.
 */
const shouldUseClientNavigation = (): boolean => {
	const sharedConfig = getConfig( 'woocommerce' );
	const isBlockTheme = sharedConfig?.isBlockTheme || false;
	const needsRefresh = sharedConfig?.needsRefreshForInteractivityAPI || false;

	return isBlockTheme && ! needsRefresh;
};

const catalogSortingStore = {
	actions: {
		/**
		 * Prevent default form submission only when using client-side navigation.
		 */
		preventSubmit: ( event: Event ) => {
			if ( shouldUseClientNavigation() ) {
				event.preventDefault();
			}
		},

		/**
		 * Handle sort order change.
		 */
		*handleSortChange( event: Event ): Generator {
			// Only intercept when using client-side navigation.
			if ( ! shouldUseClientNavigation() ) {
				// Let legacy handler and form submission do their job.
				return;
			}

			// Stop propagation to prevent jQuery handler from seeing the event.
			event.stopPropagation();

			const target = event.target as HTMLSelectElement;
			const newOrderBy = target.value;

			// Build URL with updated orderby parameter.
			const url = new URL( window.location.href );

			url.searchParams.set( 'orderby', newOrderBy );
			url.searchParams.set( 'paged', '1' );

			// Client-side navigation.
			const routerModule: typeof import('@wordpress/interactivity-router') =
				yield import( '@wordpress/interactivity-router' );

			yield routerModule.actions.navigate( url.href );
		},
	},
};

store( BLOCK_NAME, catalogSortingStore );
