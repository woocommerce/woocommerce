/* global jQuery */

/**
 * Internal dependencies
 */
import './public-path';
import { consentManager } from './consent';

jQuery( () => {
	if ( ! window.wcAnalytics ) {
		return;
	}

	// Check for consent before initializing analytics
	if ( consentManager.hasAnalyticsConsent() ) {
		import( './init' );
		return;
	}

	// Set up consent change listener to initialize when consent is granted
	consentManager.addConsentChangeListener( ( hasConsent: boolean ) => {
		if ( hasConsent ) {
			import( './init' );
		}
	} );
} );
