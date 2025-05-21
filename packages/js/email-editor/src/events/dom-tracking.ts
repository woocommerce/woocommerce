/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { recordEvent } from '.';

const EVENTS_TO_TRACK = [
	{
		trackName: 'header_preview_dropdown_preview_in_new_tab_selected',
		selector: `.editor-preview-dropdown__button-external`,
	},
];

/**
 * Filter events by selector and record the event.
 */
function trackMatchingEvents( event: Event ) {
	const matchedEvents = EVENTS_TO_TRACK.filter( ( candidate ) => {
		return (
			event.target &&
			( event.target as Element )?.matches?.( candidate.selector )
		);
	} );
	matchedEvents.forEach( ( event ) => {
		recordEvent( event.trackName );
	} );
}

export function initDomTracking() {
	const isEventTrackingEnabled = applyFilters(
		'woocommerce_email_editor_events_tracking_enabled',
		false
	);

	if ( ! isEventTrackingEnabled ) {
		return;
	}

	document.addEventListener( 'click', trackMatchingEvents );
}
