/* global jQuery */

/**
 * Internal dependencies
 */
import './public-path';
import { consentManager } from './consent';
import { loadTrackingScript } from './tracking-script';

/**
 * Start tracking. Load s.js before analytics so the events analytics queues are sent once it arrives.
 */
function startTracking() {
	const { trackingScriptUrl } = window.wcAnalytics;

	// null means PHP already enqueued s.js. Requiring a string also skips a same-named DOM element
	// that stands in for `wcAnalytics` when the config script did not run.
	if ( typeof trackingScriptUrl === 'string' ) {
		loadTrackingScript( trackingScriptUrl );
	}

	import( './init' );
}

jQuery( () => {
	if ( ! window.wcAnalytics ) {
		return;
	}

	consentManager.waitForConsentType( () => {
		// Check for consent before initializing analytics
		if ( consentManager.hasAnalyticsConsent() ) {
			startTracking();
			return;
		}

		// Set up consent change listener to initialize when consent is granted
		consentManager.addConsentChangeListener( ( hasConsent: boolean ) => {
			if ( hasConsent ) {
				startTracking();
			}
		} );
	} );
} );
