interface PreloadScriptParams {
	handle: string;
	src: string;
	version?: string;
}

/**
 * Appends a `<link>` tag to the document head to preload a script based on the
 * src and handle parameters.
 */
const preloadScript = ( {
	handle,
	src,
	version,
}: PreloadScriptParams ): void => {
	const handleScriptElements = document.querySelectorAll(
		`#${ handle }-js, #${ handle }-js-prefetch`
	);

	if ( handleScriptElements.length === 0 ) {
		const prefetchLink = document.createElement( 'link' );
		prefetchLink.href = version ? `${ src }?ver=${ version }` : src;
		prefetchLink.rel = 'preload';
		prefetchLink.as = 'script';
		prefetchLink.id = `${ handle }-js-prefetch`;
		document.head.appendChild( prefetchLink );
	}
};

export default preloadScript;
