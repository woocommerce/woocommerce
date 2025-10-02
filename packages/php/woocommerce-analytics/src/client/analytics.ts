/**
 * External dependencies
 */
import debugFactory from 'debug';
/**
 * Internal dependencies
 */
import { ApiClient } from './api-client';
import { consentManager } from './consent';
import { EVENT_NAME_REGEX, EVENT_PREFIX, CLICK_HOUSE_EVENTS } from './constants';
import SessionManager from './session-manager';
import type { AnalyticsConfig } from './types/shared';

const debug = debugFactory( 'wc-analytics:analytics' );

/**
 * Analytics class for WooCommerce Analytics.
 */
export class Analytics {
	private isInitialized: boolean;
	private sessionManager: SessionManager;
	private apiClient: ApiClient;
	private eventQueue: AnalyticsConfig[ 'eventQueue' ];
	private commonProps: AnalyticsConfig[ 'commonProps' ];
	private features: AnalyticsConfig[ 'features' ];
	private pages: AnalyticsConfig[ 'pages' ];

	constructor(
		sessionManager: SessionManager,
		{ eventQueue = [], commonProps = {}, features = {}, pages = {} }: AnalyticsConfig
	) {
		this.isInitialized = false;

		this.sessionManager = sessionManager;
		this.apiClient = new ApiClient();
		this.eventQueue = eventQueue;
		this.commonProps = commonProps;
		this.features = features;
		this.pages = pages;
	}

	/**
	 * Initialize the analytics.
	 */
	init = () => {
		if ( this.isInitialized ) {
			return;
		}

		// Initialize API client if proxy tracking is enabled
		if ( this.features.proxy ) {
			this.apiClient.init();
		}

		/*
		 * Initialize the session manager and record the page_view event
		 * only if the ClickHouse (ch) feature is enabled as these events are relevant exclusively when ClickHouse is active.
		 */
		if ( this.features.sessionTracking ) {
			// Set up consent change listener
			consentManager.addConsentChangeListener( this.handleConsentChange );

			this.sessionManager.init();
			const { sessionId, landingPage, isNewSession } = this.sessionManager;

			// Not needed if proxy tracking is enabled.
			if ( ! this.features.proxy ) {
				// Add session ID and landing page to common properties.
				this.commonProps = {
					...this.commonProps,
					session_id: sessionId,
					landing_page: landingPage,
				};
			}

			if ( isNewSession ) {
				this.maybeRecordSessionStartedEvent();
			} else {
				this.maybeRecordEngagementEvent();
			}

			this.recordEvent( 'page_view' );
		}

		this.processEventQueue();
		this.initListeners();

		this.isInitialized = true;
	};

	/**
	 * Initialize Listeners for pages.
	 */
	initListeners = (): void => {
		if ( this.pages.isAccountPage ) {
			import( './listeners/account' ).then( ( { initListeners } ) => {
				initListeners( this.recordEvent );
			} );
		}
	};

	/**
	 * Process the event queue.
	 */
	processEventQueue = (): void => {
		for ( const event of this.eventQueue ) {
			this.recordEvent( event.eventName, event.props );
		}
	};

	/**
	 * Record an event.
	 * @param event      - The name of the event.
	 * @param properties - The properties of the event.
	 */
	recordEvent = ( event: string, properties: Record< string, unknown > = {} ): void => {
		// Check consent before recording any event
		if ( ! consentManager.hasAnalyticsConsent() ) {
			debug( 'Skipping event recording due to lack of statistics consent: %s', event );
			return;
		}

		// Validate event name
		if ( typeof event !== 'string' || ! EVENT_NAME_REGEX.test( event ) ) {
			debug( 'Skipping event recording because event name is not valid' );
			return;
		}

		const eventProperties = {
			...this.commonProps,
			...properties,
		};
		// Use API client if enabled, otherwise fall back to _wca.push
		if ( this.features.proxy ) {
			// Add client specific properties to the event properties. We don't need to do this for direct pixel tracking since it's already done there.
			this.addClientProperties( eventProperties );
			this.apiClient.addEvent( event, eventProperties );
		} else {
			this.fireDirectPixel( event, eventProperties );
		}

		// Post initialization, maybe record engagement event.
		if ( this.isInitialized ) {
			this.maybeRecordEngagementEvent();
		}
	};

	/**
	 * Fire a pixel event.
	 * @param event           - The name of the event.
	 * @param eventProperties - The properties of the event.
	 */
	fireDirectPixel = ( event: string, eventProperties: Record< string, unknown > ): void => {
		// Legacy _wca tracking
		if ( ! window._wca ) {
			debug( 'Skipping event recording because _wca is not defined' );
			return;
		}

		if ( this.features.ch && CLICK_HOUSE_EVENTS.includes( event ) ) {
			eventProperties.ch = 1;
		} else {
			delete eventProperties.ch;
		}

		debug( 'Recording event via _wca: "%s" with props %o', event, eventProperties );

		eventProperties._en = `${ EVENT_PREFIX }${ event }`;
		window._wca.push( eventProperties );
	};

	/**
	 * Add client properties to the event properties.
	 * @param eventProperties - The properties of the event.
	 */
	addClientProperties = ( eventProperties: Record< string, unknown > ): void => {
		const date = new Date();
		eventProperties._ts = date.getTime();
		eventProperties._tz = date.getTimezoneOffset() / 60;

		const nav = window.navigator;
		const screen = window.screen;
		eventProperties._lg = nav.language;
		eventProperties._pf = navigator?.platform;
		eventProperties._ht = screen.height;
		eventProperties._wd = screen.width;

		const sx =
			window.pageXOffset !== undefined
				? window.pageXOffset
				: ( document.documentElement || document.body ).scrollLeft;
		const sy =
			window.pageYOffset !== undefined
				? window.pageYOffset
				: ( document.documentElement || document.body ).scrollTop;

		eventProperties._sx = sx !== undefined ? sx : 0;
		eventProperties._sy = sy !== undefined ? sy : 0;

		if ( document.location !== undefined ) {
			eventProperties._dl = document.location.toString();
		}
		if ( document.referrer !== undefined ) {
			eventProperties._dr = document.referrer;
		}
	};

	/**
	 * Record the session started event if it's a new session and session ID is set.
	 */
	maybeRecordSessionStartedEvent = (): void => {
		if ( ! this.features.sessionTracking ) {
			return;
		}

		if ( ! this.sessionManager.isNewSession || ! this.sessionManager.sessionId ) {
			return;
		}

		this.recordEvent( 'session_started' );
	};

	/**
	 * Record the session engagement event if session is not engaged and session ID is set.
	 */
	maybeRecordEngagementEvent = (): void => {
		if ( ! this.features.sessionTracking ) {
			return;
		}

		if ( this.sessionManager.isEngaged || ! this.sessionManager.sessionId ) {
			return;
		}

		this.sessionManager.setEngaged();
		this.recordEvent( 'session_engagement' );
	};

	/**
	 * Handle consent changes
	 *
	 * @param hasConsent - Whether the user has granted consent
	 */
	handleConsentChange = ( hasConsent: boolean ): void => {
		if ( ! hasConsent ) {
			// Consent withdrawn - clear session data if session tracking is enabled
			this.sessionManager.clearSession();
		} else if ( ! this.sessionManager.sessionId ) {
			// Consent granted - reinitialize session if needed
			this.sessionManager.init();
		}
	};
}
