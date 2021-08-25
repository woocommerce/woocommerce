interface lazyLoadScriptParams {
	handle: string;
	src: string;
	version?: string;
	after?: string;
	before?: string;
	translations?: string;
}

/**
 * In WP, registered scripts are loaded into the page with an element like this:
 * `<script src='...' id='[SCRIPT_ID]'></script>`
 * This function checks whether an element matching that selector exists.
 * Useful to know if a script has already been appended to the page.
 */
const isScriptTagInDOM = ( scriptId: string ): boolean => {
	const scriptElements = document.querySelectorAll( `script#${ scriptId }` );
	return scriptElements.length > 0;
};

/**
 * Appends a `<script>` tag to the document body based on the src and handle
 * parameters. In addition, it appends additional script tags to load the code
 * needed for translations and any before and after inline scripts. See these
 * documentation pages for more information:
 *
 * https://developer.wordpress.org/reference/functions/wp_set_script_translations/
 * https://developer.wordpress.org/reference/functions/wp_add_inline_script/
 */
const lazyLoadScript = ( {
	handle,
	src,
	version,
	after,
	before,
	translations,
}: lazyLoadScriptParams ): Promise< void > => {
	return new Promise( ( resolve, reject ) => {
		// Append script translations if they doesn't exist yet in the page.
		if (
			translations &&
			! isScriptTagInDOM( `${ handle }-js-translations` )
		) {
			const handleTranslations = document.createElement( 'script' );
			handleTranslations.innerHTML = translations;
			handleTranslations.id = `${ handle }-js-translations`;
			document.body.appendChild( handleTranslations );
		}
		// Append before inline script if it doesn't exist yet in the page.
		if ( before && ! isScriptTagInDOM( `${ handle }-js-before` ) ) {
			const handleBeforeScript = document.createElement( 'script' );
			handleBeforeScript.innerHTML = before;
			handleBeforeScript.id = `${ handle }-js-before`;
			document.body.appendChild( handleBeforeScript );
		}

		if ( isScriptTagInDOM( `${ handle }-js` ) ) {
			resolve();
		} else {
			// Append script.
			const handleScript = document.createElement( 'script' );
			handleScript.src = version ? `${ src }?${ version }` : src;
			handleScript.id = `${ handle }-js`;
			handleScript.onerror = reject;
			handleScript.onload = () => {
				// Append after inline script if it doesn't exist yet in the page.
				if ( after && ! isScriptTagInDOM( `${ handle }-js-after` ) ) {
					const handleAfterScript = document.createElement(
						'script'
					);
					handleAfterScript.innerHTML = after;
					handleAfterScript.id = `${ handle }-js-after`;
					document.body.appendChild( handleAfterScript );
				}
				resolve();
			};
			document.body.appendChild( handleScript );
		}
	} );
};

export default lazyLoadScript;
