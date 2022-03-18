/**
 * External dependencies
 */
import debug from 'debug';

/**
 * Module variables
 */
const tracksDebug = debug( 'wc-admin:tracks' );

/**
 * Record an event to Tracks
 *
 * @param {string} eventName       The name of the event to record, don't include the wcadmin_ prefix
 * @param {Object} eventProperties event properties to include in the event
 */
export function recordEvent( eventName, eventProperties ) {
	tracksDebug( 'recordevent %s %o', 'wcadmin_' + eventName, eventProperties, {
		_tqk: window._tkq,
		shouldRecord:
			process.env.NODE_ENV !== 'development' &&
			!! window._tkq &&
			!! window.wcTracks &&
			!! window.wcTracks.isEnabled,
	} );

	if (
		! window.wcTracks ||
		typeof window.wcTracks.recordEvent !== 'function' ||
		process.env.NODE_ENV === 'development'
	) {
		return false;
	}

	window.wcTracks.recordEvent( eventName, eventProperties );
}

const tracksQueue = {
	localStorageKey() {
		return 'tracksQueue';
	},

	clear() {
		if ( ! window.localStorage ) {
			return;
		}

		window.localStorage.removeItem( tracksQueue.localStorageKey() );
	},

	get() {
		if ( ! window.localStorage ) {
			return [];
		}

		let items = window.localStorage.getItem(
			tracksQueue.localStorageKey()
		);

		items = items ? JSON.parse( items ) : [];
		items = Array.isArray( items ) ? items : [];

		return items;
	},

	add( ...args ) {
		if ( ! window.localStorage ) {
			// If unable to queue, run it now.
			tracksDebug( 'Unable to queue, running now', { args } );
			recordEvent.apply( null, args || undefined );
			return;
		}

		let items = tracksQueue.get();
		const newItem = { args };

		items.push( newItem );
		items = items.slice( -100 ); // Upper limit.

		tracksDebug( 'Adding new item to queue.', newItem );
		window.localStorage.setItem(
			tracksQueue.localStorageKey(),
			JSON.stringify( items )
		);
	},

	process() {
		if ( ! window.localStorage ) {
			return; // Not possible.
		}

		const items = tracksQueue.get();
		tracksQueue.clear();

		tracksDebug( 'Processing items in queue.', items );

		items.forEach( ( item ) => {
			if ( typeof item === 'object' ) {
				tracksDebug( 'Processing item in queue.', item );
				recordEvent.apply( null, item.args || undefined );
			}
		} );
	},
};

/**
 * Queue a tracks event.
 *
 * This allows you to delay tracks  events that would otherwise cause a race condition.
 * For example, when we trigger `wcadmin_tasklist_appearance_continue_setup` we're simultaneously moving the user to a new page via
 * `window.location`. This is an example of a race condition that should be avoided by enqueueing the event,
 * and therefore running it on the next pageview.
 *
 * @param {string} eventName       The name of the event to record, don't include the wcadmin_ prefix
 * @param {Object} eventProperties event properties to include in the event
 */

export function queueRecordEvent( eventName, eventProperties ) {
	tracksQueue.add( eventName, eventProperties );
}

/**
 * Record a page view to Tracks
 *
 * @param {string} path            the page/path to record a page view for
 * @param {Object} extraProperties extra event properties to include in the event
 */

export function recordPageView( path, extraProperties ) {
	if ( ! path ) {
		return;
	}

	recordEvent( 'page_view', { path, ...extraProperties } );

	// Process queue.
	tracksQueue.process();
}
