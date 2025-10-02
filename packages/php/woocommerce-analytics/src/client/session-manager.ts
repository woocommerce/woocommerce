/**
 * Internal dependencies
 */
import { COOKIE_NAME } from './constants';
import type { SessionCookieData } from './types/shared';

/**
 * Session Manager for WooCommerce Analytics
 *
 */
export default class SessionManager {
	public sessionId: string | null = null;
	public landingPage: string | null = null;
	public isEngaged: boolean = false;
	public isNewSession: boolean = false;

	private isInitialized: boolean = false;

	/**
	 * Initialize the session manager
	 */
	init = () => {
		if ( this.isInitialized ) {
			return;
		}

		this.loadOrCreateSession();
		this.isInitialized = true;
	};

	/**
	 * Load existing session or create new one
	 */
	loadOrCreateSession() {
		const cookie = this.getSessionCookie();

		if ( cookie && cookie.session_id ) {
			// Load existing session
			this.sessionId = cookie.session_id;
			this.landingPage = cookie.landing_page || null;
			this.isEngaged = cookie.is_engaged || false;
			this.isNewSession = false;
		} else {
			this.createNewSession();
		}
	}

	/**
	 * Create a new session
	 */
	createNewSession() {
		this.isNewSession = true;

		const sessionData = {
			session_id: this.generateRandomUuid(),
			landing_page: JSON.stringify( window.wcAnalytics?.breadcrumbs || [] ),
			expires: this.getSessionExpirationTime(),
		};

		if ( this.setSessionCookie( sessionData ) ) {
			// Only set session data if cookie was set successfully
			this.sessionId = sessionData.session_id;
			this.landingPage = sessionData.landing_page;
			this.isEngaged = false;
		}
	}

	/**
	 * Get session cookie data
	 *
	 * @return SessionCookieData | null
	 */
	getSessionCookie(): SessionCookieData | null {
		const rawCookie = this.getCookie( COOKIE_NAME );
		if ( ! rawCookie ) {
			return null;
		}
		try {
			return JSON.parse( decodeURIComponent( rawCookie ) ) as SessionCookieData;
		} catch ( _error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error parsing session cookie', _error );
			return null;
		}
	}

	/**
	 * Get cookie value by name
	 *
	 * @param name - Cookie name
	 * @return string | null
	 */
	getCookie( name: string ): string | null {
		const value = `; ${ document.cookie }`;
		const parts = value.split( `; ${ name }=` );
		if ( parts.length === 2 ) {
			return parts.pop()?.split( ';' ).shift() || null;
		}
		return null;
	}

	/**
	 * Set session cookie
	 *
	 * @param sessionData - Session data
	 * @return boolean
	 */
	setSessionCookie( sessionData: SessionCookieData ): boolean {
		const encoded = encodeURIComponent( JSON.stringify( sessionData ) );
		const expires = sessionData.expires || this.getSessionExpirationTime();

		document.cookie = `${ COOKIE_NAME }=${ encoded }; expires=${ expires }; path=/; secure; samesite=strict`;

		const isCookieSet = this.getCookie( COOKIE_NAME ) === encoded;
		return isCookieSet;
	}

	/**
	 * Generate session expiration time
	 * 30 minutes from now or at midnight UTC, whichever comes first
	 *
	 * @return string
	 */
	getSessionExpirationTime(): string {
		const thirtyMinutesFromNow = Date.now() + 30 * 60 * 1000;
		const midnightUTC = new Date();
		midnightUTC.setUTCDate( midnightUTC.getUTCDate() + 1 );
		midnightUTC.setUTCHours( 0, 0, 0, 0 );

		const expirationTime = Math.min( thirtyMinutesFromNow, midnightUTC.getTime() );
		return new Date( expirationTime ).toUTCString();
	}

	/**
	 * Generate a random UUID v4 token
	 *
	 * @return string
	 */
	generateRandomUuid(): string {
		// Use modern crypto.randomUUID() if available (most efficient)
		if ( typeof crypto !== 'undefined' && crypto.randomUUID ) {
			return crypto.randomUUID();
		}

		// Use crypto.getRandomValues for better security
		if ( typeof crypto !== 'undefined' && crypto.getRandomValues ) {
			const bytes = new Uint8Array( 16 );
			crypto.getRandomValues( bytes );

			// Set version (4) and variant bits according to RFC 4122
			// Set version (4) and variant bits according to RFC 4122 without using bitwise operators
			// eslint-disable-next-line no-bitwise
			bytes[ 6 ] = ( bytes[ 6 ] & 0x0f ) | 0x40; // Version 4
			// eslint-disable-next-line no-bitwise
			bytes[ 8 ] = ( bytes[ 8 ] & 0x3f ) | 0x80; // Variant 10

			// Convert to hex string with proper formatting
			const hex = Array.from( bytes, b => b.toString( 16 ).padStart( 2, '0' ) ).join( '' );

			return [
				hex.slice( 0, 8 ),
				hex.slice( 8, 12 ),
				hex.slice( 12, 16 ),
				hex.slice( 16, 20 ),
				hex.slice( 20, 32 ),
			].join( '-' );
		}

		// Fallback for older browsers (Math.random - less secure)
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, c => {
			const r = Math.floor( Math.random() * 16 );
			const v = c === 'x' ? r : ( r % 4 ) + 8;
			return v.toString( 16 );
		} );
	}

	/**
	 * Set engaged and update session cookie
	 *
	 */
	setEngaged() {
		if ( this.isEngaged ) {
			// Engagement already recorded
			return;
		}

		const sessionData = this.getSessionCookie();
		if ( sessionData && sessionData.session_id ) {
			sessionData.is_engaged = true;
			this.setSessionCookie( sessionData );
		}

		this.isEngaged = true;
	}

	/**
	 * Clear session data (for consent withdrawal)
	 */
	clearSession() {
		// Clear cookie
		document.cookie = `${ COOKIE_NAME }=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; secure; samesite=strict`;

		// Reset session variables
		this.sessionId = null;
		this.landingPage = null;
		this.isEngaged = false;
		this.isNewSession = false;
		this.isInitialized = false;
	}
}
