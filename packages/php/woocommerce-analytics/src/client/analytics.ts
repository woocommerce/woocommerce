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
// The last acquisition source reported as a session_entry event, and when.
const LAST_ENTRY_KEY = 'wcAnalyticsLastEntry';
// A repeat of the same source within this window (a reload, back/forward) is not a new entry.
const ENTRY_REPEAT_WINDOW_MS = 30 * 60 * 1000;
// sourcebuster's own aliases, as WooCommerce's Order Attribution writes them.
const ENTRY_FIELDS: Record< string, string > = {
	typ: 'source_type',
	src: 'utm_source',
	mdm: 'utm_medium',
	cmp: 'utm_campaign',
	cnt: 'utm_content',
	trm: 'utm_term',
	id: 'utm_id',
	ep: 'entry_url',
	rf: 'entry_referrer',
	fd: 'entry_at',
};

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
	 * Initialize the analytics. The visitor id is resolved first so every event carries it.
	 */
	init = async (): Promise< void > => {
		if ( this.isInitialized ) {
			return;
		}

		await this.ensureAnonId();
		this.maybeRecordSessionEntry();

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
		// Carry the id so the tracker never mints its own.
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
	 * Report how the visitor arrived, once per acquisition source.
	 *
	 * WooCommerce's Order Attribution already classifies every landing (sourcebuster: utm,
	 * organic, referral, typein) into the `sbjs_current` and `sbjs_current_add` cookies, but
	 * only stamps the last one on an order. Reporting each new source as an event keeps the
	 * whole sequence under the visitor id. The same source seen again within half an hour is
	 * a reload or a back/forward, not a new entry; the same link followed a day later is.
	 */
	private maybeRecordSessionEntry = (): void => {
		const current = parseSourcebusterCookie( 'sbjs_current' );
		const extra = parseSourcebusterCookie( 'sbjs_current_add' );
		if ( ! current || ! extra ) {
			return;
		}

		const props: Record< string, unknown > = {};
		for ( const [ alias, value ] of Object.entries( {
			...current,
			...extra,
		} ) ) {
			const name = ENTRY_FIELDS[ alias ];
			if ( name && value && value !== '(none)' ) {
				props[ name ] = value;
			}
		}
		const key = Object.keys( current )
			.sort()
			.map( ( alias ) => `${ alias }=${ current[ alias ] }` )
			.join( '|' );
		const now = Date.now();

		try {
			const last = JSON.parse(
				window.localStorage.getItem( LAST_ENTRY_KEY ) || 'null'
			);
			if (
				last &&
				last.key === key &&
				now - Number( last.at ) < ENTRY_REPEAT_WINDOW_MS
			) {
				return;
			}
		} catch ( error ) {
			// Unreadable marker: report, and overwrite it below.
		}

		this.recordEvent( 'session_entry', props );

		try {
			window.localStorage.setItem(
				LAST_ENTRY_KEY,
				JSON.stringify( { key, at: now } )
			);
		} catch ( error ) {
			// Storage unavailable: the entry repeats on the next page, which the warehouse can dedupe.
		}
	};

	/**
	 * Resolve the visitor id.
	 *
	 * The server issues the cookie, because WebKit caps script-written cookies. The request is
	 * awaited when no cookie is visible, and fired once per browser session otherwise so the
	 * expiry rolls. If the server cannot issue one, the cookie is written here as before.
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
	 * @return The issued id, or null.
	 */
	private requestVisitorId = async (
		endpoint: string
	): Promise< string | null > => {
		const controller =
			typeof AbortController === 'undefined'
				? null
				: new AbortController();
		const timer = controller
			? window.setTimeout(
					() => controller.abort(),
					VISITOR_REQUEST_TIMEOUT_MS
			  )
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
			// Storage unavailable: the refresh repeats on the next page.
		}
	};
}

/**
 * Parse one sourcebuster cookie (`key=value|||key=value`, URL-encoded, optionally base64).
 *
 * @param name - Cookie name.
 * @return The key/value pairs, or null when the cookie is absent or not in that shape.
 */
function parseSourcebusterCookie( name: string ): Record< string, string > | null {
	const raw = getCookie( name );
	if ( ! raw ) {
		return null;
	}
	let decoded: string;
	try {
		decoded = decodeURIComponent( raw );
		if ( ! decoded.includes( '|||' ) ) {
			decoded = window.atob( decoded );
		}
	} catch ( error ) {
		return null;
	}
	if ( ! decoded.includes( '=' ) ) {
		return null;
	}
	const out: Record< string, string > = {};
	for ( const pair of decoded.split( '|||' ) ) {
		const at = pair.indexOf( '=' );
		if ( at > 0 ) {
			out[ pair.slice( 0, at ) ] = pair.slice( at + 1 );
		}
	}
	return out;
}
