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

// Each block's last count. A block without an entry has not run its first watch, which is the page load.
const lastAnnouncements = new WeakMap< HTMLElement, string >();

let pendingAnnouncement:
	{ timer: ReturnType< typeof setTimeout >; isChanged: boolean } | undefined;

const productResultsCountStore = {
	callbacks: {
		/**
		 * Speak the new count after client-side navigation replaces it.
		 *
		 * Every block's watch re-runs on each navigation, so a block whose count did not
		 * change must not replace an announcement from a block whose count did.
		 */
		announceResultsCount: () => {
			// Read on every run, including the first, so the watch re-runs on every navigation.
			const { announcement } =
				getServerContext< ProductResultsCountContext >();
			const { ref } = getElement();
			const previousAnnouncement = ref
				? lastAnnouncements.get( ref )
				: undefined;
			if ( ref ) {
				lastAnnouncements.set( ref, announcement );
			}

			const isChanged = announcement !== previousAnnouncement;
			if (
				previousAnnouncement === undefined ||
				! announcement ||
				( ! isChanged && pendingAnnouncement?.isChanged )
			) {
				return;
			}

			clearTimeout( pendingAnnouncement?.timer );
			pendingAnnouncement = {
				isChanged,
				timer: setTimeout( () => {
					pendingAnnouncement = undefined;
					import( '@wordpress/a11y' )
						.then( ( { speak } ) =>
							speak( announcement, 'polite' )
						)
						.catch( () => {
							// A failed chunk load leaves the router's message as the only feedback.
						} );
				}, ANNOUNCEMENT_DELAY_MS ),
			};
		},
	},
};

store( BLOCK_NAME, productResultsCountStore, { lock: true } );
