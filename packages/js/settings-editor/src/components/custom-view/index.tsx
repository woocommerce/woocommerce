/**
 * External dependencies
 */
import {
	Fragment,
	createElement,
	useMemo,
	useEffect,
} from '@wordpress/element';
import { sanitize } from 'dompurify';

/**
 * Sometimes extensions will place a <script /> tag in the custom output of a settings field,
 * this function will extract the script content and return it as a SettingsField and the clean HTML.
 *
 * @param {string} html - The HTML content to process.
 * @return {Object} An object containing the clean HTML and script settings.
 */
const processCustomView = ( html: string ) => {
	const parser = new DOMParser();
	const doc = parser.parseFromString( html, 'text/html' );

	const scripts = Array.from( doc.getElementsByTagName( 'script' ) ).filter(
		( script ) => script.textContent?.trim()
	);

	// Remove scripts from the HTML.
	scripts.forEach( ( script ) => {
		script.remove();
	} );

	return {
		cleanHTML: sanitize( doc.documentElement.outerHTML ),
		scripts,
	};
};

const SettingsScript = ( { script }: { script: HTMLScriptElement } ) => {
	useEffect( () => {
		try {
			document.body.appendChild( script );
			return () => {
				document.body.removeChild( script );
			};
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to execute script:', error );
		}
	}, [ script ] );
	return null;
};

export const CustomView = ( { html }: { html: string } ) => {
	const { cleanHTML, scripts } = useMemo(
		() => processCustomView( html ),
		[ html ]
	);

	return (
		<Fragment>
			<div dangerouslySetInnerHTML={ { __html: cleanHTML } } />
			{ scripts.map( ( script, index ) => (
				<SettingsScript key={ index } script={ script } />
			) ) }
		</Fragment>
	);
};
