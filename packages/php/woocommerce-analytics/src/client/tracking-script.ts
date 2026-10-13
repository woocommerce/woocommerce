/**
 * Load s.js, which sets the Tracks referrer cookies as soon as it runs and then sends what is queued in `window._wca`.
 *
 * @param url - The weekly s.js URL from the page config.
 */
export function loadTrackingScript( url: string ): void {
	if ( Array.from( document.scripts ).some( script => script.src === url ) ) {
		return;
	}

	const script = document.createElement( 'script' );
	script.src = url;
	script.async = true;
	document.head.appendChild( script );
}
