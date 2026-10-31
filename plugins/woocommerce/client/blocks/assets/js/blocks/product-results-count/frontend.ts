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

// Each block's last count. A block without an entry has not run its first watch yet.
const lastAnnouncements = new WeakMap< HTMLElement, string >();

// Blocks rendered with the page. A block that mounts later came from a navigation, such as clearing filters on a page with no results.
const pageLoadBlocks = new WeakSet< Element >(
	Array.from( document.querySelectorAll( '.wc-block-product-results-count' ) )
);

// The queued count. It stays set until @wordpress/a11y has loaded, so a later navigation can still cancel it.
let pendingAnnouncement:
	| {
			timer?: ReturnType< typeof setTimeout >;
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
		 *
		 * @return Cleanup the watch runs before the next run and when the block leaves the page.
		 */
		announceResultsCount: () => {
			// Read on every run, including the first, so the watch re-runs on every navigation.
			const { announcement } =
				getServerContext< ProductResultsCountContext >();
			const { ref } = getElement();
			// A page with no results drops the block, so its queued count must not be spoken.
			const cancelOwnAnnouncement = () => {
				if ( ref && pendingAnnouncement?.block === ref ) {
					clearTimeout( pendingAnnouncement.timer );
					pendingAnnouncement = undefined;
				}
			};
			const previousAnnouncement = ref
				? lastAnnouncements.get( ref )
				: undefined;
			if ( ref ) {
				lastAnnouncements.set( ref, announcement );
			}

			const isFirstRun = previousAnnouncement === undefined;
			const isPageLoad =
				isFirstRun && ( ! ref || pageLoadBlocks.has( ref ) );
			const isChanged =
				! isFirstRun && announcement !== previousAnnouncement;
			if (
				( isPageLoad && ! shouldAnnounceOnPageLoad() ) ||
				! announcement ||
				( ! isChanged && pendingAnnouncement?.isChanged )
			) {
				return cancelOwnAnnouncement;
			}

			clearTimeout( pendingAnnouncement?.timer );
			const queued: NonNullable< typeof pendingAnnouncement > = {
				isChanged,
				block: ref,
			};
			queued.timer = setTimeout( () => {
				import( '@wordpress/a11y' )
					.then( ( { speak } ) => {
						if ( pendingAnnouncement === queued ) {
							speak( announcement, 'polite' );
						}
					} )
					.catch( () => {
						// A failed chunk load leaves the router's message as the only feedback.
					} )
					.finally( () => {
						if ( pendingAnnouncement === queued ) {
							pendingAnnouncement = undefined;
						}
					} );
			}, ANNOUNCEMENT_DELAY_MS );
			pendingAnnouncement = queued;

			return cancelOwnAnnouncement;
		},
	},
};

store( BLOCK_NAME, productResultsCountStore, { lock: true } );
