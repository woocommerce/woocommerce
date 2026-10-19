/* global jQuery */

/**
 * Internal dependencies
 */
import './public-path';
import { consentManager } from './consent';
import { activateTrackingScript } from './tracking-script';

/**
 * Start tracking. Safe to call again, and s.js sends the events analytics queues in `window._wca` whenever it arrives.
 */
function startTracking() {
	activateTrackingScript();
	import( './init' );
}

jQuery( () => {
	if ( ! window.wcAnalytics ) {
		return;
	}

	// Consent given on the page counts without the consent type, so this does not wait for it.
	consentManager.addConsentChangeListener( ( hasConsent: boolean ) => {
		if ( hasConsent ) {
			startTracking();
		}
	} );

	consentManager.waitForConsentType( () => {
		if ( consentManager.hasAnalyticsConsent() ) {
			startTracking();
		}
	} );
} );
