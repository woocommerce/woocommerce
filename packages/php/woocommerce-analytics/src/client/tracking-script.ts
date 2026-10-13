const HELD_ATTRIBUTE = 'data-wc-analytics-consent';

/**
 * Check that a URL is the weekly s.js, so markup injected into page content cannot use this to run another script.
 *
 * @param src - The held tag's src.
 */
function isTrackingScriptUrl( src: string ): boolean {
	try {
		const url = new URL( src, document.baseURI );
		return (
			url.protocol === 'https:' &&
			url.hostname === 'stats.wp.com' &&
			/^\/s-\d+\.js$/.test( url.pathname )
		);
	} catch {
		return false;
	}
}

/**
 * Run s.js, which PHP printed as `type="text/plain"` because it sets referrer cookies as soon as it runs.
 *
 * Replacing the held tag keeps its attributes (and nonce), and makes a second call a no-op.
 */
export function activateTrackingScript(): void {
	const held = document.querySelector< HTMLScriptElement >(
		`script[type="text/plain"][${ HELD_ATTRIBUTE }]`
	);

	// No src means a consent plugin took the tag over and will run it itself.
	const src = held?.getAttribute( 'src' );
	if ( ! held || ! src || ! isTrackingScriptUrl( src ) ) {
		return;
	}

	const script = document.createElement( 'script' );
	for ( const { name, value } of Array.from( held.attributes ) ) {
		if ( name !== 'type' && name !== HELD_ATTRIBUTE ) {
			script.setAttribute( name, value );
		}
	}
	script.nonce = held.nonce;
	held.replaceWith( script );
}
