/**
 * External dependencies
 */
import debugFactory from 'debug';
/**
 * Internal dependencies
 */
import { ApiClient } from './api-client';
import { consentManager } from './consent';
import { EVENT_NAME_REGEX, EVENT_PREFIX } from './constants';
import SessionManager from './session-manager';
import { getCookie, generateRandomToken } from './utils';
import type { AnalyticsConfig } from './types/shared';

const debug = debugFactory( 'wc-analytics:analytics' );

const ANON_ID_COOKIE = 'tk_ai';
// One browser session: the server re-issues the cookie once per session so the expiry rolls.
const VISITOR_ISSUED_KEY = 'wcAnalyticsVisitorIssued';
const VISITOR_REQUEST_TIMEOUT_MS = 2000;

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
	private anonId: string | null;

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
		this.anonId = null;
	}

	/**
	 * Initialize the analytics.
	 *
	 * The visitor id is resolved before anything else runs, so the first event of a visit
	 * already carries it and the stats.wp.com tracker never has to mint one of its own.
	 */
	init = async (): Promise< void > => {
		if ( this.isInitialized ) {
			return;
		}

		await this.ensureAnonId();

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
			const { sessionId, landingPage, isEngaged, isNewSession } = this.sessionManager;

			// Not needed if proxy tracking is enabled: that request carries the session cookie itself.
			if ( ! this.features.proxy ) {
				// The page markup is cacheable, so the server cannot send these.
				this.commonProps = {
					...this.commonProps,
					session_id: sessionId,
					landing_page: landingPage,
					is_engaged: isEngaged,
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

		if ( this.features.ch ) {
			eventProperties.ch = 1;
		} else {
			delete eventProperties.ch;
		}

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

		debug( 'Recording event via _wca: "%s" with props %o', event, eventProperties );

		eventProperties._en = `${ EVENT_PREFIX }${ event }`;
		// Stamp the identity so the tracker uses this id instead of minting one when it cannot see the cookie.
		if ( this.anonId ) {
			eventProperties._ui = this.anonId;
			eventProperties._ut = 'anon';
		}
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

	/**
	 * Resolve the anonymous visitor id.
	 *
	 * The cookie is issued by the server (`POST /woocommerce-analytics/v1/visitor`), never by
	 * this script, because WebKit deletes script-written cookies after seven days of Safari use
	 * without interaction and caps them to 24 hours on an ad-click landing. The request goes out
	 * when no cookie is visible, and once per browser session otherwise, so the server re-issues
	 * it and the one-year expiry rolls from the most recent visit. Only the no-cookie case is
	 * awaited: the refresh does not change the id, so events need not wait for it.
	 *
	 * When the server cannot issue one, the id is written from here as before, so a visitor on a
	 * site where the endpoint is unreachable still keeps one id across pages.
	 */
	private ensureAnonId = async (): Promise< void > => {
		this.anonId = getCookie( ANON_ID_COOKIE );
		const endpoint = window.wcAnalytics?.visitorEndpoint;

		if ( endpoint && ! this.anonId ) {
			this.anonId = await this.requestVisitorId( endpoint );
		} else if ( endpoint && ! this.wasVisitorIssuedThisSession() ) {
			void this.requestVisitorId( endpoint );
		}

		if ( ! this.anonId ) {
			this.anonId = generateRandomToken( 18 );
			const expires = new Date(
				Date.now() + 1 * 365 * 24 * 60 * 60 * 1000
			).toUTCString();
			document.cookie = `${ ANON_ID_COOKIE }=${ this.anonId }; path=/; secure; samesite=lax; expires=${ expires }`;
		}
	};

	/**
	 * Ask the server to issue or refresh the visitor cookie.
	 *
	 * @param endpoint - The visitor endpoint URL.
	 * @return The issued id, or null when the server did not issue one.
	 */
	private requestVisitorId = async (
		endpoint: string
	): Promise< string | null > => {
		const controller = typeof AbortController === 'undefined' ? null : new AbortController();
		const timer = controller
			? window.setTimeout( () => controller.abort(), VISITOR_REQUEST_TIMEOUT_MS )
			: 0;

		try {
			const response = await fetch( endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: '{}',
				signal: controller ? controller.signal : undefined,
			} );

			if ( ! response.ok ) {
				debug( 'Visitor endpoint answered %d', response.status );
				return null;
			}

			const data = ( await response.json() ) as { anon_id?: unknown };
			if ( typeof data.anon_id !== 'string' || ! data.anon_id ) {
				return null;
			}

			this.markVisitorIssuedThisSession();
			return data.anon_id;
		} catch ( error ) {
			debug( 'Visitor endpoint request failed: %o', error );
			return null;
		} finally {
			if ( timer ) {
				window.clearTimeout( timer );
			}
		}
	};

	/**
	 * Whether the server already re-issued the cookie during this browser session.
	 */
	private wasVisitorIssuedThisSession = (): boolean => {
		try {
			return window.sessionStorage.getItem( VISITOR_ISSUED_KEY ) === '1';
		} catch ( error ) {
			return false;
		}
	};

	/**
	 * Remember that the server re-issued the cookie during this browser session.
	 */
	private markVisitorIssuedThisSession = (): void => {
		try {
			window.sessionStorage.setItem( VISITOR_ISSUED_KEY, '1' );
		} catch ( error ) {
			// Storage unavailable: the refresh simply repeats on the next page.
		}
	};
}
