/** @format */

/**
 * Record an event to Tracks
 *
 * @param {String} eventName The name of the event to record, always prefixed with wca_
 * @param {Object} eventProperties event properties to include in the event
 */

export function recordEvent( eventName, eventProperties ) {
	if ( ! wcSettings.trackingEnabled ) {
		return false;
	}

	// TODO - should we add validation/whitelist of tracks
	// TODO - Don't send tracks in dev envs maybe based off WP_DEBUG?
	const event = `wca_${ eventName }`;

	// Should already be initialized via inline ./lib/clicent-assets.php
	// but just being extra safe
	window._tkq = window._tkq || [];
	window._tkq.push( [ 'recordEvent', event, eventProperties ] );
}

/**
 * Record a page view to Tracks
 *
 * @param {String} path the page/path to record a page view for
 */

export function recordPageView( path ) {
	if ( ! path ) {
		return;
	}
	recordEvent( 'page_view', { path } );
}
