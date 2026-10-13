/* global jQuery */

/**
 * Internal dependencies
 */
import './public-path';
import { consentManager } from './consent';
import { activateTrackingScript } from './tracking-script';

/**
 * Start tracking. s.js sends the events analytics queues in `window._wca` whenever it arrives.
 */
function startTracking() {
	activateTrackingScript();
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
