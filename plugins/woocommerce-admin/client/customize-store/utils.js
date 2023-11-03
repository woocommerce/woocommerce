/**
 * Internal dependencies
 */
import { DEFAULT_LOGO_WIDTH } from './assembler-hub/sidebar/constants';

export function sendMessageToParent( message ) {
	window.parent.postMessage( message, '*' );
}

export function isIframe( windowObject ) {
	return windowObject.document !== windowObject.parent.document;
}

export function editorIsLoaded() {
	window.parent.postMessage( { type: 'iframe-loaded' }, '*' );
}

export function onIframeLoad( callback ) {
	window.addEventListener( 'message', ( event ) => {
		if ( event.data.type === 'iframe-loaded' ) {
			callback();
		}
	} );
}

/**
 * Attach a listener to the window object to listen for messages from the parent window.
 *
 * @return {() => void} Remove listener function
 */
export function attachParentListeners() {
	const listener = ( event ) => {
		if ( event.data.type === 'navigate' ) {
			window.location.href = event.data.url;
		}
	};

	window.addEventListener( 'message', listener, false );

	return () => {
		window.removeEventListener( 'message', listener, false );
	};
}

/**
 * If iframe, post message. Otherwise, navigate to a URL.
 *
 * @param {*} windowObject
 * @param {*} url
 */
export function navigateOrParent( windowObject, url ) {
	if ( isIframe( windowObject ) ) {
		windowObject.parent.postMessage( { type: 'navigate', url }, '*' );
	} else {
		windowObject.location.href = url;
	}
}

/**
 * Attach listeners to an iframe to intercept and redirect navigation events.
 *
 * @param {HTMLIFrameElement} iframe
 */
export function attachIframeListeners( iframe ) {
	const iframeWindow = iframe.contentWindow;
	const iframeDocument =
		iframe.contentDocument || iframe.contentWindow?.document;

	// Listen for pushstate event
	if ( iframeWindow?.history ) {
		const originalPushState = iframeWindow.history.pushState;
		iframeWindow.history.pushState = function ( state, title, url ) {
			const urlString = url?.toString();
			if ( urlString ) {
				// If the URL is not the Assembler Hub, navigate the main window to the new URL.
				if ( urlString?.indexOf( 'customize-store' ) === -1 ) {
					window.location.href = urlString;
				} else {
					window.history.pushState( state, title, url ); // Update the main window's history
					originalPushState( state, title, url );
				}
			}
		};
	}

	// Listen for popstate event
	iframeWindow?.addEventListener( 'popstate', function ( event ) {
		window.history.replaceState(
			event.state,
			'',
			iframeWindow.location.href
		);
	} );

	// Intercept external link clicks
	iframeDocument?.addEventListener( 'click', function ( event ) {
		if ( event.target ) {
			const anchor = event.target?.closest( 'a' );
			if ( anchor && anchor.target === '_blank' ) {
				event.preventDefault();
				window.open( anchor.href, '_blank' ); // Open in new tab in parent
			} else if ( anchor ) {
				event.preventDefault();
				window.location.href = anchor.href; // Navigate parent to new URL
			}
		}
	} );
}

export const setLogoWidth = ( content, width = DEFAULT_LOGO_WIDTH ) => {
	const logoPatternReg = /<!-- wp:site-logo\s*(\{.*?\})?\s*\/-->/g;

	// Replace the logo width with the default width.
	return content.replaceAll( logoPatternReg, ( match, group ) => {
		if ( group ) {
			const json = JSON.parse( group );
			json.width = width;
			return `<!-- wp:site-logo ${ JSON.stringify( json ) } /-->`;
		}
		return `<!-- wp:site-logo {"width":${ width }} /-->`;
	} );
};
