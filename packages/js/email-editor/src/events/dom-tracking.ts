/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { recordEvent } from '.';

const EVENTS_TO_TRACK = [
	// Header preview dropdown preview in new tab selected
	{
		track: 'header_preview_dropdown_preview_in_new_tab_selected',
		selector: '.editor-preview-dropdown__button-external',
	},
	// Header toggle block tools
	{
		track: () => {
			const isBlockToolsCollapsed = ! document.getElementsByClassName(
				'is-collapsed editor-collapsible-block-toolbar'
			).length;
			recordEvent( 'header_blocks_tool_button_clicked', {
				isBlockToolsCollapsed,
			} );
		},
		selector: '.editor-collapsible-block-toolbar__toggle',
	},
];

/**
 * Filter events by selector and record the event.
 */
function trackMatchingEvents( event: Event ) {
	const matchedEvents = EVENTS_TO_TRACK.filter( ( candidate ) => {
		return (
			event.target &&
			( ( event.target as Element )?.matches?.( candidate.selector ) ||
				( event.target as Element )?.closest?.( candidate.selector ) )
		);
	} );
	matchedEvents.forEach( ( matched ) => {
		if ( typeof matched.track === 'function' ) {
			matched.track( event );
		} else {
			recordEvent( matched.track );
		}
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
