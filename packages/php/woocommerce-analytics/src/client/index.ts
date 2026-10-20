/* global jQuery */

/**
 * Internal dependencies
 */
import './public-path';
import { consentManager } from './consent';
import { clearTrackingCookies } from './tracking-cookies';
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
		} else {
			clearTrackingCookies();
			// Until s.js loads, events wait in this array, and s.js sends them when it arrives.
			if ( Array.isArray( window._wca ) ) {
				window._wca.length = 0;
			}
		}
	} );

	consentManager.waitForConsentApi( () =>
		consentManager.waitForConsentType( () => {
			if ( consentManager.hasAnalyticsConsent() ) {
				startTracking();
			} else {
				// Consent may have been withdrawn on a page without analytics, after an earlier visit set these.
				clearTrackingCookies();
			}
		} )
	);
} );
