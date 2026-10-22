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
	| {
			timer: ReturnType< typeof setTimeout >;
			isChanged: boolean;
			block: HTMLElement | null;
	  }
	| undefined;

/**
 * Whether a full page load should announce the count: when the URL has a query string, such as a
 * sorted or filtered link or a page reload after either. Unlike the legacy script, this includes
 * pages with Product Filters, which it only skipped to avoid overwriting the count (#63969).
 */
const shouldAnnounceOnPageLoad = (): boolean => window.location.search !== '';

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

			if ( ! announcement ) {
				// This block now shows no results, so its own queued count is out of date.
				if ( ref && pendingAnnouncement?.block === ref ) {
					clearTimeout( pendingAnnouncement.timer );
					pendingAnnouncement = undefined;
				}
				return;
			}

			const isFirstRun = previousAnnouncement === undefined;
			const isChanged =
				! isFirstRun && announcement !== previousAnnouncement;
			if (
				( isFirstRun && ! shouldAnnounceOnPageLoad() ) ||
				( ! isChanged && pendingAnnouncement?.isChanged )
			) {
				return;
			}

			clearTimeout( pendingAnnouncement?.timer );
			pendingAnnouncement = {
				isChanged,
				block: ref,
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
