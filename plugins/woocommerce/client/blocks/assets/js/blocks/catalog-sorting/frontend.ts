/**
 * External dependencies
 */
import { store, withSyncEvent } from '@wordpress/interactivity';

const BLOCK_NAME = 'woocommerce/catalog-sorting';

const catalogSortingStore = {
	actions: {
		/**
		 * Prevent default form submission.
		 */
		preventSubmit: withSyncEvent( ( event: Event ) => {
			event.preventDefault();
		} ),

		/**
		 * Handle sort order change.
		 */
		handleSortChange: withSyncEvent( function* ( event: Event ): Generator {
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
		} ),
	},
};

store( BLOCK_NAME, catalogSortingStore, {
	lock: true,
} );
