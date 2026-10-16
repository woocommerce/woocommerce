/**
 * External dependencies
 */
import { getElement, getServerContext, store } from '@wordpress/interactivity';

const BLOCK_NAME = 'woocommerce/product-results-count';

/**
 * How long to wait before speaking, so the count follows the router's
 * "Page Loaded." message and quick successive changes speak only once.
 */
export const ANNOUNCEMENT_DELAY_MS = 500;

type ProductResultsCountContext = {
	announcement: string;
};

// Blocks that have run their first watch; that run is the page load, not a navigation.
const renderedBlocks = new WeakSet< HTMLElement >();
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

			const { ref } = getElement();
			const isFirstRun = ! ref || ! renderedBlocks.has( ref );
			if ( ref ) {
				renderedBlocks.add( ref );
			}

			if ( isFirstRun || ! announcement ) {
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
