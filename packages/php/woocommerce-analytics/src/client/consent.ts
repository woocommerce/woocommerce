/**
 * External dependencies
 */
import debugFactory from 'debug';

const debug = debugFactory( 'wc-analytics:consent' );

const WP_CONSENT_API_STATISTICS_TYPE = 'statistics' as const;

/**
 * Consent utility functions for WP Consent API integration
 */
export class ConsentManager {
	private consentListeners: Array< ( hasConsent: boolean ) => void > = [];
	private isListenerInitialized = false;

	/**
	 * Check if WP Consent API is available
	 *
	 * @return The consent status
	 */
	isWpConsentApiAvailable(): boolean {
		return typeof window.wp_has_consent === 'function';
	}

	/**
	 * Check if user has consent for analytics/statistics tracking
	 *
	 * @return The consent status
	 */
	hasAnalyticsConsent(): boolean {
		if ( ! this.isWpConsentApiAvailable() ) {
			debug( 'WP Consent API not available, defaulting to true for backward compatibility' );
			return true;
		}

		const hasConsent = window.wp_has_consent!( WP_CONSENT_API_STATISTICS_TYPE );
		debug( 'Analytics consent status:', hasConsent );
		return hasConsent;
	}

	/**
	 * Run the callback once the consent type is known.
	 *
	 * A consent plugin that sets the consent type late, for example from the visitor's region, sets
	 * `waitfor_consent_hook`. Until it dispatches `wp_consent_type_defined`, `wp_has_consent()` answers from the
	 * fallback consent type, which reports consent when it is empty.
	 *
	 * @param callback - The callback to run.
	 */
	waitForConsentType( callback: () => void ): void {
		if (
			! this.isWpConsentApiAvailable() ||
			! window.waitfor_consent_hook ||
			typeof window.wp_consent_type !== 'undefined'
		) {
			callback();
			return;
		}

		debug( 'Waiting for the consent type to be defined' );
		document.addEventListener( 'wp_consent_type_defined', callback, { once: true } );
	}

	/**
	 * Add a listener for consent changes
	 *
	 * @param callback - The callback to call when consent changes
	 */
	addConsentChangeListener( callback: ( hasConsent: boolean ) => void ): void {
		this.consentListeners.push( callback );
		this.initializeConsentListener();
	}

	/**
	 * Initialize the consent change listener if not already done
	 */
	private initializeConsentListener(): void {
		if ( this.isListenerInitialized || ! this.isWpConsentApiAvailable() ) {
			return;
		}

		debug( 'Initializing consent change listener' );

		document.addEventListener( 'wp_listen_for_consent_change', event => {
			const changedConsentCategory = ( event as CustomEvent ).detail;
			for ( const key in changedConsentCategory ) {
				if ( Object.prototype.hasOwnProperty.call( changedConsentCategory, key ) ) {
					if ( key === WP_CONSENT_API_STATISTICS_TYPE ) {
						this.notifyListeners( changedConsentCategory[ key ] === 'allow' );
					}
				}
			}
		} );

		this.isListenerInitialized = true;
	}

	/**
	 * Notify all registered listeners about consent changes
	 *
	 * @param hasConsent - The consent status
	 */
	private notifyListeners( hasConsent: boolean ): void {
		this.consentListeners.forEach( listener => {
			try {
				listener( hasConsent );
			} catch ( error ) {
				debug( 'Error in consent change listener:', error );
			}
		} );
	}
}

export const consentManager = new ConsentManager();
