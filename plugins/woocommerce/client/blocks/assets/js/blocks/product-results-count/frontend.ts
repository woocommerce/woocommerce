/**
 * External dependencies
 */
import { getServerContext, store } from '@wordpress/interactivity';

const BLOCK_NAME = 'woocommerce/product-results-count';

/**
 * How long to wait before speaking, so the count follows the router's
 * "Page Loaded." message and quick successive changes speak only once.
 */
export const ANNOUNCEMENT_DELAY_MS = 500;

type ProductResultsCountContext = {
	announcement: string;
};

let isInitialRender = true;
let pendingAnnouncement: ReturnType< typeof setTimeout > | null = null;

const productResultsCountStore = {
	callbacks: {
		/**
		 * Speak the new count after client-side navigation replaces it.
		 */
		announceResultsCount: () => {
			// Read on every run, including the first, so the watch re-runs on every navigation.
			const { announcement } =
				getServerContext< ProductResultsCountContext >();

			if ( pendingAnnouncement ) {
				clearTimeout( pendingAnnouncement );
				pendingAnnouncement = null;
			}

			if ( isInitialRender || ! announcement ) {
				isInitialRender = false;
				return;
			}

			pendingAnnouncement = setTimeout( () => {
				pendingAnnouncement = null;
				import( '@wordpress/a11y' )
					.then( ( { speak } ) => speak( announcement, 'polite' ) )
					.catch( () => {
						// A failed chunk load leaves the router's message as the only feedback.
					} );
			}, ANNOUNCEMENT_DELAY_MS );
		},
	},
};

store( BLOCK_NAME, productResultsCountStore, { lock: true } );
