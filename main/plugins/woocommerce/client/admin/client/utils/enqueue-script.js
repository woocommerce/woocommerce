/**
 * Adds a script to the page if it has not already been loaded. JS version of `wp_enqueue_script`.
 *
 * @param {Object} script        WP_Script
 * @param {string} script.handle Script handle.
 * @param {string} script.src    Script URL.
 */
export function enqueueScript( script ) {
	return new Promise( ( resolve, reject ) => {
		if ( document.querySelector( `#${ script.handle }-js` ) ) {
			resolve();
		}

		const domElement = document.createElement( 'script' );
		domElement.src = script.src;
		domElement.id = `${ script.handle }-js`;
		domElement.async = true;
		domElement.onload = resolve;
		domElement.onerror = reject;
		document.body.appendChild( domElement );
	} );
}
