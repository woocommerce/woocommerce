/**
 * External dependencies
 */
import debugFactory from 'debug';

const debug = debugFactory( 'wc-analytics:tracking-cookies' );

/**
 * Cookies s.js sets for referrer attribution, the anonymous visitor ID and its unsent pixel queue.
 */
const TRACKING_COOKIE_NAMES = [ 'tk_or', 'tk_lr', 'tk_r3d', 'tk_ai', 'tk_aip', 'tk_qs' ];

/**
 * The current host and each of its parent domains, which is where s.js sets these cookies.
 */
function getCookieDomains(): string[] {
	const labels = window.location.hostname.split( '.' );
	return labels.map( ( _, index ) => '.' + labels.slice( index ).join( '.' ) );
}

/**
 * Expire the Tracks cookies on every domain they may have been set on, for when statistics consent is denied.
 */
export function clearTrackingCookies(): void {
	// Not `getCookie()`: it returns null when the same name is set both host-only and on a parent domain.
	const cookies = document.cookie.split( '; ' );
	const present = TRACKING_COOKIE_NAMES.filter( name =>
		cookies.some( cookie => cookie.startsWith( `${ name }=` ) )
	);

	if ( present.length === 0 ) {
		return;
	}

	const expired = 'path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
	const domains = getCookieDomains();

	for ( const name of present ) {
		document.cookie = `${ name }=; ${ expired }`;
		for ( const domain of domains ) {
			document.cookie = `${ name }=; domain=${ domain }; ${ expired }`;
		}
	}

	debug( 'Cleared tracking cookies: %o', present );
}
